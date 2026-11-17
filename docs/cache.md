# Cache

Atrium keeps public pages fast even when a record has dozens of properties, relations, pictures and translations. The idea:

- a list or a filter asks the database **only for IDs** (and the total), and keeps that answer;
- each record is drawn from its own **card**, a cached array with everything a page shows; the cards of a page come in **one multi-get**, and the missing ones are built together, in a few queries, whatever their number;
- a save drops exactly the cards and lists it touches, so an editor sees the change at once.

On a warm cache a product page or a list page runs no query at all. A page cache for anonymous visitors (off by default) skips PHP's view work too.

The cache works on every Laravel store. **Redis is recommended**.

## Contents

- [Stores](#stores)
- [Making a module cacheable](#making-a-module-cacheable)
- [Lists](#lists)
- [Reading cards](#reading-cards)
- [Other values: remember()](#other-values-remember)
- [When the cache is dropped](#when-the-cache-is-dropped)
- [Imports](#imports)
- [Page cache](#page-cache)
- [CDN](#cdn)
- [Warming, the admin screen and the debug line](#warming-the-admin-screen-and-the-debug-line)
- [Configuration](#configuration)
- [Benchmark](#benchmark)

## Stores

| Store | How a group is dropped | Notes |
|---|---|---|
| `redis` | tags | Recommended: fast multi-get, tags, sizes in the admin. |
| `memcached` | tags | Fine as well. |
| `file`, `database` | version keys | Works; each card is a file or a row. |
| `array` | tags | Tests only: lives one request. |

The record cache uses the app's default store. To give it its own, set `ATRIUM_CACHE_STORE=redis` (a store from `config/cache.php`). For Redis:

```dotenv
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
```

A store without tags gets **version keys** instead: every key carries a stamp made of a few counters (`all`, `site:{id}`, `card:{Class}`, `list:{Class}`), and dropping a group means bumping its counter. Old entries are never read again and expire with their TTL. `ATRIUM_CACHE_STRATEGY=versions` forces this on Redis too.

## Making a module cacheable

Add the `Cacheable` interface and the `CachesRecords` trait to the model:

```php
use Atrium\Admin\Cache\CachesRecords;
use Atrium\Admin\Contracts\Cacheable;

class Product extends Model implements Administrable, Cacheable
{
    use CachesRecords, HasAdminSeo, InteractsWithAdmin, Translatable;
}
```

That is all for most models. The card is built from the model's admin fields:

- every column, with translations as they are (the card holds the record in every language);
- the relations behind `BelongsToField`, `BelongsToManyField` and the like, loaded for all missing cards at once;
- SEO meta, pictures and galleries (with thumbnails), the public URL;
- what extenders add (the theme puts in the ready view of the record).

To name the relations yourself, override `cacheRelations()`:

```php
public function cacheRelations(): array
{
    return ['brand', 'stores', 'section'];
}
```

A related model whose saves must reach the card is a **dependency**: the card stamp includes its list counter, so a brand renamed in the admin rebuilds the product cards that show it the next time they are read, not before. Relations to the same model (related products, a parent page) are not dependencies: a save drops the cards of the records that show it, found with one query.

A row that belongs to another record through a morph pair (SEO meta, a slug) should use the `TouchesOwner` trait, so saving it drops the owner's cards. A model may also list owners in `cacheTouches()`.

## Lists

Ask for IDs, keep them, and draw the records from cards:

```php
// IDs and the total of one page: one query on a miss, none on a hit.
$page = Product::query()->where('active', true)->orderBy('sort')->cachedIds(perPage: 20, page: 2);
$page->ids;   // [12, 7, 31, …]
$page->total; // 4310

// The same, as a paginator of cards.
$products = Product::query()->where('active', true)->orderBy('sort')->cachedPaginate(20);

// Every matching record, as cards (no paging).
$questions = Question::query()->where('category_id', $id)->orderBy('sort')->cachedCards(dependsOn: [Category::class]);
```

The list key is a hash of the SQL with its bindings, the page, the site and the language. It is dropped when any record of the model is saved, deleted or restored, and when a model listed in `dependsOn` changes (use it when the query joins or filters by another table).

Times in a list query are rounded down to a minute (`RecordCache::now()`), so `active_from <= now()` gives the same key for 60 seconds. Use it instead of `now()` in public queries:

```php
->where('active_from', '<=', app(RecordCache::class)->now())
```

## Reading cards

```php
$card = Product::cached(42);                 // one card, or null
$cards = Product::cachedMany([42, 7, 31]);   // in the given order, missing ones left out

$card->name;                // in the current language, with fallback
$card->get('price');
$card->relation('brand');   // a model or a collection, built from the card
$card->seo('title');
$card->picture('preview_picture', 'detail_picture');
$card->media('photos');     // [['url' => …, 'thumbnails' => …], …]
$card->url();
$card->extra('theme');      // what an extender put in
$card->model();             // a model filled from the card, with its relations (no query)
```

The locale defaults to the current one; pass another as the second argument.

Use `model()` where a view expects a model. It is never saved: it is built from the cache.

## Other values: remember()

For anything else drawn from the database (a menu, a tree, a summary of ratings), use `remember()` and name the models it reads:

```php
$tree = app(RecordCache::class)->remember(Page::class, 'page-tree', fn () => $this->buildTree());
$summary = app(RecordCache::class)->remember([Review::class, Product::class], 'summary:'.$id, fn () => …);
```

The value is dropped when any of those models changes. It is kept per site unless `perSite: false`. Keep values plain (arrays, scalars): models in a cache are slow to unpack and easy to get stale.

A name that is not a model works too: `remember(Settings::class, 'settings:'.$module, …)` is dropped by `flushClass(Settings::class)`. Module settings are read this way, so a warm page asks the database nothing.

## When the cache is dropped

Saving, deleting or restoring a cacheable model through Eloquent (the admin, the API, MCP, your own code):

- drops the record's cards in every site and language;
- drops the cards of records of the same model that show it (a related product, a child page);
- bumps the model's list counter: its lists, the `remember()` values naming it, and the cards that depend on it are rebuilt the next time they are read.

Saving a site drops its cards (its domains are in every URL), saving a language drops everything. When a save runs inside a transaction, the cards are dropped once more after the commit, so a request in between cannot keep the old row.

Code that writes past Eloquent (`DB::table()->update()`, `insert()`) must say so:

```php
app(RecordCache::class)->flushClass(Product::class); // one model
app(RecordCache::class)->flushSite($siteId);         // one site
app(RecordCache::class)->flushAll();                 // everything
```

Cards also expire with their TTL (a day by default), so a missed drop heals itself.

## Imports

Thousands of saves in a row should not drop the cache thousands of times. Wrap the import:

```php
app(RecordCache::class)->bulk(function () {
    foreach ($rows as $row) {
        Product::updateOrCreate(['xml_id' => $row['id']], $row);
    }
});
```

Inside `bulk()` saves only note the models they touched; at the end each of them is dropped once. An import that writes with `DB::table()` passes `everything: true`, and the whole cache is dropped once at the end. The Bitrix migrator does that.

## Page cache

For visitors who are not logged in, a whole page can be kept: the second visitor gets it from the store without running the controller.

```dotenv
ATRIUM_PAGE_CACHE=true
```

- The key is the URL (scheme, host, path, sorted query) and the site; the language is in the URL.
- Only `GET` and `HEAD`, only `200` HTML answers, never the admin, never a signed-in admin or user, never Inertia or ajax, never a page that set its own cookie or flashed errors or old input, never paths in `atrium.cache.pages.except`. A response with `Cache-Control: no-store` or the `X-Atrium-No-Cache` header is not kept.
- **Form tokens**: the CSRF token in a kept page is swapped for the current visitor's on the way out, so forms keep working.
- A kept page is valid while the cards, lists and models it read stay the same: a save drops it at once. Models read in other ways can be named in `atrium.cache.pages.depends_on`.
- The answer carries `X-Atrium-Cache: HIT` or `MISS`.

Turn it on after checking that no page shows something personal to a visitor without a login (a cart counter, "recently viewed"). Put such a page in `except`, or send the header from its controller:

```php
return response()->view('cart')->header('X-Atrium-No-Cache', '1');
```

A component that renders something for one visitor only, wherever it is placed, calls `app(RecordCache::class)->dontKeepPage()` instead. The built-in image captcha does: its question can be answered once, so a page that shows it is never kept.

## CDN

Kept pages are sent with `ETag` and `Last-Modified`; a request with a matching `If-None-Match` gets `304 Not Modified`.

- A page without a form token: `Cache-Control: public, max-age=0, s-maxage=60`. A CDN may keep it for `ATRIUM_PAGE_CACHE_SHARED_MAX_AGE` seconds, the browser asks again every time.
- A page with a form token: `Cache-Control: private, no-cache`. A shared copy would hand one visitor's token to the next.

Laravel's `web` group starts a session for every visitor, so every answer carries `Set-Cookie`, and most CDNs do not keep an answer that sets a cookie. Either have the CDN ignore the session cookie on public paths, or send public pages without the session. Purging a CDN after a save is not done by Atrium. The short `s-maxage` is the bound on staleness.

## Warming, the admin screen and the debug line

After a deploy or a cleared cache:

```bash
php artisan atrium:cache:warm            # home page and first page of every list, every site and language
php artisan atrium:cache:warm --records  # also the card of every record of every cached module
php artisan atrium:cache:warm --site=main
```

A module adds its own pages with `app(RecordCache::class)->warmUsing(fn (string $language): iterable => [...])`.

**Admin → Cache** (superadmins) shows the hit rate since the last reset, the store and whether it has tags, the number of entries and their size (Redis, file and database stores), and clears one module, one site or everything.

In the local environment with `APP_DEBUG=true`, every public page gets an `X-Atrium-Debug` header and a line at the bottom of the screen:

```
queries=3 (2.1 ms); cache hits=21 misses=0; page cache=off; 18.4 ms
```

`ATRIUM_CACHE_DEBUG=false` hides it.

## Configuration

`config/atrium.php` → `cache`:

| Key | Env | Default | |
|---|---|---|---|
| `enabled` | `ATRIUM_CACHE_ENABLED` | `true` | Off: cards are built on every read, nothing is kept. |
| `store` | `ATRIUM_CACHE_STORE` | default store | A store from `config/cache.php`. |
| `strategy` | `ATRIUM_CACHE_STRATEGY` | `auto` | `auto` (tags where the store has them) or `versions`. |
| `ttl.cards` | `ATRIUM_CACHE_TTL_CARDS` | `86400` | Seconds. |
| `ttl.lists` | `ATRIUM_CACHE_TTL_LISTS` | `3600` | Seconds; also for `remember()`. |
| `ttl.pages` | `ATRIUM_CACHE_TTL_PAGES` | `600` | Seconds. |
| `time_step` | | `60` | Seconds `RecordCache::now()` is rounded to. |
| `stats` | | `true` | Count hits and misses for the admin screen. |
| `ignore` | | | Models whose saves never touch the cache. |
| `pages.enabled` | `ATRIUM_PAGE_CACHE` | `false` | The page cache. |
| `pages.shared_max_age` | `ATRIUM_PAGE_CACHE_SHARED_MAX_AGE` | `60` | `s-maxage` for a CDN. |
| `pages.except` | | `[]` | Paths never kept, as `Request::is()` patterns. |
| `pages.depends_on` | | `[]` | Models whose saves drop every kept page. |
| `debug` | `ATRIUM_CACHE_DEBUG` | `true` | The debug line (local, with `APP_DEBUG`). |

## Benchmark

`packages/theme/tests/CacheBenchmarkTest.php` fills a catalog of 10 000 products with 40 properties each (16 lines, 10 numbers, 6 lists, 4 flags, 2 texts, 2 brand links), a translated name, code and text, three related products, three pictures and SEO, and opens a list page and a product page through the theme with the cache off, cold, warm and with the page cache:

```bash
ATRIUM_BENCHMARK=1 vendor/bin/phpunit packages/theme/tests/CacheBenchmarkTest.php
```

`DB_CONNECTION` picks the database as for the rest of the suite, `ATRIUM_BENCHMARK_STORE` the store, `ATRIUM_BENCHMARK_PRODUCTS` the number of products. It fails if a list page costs more queries on page 250 than on page 1, a warm list or product page runs more than two queries, or a kept page runs any.

Results on 2026-11-17 (PHP 8.4, MySQL 8.4 and PostgreSQL 17 in Docker, Redis 7, median of five requests; queries, then PHP CPU time):

| Page | Cache off | Cold cache | Warm cache | Page cache |
|---|---|---|---|---|
| MySQL, list page 1 | 3 q, 6.8 ms | 7 q, 39.0 ms | **0 q**, 7.6 ms | **0 q**, 1.2 ms |
| MySQL, list page 250 | 3 q, 6.0 ms | 7 q, 38.9 ms | **0 q**, 8.2 ms | **0 q**, 1.2 ms |
| MySQL, product page | 6 q, 7.7 ms | 6 q, 11.0 ms | **0 q**, 4.9 ms | **0 q**, 1.4 ms |
| PostgreSQL, list page 1 | 3 q, 7.3 ms | 7 q, 48.3 ms | **0 q**, 12.1 ms | **0 q**, 1.3 ms |
| PostgreSQL, list page 250 | 3 q, 7.3 ms | 7 q, 46.9 ms | **0 q**, 15.7 ms | **0 q**, 1.7 ms |
| PostgreSQL, product page | 6 q, 13.7 ms | 6 q, 17.0 ms | **0 q**, 7.7 ms | **0 q**, 1.7 ms |

"Cache off" is the theme reading the database directly, as before the cache. A cold list costs more queries than that because it builds the whole card (brands, related products, SEO), which the product page then reads for free. The count stays the same on page 1 and page 250. With the file store the counts are the same and CPU time is about twice as high.

How to read it: the cache removes the database round trips. On a warm cache a page asks the database nothing, so the time no longer depends on the database's load or distance. A cold card costs about 2 ms of PHP per record, once. A warm list unpacks 20 cards and costs about the same PHP time as reading 20 rows. The page cache skips the controller and the views: about 1 ms.

The test prints wall times too. They include the database and the store, so they move with the machine's load and are only comparable within one run.
