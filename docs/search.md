# Search

[Русский](search.ru.md)

The search module (`alveby/atrium-search`) gives the site one search across every installed module: the visitor types a few words in the header and gets the pages of the current site in the current language, grouped by module, with the words marked. Editors get a box in the admin header that finds records of every module.

## Install

```bash
composer require alveby/atrium-search
php artisan atrium:module:install alveby/atrium-search
php artisan atrium:search:rebuild
```

The first command creates the index table; the second fills it with the records that already exist. After that the index follows every save and delete.

## What is searched

Every model with public pages, unless told otherwise. A record is found by its `name` and by the text fields of its admin form (`ContentField`, `TextareaField`, `WysiwygField`), as plain text without tags. A translated record is found in each language by that language's text; a record that is not translated, in every language.

A record is found only where a visitor could open it: when it is active, between its `active_from` and `active_to`, on its own site (or on every site, for modules shared by all sites), and when it has a page in the language.

A module says more in its `composer.json`:

```json
"extra": {
    "atrium": {
        "search": {
            "Acme\\Blog\\Models\\Post": {"title": "name", "content": ["preview_text", "detail_text"]},
            "Acme\\Blog\\Models\\Draft": false
        }
    }
}
```

The app has the last word in `config/atrium.php`:

```php
'search' => [
    App\Models\Article::class => ['title' => 'name', 'content' => ['preview_text', 'detail_text']],
    App\Models\Vacancy::class => false,
],
```

A model that needs more (a price, a brand's name) writes the text itself:

```php
public function toSearchDocument(string $language): ?array
{
    return ['title' => $this->name, 'content' => $this->brand?->name.' '.$this->description];
}
```

`null` leaves the record out in that language. After changing the rules, run `php artisan atrium:search:rebuild`.

A package can change the list in code, after the manifests and the config, with the `atrium.search.sources` filter: it gets the list (model => title and content) and returns it.

```php
use Atrium\Admin\Facades\Hook;

Hook::filter('atrium.search.sources', fn (array $models) => [
    ...$models,
    Acme\Events\Models\Event::class => ['title' => 'name', 'content' => ['description']],
]);
```

## How words are matched

Every word of the query must be found, and a word finds longer ones (`lamp` finds `lamps`). On MySQL and MariaDB the index uses `FULLTEXT`, on PostgreSQL a `to_tsvector('simple', …)` index; on SQLite, and for words of one or two letters, Chinese, Japanese and MySQL's stop words, the search reads the text with `LIKE`. When whole words and their beginnings find nothing, the search looks for the words inside longer ones too, so `schlauch` finds `Gartenschlauch`. The results of a module go by where the word is: in the title first, then the newest.

## On the site

With the theme (`alveby/atrium-theme`), the header gets a search box and the site gets `/search` (and `/de/search` for other languages, route name `atrium.search`):

- without a module chosen, the first results of each module, with a link to all of them;
- `?in=news` shows one module's results, page by page;
- every result shows the title and a piece of the text around the first word found, with the words in `<mark>`;
- the page is `noindex`.

Settings are in `config/atrium-search.php` (`vendor:publish --tag=atrium-search-config`):

```php
'per_page' => 10,     // results per page of one module
'group_limit' => 5,   // results of each module on the first page
'min_length' => 2,    // shorter queries are not searched
```

To put the box somewhere else, include its view:

```blade
@include('atrium-search::box')
```

## In the admin

The box in the header finds records of every module the editor may see, the way each module's own list finds them (its searchable columns, every language, a number finds the id), inactive records too. A click opens the record's form. On a screen narrower than 1280px the box is a magnifier button that opens the field under the header, so the site and language switchers keep their room; phones do without it.

## API and MCP

`GET /api/v1/site-search?q=…` finds what a visitor would find, in the modules the token may read (`{module}:read`):

```bash
curl -H "Authorization: Bearer <token>" "https://example.com/api/v1/site-search?q=lamp&module=products"
```

```json
{"data": [{"module": "products", "id": 5, "title": "Desk lamp", "url": "https://example.com/catalog/desk-lamp", "snippet": "A warm light for the desk…"}],
 "counts": {"products": 1}, "meta": {"page": 1, "per_page": 10, "total": 1, "last_page": 1}}
```

`page`, `per_page` (up to 50), `module`, `locale` (a content language; the default one when omitted) and `site` (another site's code). AI assistants get the same as the MCP tool `search_site`.

## The index

The table `search_index` keeps one row per record and language: the title, the plain text, the page address, the site, the language and the dates. It is filled on save; records changed around the models (an import with plain queries) are caught up by:

```bash
php artisan atrium:search:rebuild
php artisan atrium:search:rebuild --model="Atrium\News\Models\News"
```

## Change the look

```bash
php artisan vendor:publish --tag=atrium-search-views
php artisan vendor:publish --tag=atrium-search-lang
```
