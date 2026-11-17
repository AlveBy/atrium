# SEO

[English](seo.md) · [Русский](seo.ru.md)

The `alveby/atrium-seo` module covers what search engines need from every page of every site and language:

- titles and descriptions from templates;
- canonical and hreflang links;
- a sitemap per language;
- IndexNow pings;
- schema.org markup;
- redirects with a 404 log;
- a check that lists what to fix, with a link to the record.

Every site has it on by default in the demo. A site without it keeps the core's simple tags and `sitemap.xml`.

- [Install](#install)
- [Titles and descriptions](#titles-and-descriptions)
- [canonical and hreflang](#canonical-and-hreflang)
- [sitemap.xml](#sitemapxml)
- [robots.txt and verification codes](#robotstxt-and-verification-codes)
- [IndexNow](#indexnow)
- [schema.org](#schemaorg)
- [Markup for your own module](#markup-for-your-own-module)
- [Redirects](#redirects)
- [The 404 log](#the-404-log)
- [The SEO check](#the-seo-check)
- [Settings](#settings)
- [For module authors](#for-module-authors)

## Install

```bash
composer require alveby/atrium-seo
php artisan atrium:module:install alveby/atrium-seo
```

The panel gets an **SEO** group:

| Screen | What it does |
|---|---|
| **SEO check** | Lists the problems of the site, one row per page and problem |
| **Redirects** | Lists redirects; for each one, any chain it leads into |
| **404 log** | Lists missing addresses, the most requested first |
| **Redirect imports** | Imports redirects from CSV files |
| **Page meta** | Sets meta for an address that is not a record |
| **Meta templates** | Sets templates for each module |

The module's settings are under **Modules → SEO**.

Until the module is installed, Composer may have it but it changes nothing on the site.

## Titles and descriptions

A page takes the first value it finds:

1. **Page meta** for its address (the home page, `/catalog`, `/news?page=2`).
2. The **SEO** tab of the record.
3. The **meta template** of its module, in the page's language.
4. The template for **every module** (`*`).
5. The built-in default for its kind of page (product, article, section, place). The defaults are written in all 15 languages.

Templates fill in variables:

| Variable | Value |
|---|---|
| `{name}` | The record's name in the page's language |
| `{site}` | The site's name |
| `{price}` | The price with the currency, in the page's language (`€49.00`, `49,00 €`) |
| `{section}` | The section's name |
| `{excerpt}` | The start of the preview or detail text, without HTML, up to 200 characters |
| `{date}` | The publication date, in the page's language (`1. März 2026`) |
| `{anything}` | The record's attribute of that name, without HTML (`{address}`, `{sku}`) |

**A part in square brackets is dropped when a variable inside it is empty.** A variable outside the brackets is required. Without it, the whole template is empty and the page falls back to the record's name or text.

| Template | Record | Result |
|---|---|---|
| `{name}[ — {price}] \| {site}` | Desk lamp, €49 | `Desk lamp — €49.00 \| Lumen` |
| `{name}[ — {price}] \| {site}` | Desk lamp, no price | `Desk lamp \| Lumen` |
| `[{date}. ]{excerpt}` | No text | no description (a date alone says nothing) |

Templates carry the site's name themselves, so the core's title suffix (`atrium.seo.title_suffix`) is switched off while the module is installed.

**Later pages of a list** add "— page 2" to the title, in the page's language.

The SEO tab of every record gets a **Search engines** select:

- **Index and follow links**;
- **Do not index, follow links** (`noindex, follow`);
- **Do not index, do not follow links** (`noindex, nofollow`).

A page kept out of the index has no hreflang links. It is left out of `sitemap.xml` and IndexNow.

**Pictures for sharing** (`og:image`) are taken in this order:

1. the SEO tab;
2. the record's own picture;
3. the site's default from the settings.

## canonical and hreflang

Each page has a canonical link to itself:

- without tracking parameters;
- with `?page=N` on later pages of a list. Every page of a list is canonical to itself, not to the first.

hreflang links list the same page in every language and on every site it is on, plus `x-default`. `x-default` is the default site's default language.

- A shared page (the home page, a list) links every language of every active site.
- A record links only the languages it is written in. A news item in English and German has no French link.
- A record that belongs to one site is not linked from the others.
- A record of a module that is not translated (one product name for all languages) has no hreflang. The theme gives it one address.
- When two sites share a language, the link points to the current site.
- A site on its own domain is linked with its domain (`https://shop.example.de/…`), a site in a folder with its folder (`/site2/…`).

The codes come from each language's `hreflang` (`pt_BR` → `pt-BR`, `zh_CN` → `zh-CN`).

## sitemap.xml

`/sitemap.xml` is an index of sitemaps, one file per language. A language with more than 50,000 addresses is split into parts:

```
/sitemap.xml
/sitemap-en-1.xml
/sitemap-de-1.xml
/sitemap-de-2.xml
```

Each address has:

- `<lastmod>`, the record's last change;
- `<xhtml:link rel="alternate">` for its other languages;
- `<image:image>` for its pictures.

Each site has its own: `https://shop.example.de/sitemap.xml`, `/site2/sitemap.xml`.

A sitemap leaves out:

- records that are switched off or not yet active;
- records marked "do not index";
- addresses with **Page meta** set to noindex;
- the sources of redirects.

The files are cached. Saving a record, a redirect or page meta in the panel rebuilds them. `atrium.sitemap.per_file` changes the size of a part.

Where the addresses come from:

- records of modules that implement `Sitemapable`;
- records the theme draws;
- classes listed in `atrium-seo.sitemap_sources` (see [For module authors](#for-module-authors)).

## robots.txt and verification codes

These are in the core and work without the module:

- `robots.txt` is edited per site under **Sites → (site) → Code**. The module adds the `Sitemap:` line with the site's own `sitemap.xml`. Off production the site answers `Disallow: /`, so a staging copy never gets indexed.
- **Google Search Console, Yandex Webmaster and Bing Webmaster** codes are set per site on the same screen. Paste only the code from the `content="…"` part of the tag; the core prints the `<meta name="google-site-verification">`, `yandex-verification` and `msvalidate.01` tags.

The SEO check reports addresses of `sitemap.xml` that `robots.txt` closes.

## IndexNow

When a record is published or changed in the panel, its addresses in every language go to Bing and Yandex through [IndexNow](https://www.indexnow.org/). They do not wait for the next crawl.

- No setup. The key is derived from the app key, one per site, and served at `/{key}.txt`.
- **Production only.** Locally and on staging nothing is sent.
- Addresses are sent after the response, so saving never waits for the engines. An engine that does not answer is logged and does not break the save.
- The same address is not sent again within 10 minutes (`atrium-seo.indexnow.throttle_minutes`).
- Records kept out of the index, switched off, or without a public page send nothing.
- Switched off per site under **Modules → SEO → IndexNow**, or everywhere with `ATRIUM_SEO_INDEXNOW=false`.

Google does not take IndexNow; it reads `sitemap.xml`.

## schema.org

Every page has **one** JSON-LD graph (`<script type="application/ld+json">` with `@graph`). Each module adds its nodes to the graph and does not print a script of its own. Nodes with the same `@id` are merged.

The built-in providers:

| Node | On which pages | Where the data comes from |
|---|---|---|
| `Organization` (or the type chosen in the settings) | Home page | Settings: name, logo, phones, email, address, profiles |
| `WebSite` with `SearchAction` | Home page | Settings: the search address with `{search_term_string}` |
| `BreadcrumbList` | Every page with breadcrumbs | The theme's breadcrumbs |
| `Product` with `Offer` | A product | Price, currency, `in_stock`, pictures, SKU, brand |
| `NewsArticle` | A news item | Name, date, change date, picture, the organization as publisher |
| `FAQPage` | The FAQ and its categories | The questions on the page |
| `AggregateRating` and `Review` | A record with published reviews | The reviews module; put into the product's node, or a node of its own |
| `LocalBusiness` (or the type chosen) | A store | Name, address, phone, coordinates, opening hours |

The settings under **Modules → SEO** are per site:

- the organization's name and type (`Organization`, `Corporation`, `LocalBusiness`, `Store`, `Restaurant`, `Hotel`, `MedicalClinic` and others);
- logo, phones, email, address and profiles (`sameAs`);
- the search address;
- the default currency;
- the default picture for sharing;
- IndexNow on or off.

Check a page with [Google's Rich Results Test](https://search.google.com/test/rich-results) or [validator.schema.org](https://validator.schema.org/). The module's own tests check every node against the schema.org types (see [Testing your markup](#testing-your-markup)).

## Markup for your own module

A module adds its nodes with a class implementing `Atrium\Seo\Contracts\SchemaProvider`:

```php
use Atrium\Seo\Contracts\SchemaProvider;
use Atrium\Seo\SeoPage;

final class EventSchema implements SchemaProvider
{
    public function nodes(SeoPage $page): array
    {
        if (! $page->record instanceof Event) {
            return [];
        }

        return [[
            '@type' => 'Event',
            '@id' => $page->mainEntityId(),
            'name' => $page->record->translated('name'),
            'startDate' => $page->record->starts_at->toAtomString(),
            'location' => ['@type' => 'Place', 'name' => $page->record->place, 'address' => $page->record->address],
            'organizer' => ['@id' => $page->organizationId()],
        ]];
    }
}
```

Register it in the module's service provider. Pushing a class name loads nothing. The class is only resolved when the SEO module is installed and a page is drawn, so your module does not depend on it:

```php
config()->push('atrium-seo.schema', EventSchema::class);
```

What `SeoPage` gives a provider:

| Member | Value |
|---|---|
| `$page->record` | The record the page shows, or `null` |
| `$page->kind` | `home`, `index` (a list), `section`, `record` or `page` |
| `$page->prefix` | The theme's address prefix (`catalog`, `news`) |
| `$page->url`, `canonical()` | The page's address |
| `$page->breadcrumbs` | `[['label' => …, 'url' => …], …]` |
| `$page->meta` | The resolved title, description, picture, robots |
| `$page->pageNumber` | The page of a list, 1 for the first |
| `$page->language()`, `site()` | The page's language and site |
| `mainEntityId()`, `organizationId()`, `websiteId()` | The `@id` values to link nodes with |
| `$page->context` | What other parts of the page left, such as the FAQ's questions |

A provider that throws is logged and leaves out only its own nodes; the rest of the page is drawn.

**To change another provider's node** instead of adding one, also implement `SchemaEnricher`. Enrichers run after every provider and get the whole graph. The reviews add their rating to the product this way.

**A view can add a node** for one page:

```blade
@php(app(\Atrium\Seo\SeoPage::class)->addSchema(['@type' => 'VideoObject', 'name' => $video->name, ...]))
```

### Types for the modules

The nodes below pass the module's validator and Google's rich-result rules. Copy the shape and fill it from your records. Required properties are in **bold**.

**Tours**: `TouristTrip` with an `Offer` per date:

```php
['@type' => 'TouristTrip', '@id' => $page->mainEntityId(),
 'name' => …, 'description' => …, 'touristType' => 'Hikers',
 'itinerary' => ['@type' => 'ItemList', 'itemListElement' => [
     ['@type' => 'ListItem', 'position' => 1, 'item' => ['@type' => 'TouristAttraction', 'name' => 'First lake']],
 ]],
 'offers' => ['@type' => 'Offer', 'price' => '120.00', 'priceCurrency' => 'EUR',
              'availability' => 'https://schema.org/InStock', 'validFrom' => '2026-12-01', 'url' => …],
 'provider' => ['@id' => $page->organizationId()]]
```

**Events**: `Event` (or `MusicEvent`, `SportsEvent`, `TheaterEvent`…). Required: **`name`**, **`startDate`**, **`location`**. Use `eventStatus` (`https://schema.org/EventScheduled`, `EventCancelled`, `EventPostponed`) and `eventAttendanceMode` (`OfflineEventAttendanceMode`, `OnlineEventAttendanceMode`, `MixedEventAttendanceMode`). An online event's `location` is a `VirtualLocation` with a `url`.

**Jobs**: `JobPosting`. Required: **`title`**, **`description`** (HTML allowed), **`datePosted`**, **`hiringOrganization`**. Also give `validThrough`, `employmentType` (`FULL_TIME`, `PART_TIME`, `CONTRACTOR`, `TEMPORARY`, `INTERN`), `jobLocation` (a `Place` with a `PostalAddress`) or `jobLocationType: TELECOMMUTE` with `applicantLocationRequirements`. Give `baseSalary` as a `MonetaryAmount` with a `QuantitativeValue` (`minValue`, `maxValue`, `unitText`: `HOUR`, `MONTH`, `YEAR`). Take the posting out of the graph, or the whole page out of the index, when it is closed.

**Booking and hotels**: `LodgingBusiness` or `Hotel`, `Hostel`, `Resort`, `BedAndBreakfast`. Required: **`name`**, **`address`**. Also give `checkinTime`, `checkoutTime`, `starRating` (`Rating`), `amenityFeature` (`LocationFeatureSpecification`), `priceRange`, and rooms as `containsPlace` → `HotelRoom` with `occupancy` and `bed`. A booking module that sells dates adds an `Offer` per room type.

**Medical**: `MedicalClinic` for the place and `Physician` for a doctor. Required: **`name`**, **`address`**. Give `medicalSpecialty` as an enumeration member (`https://schema.org/Dermatology`, `Pediatric`, `Cardiovascular`…), `openingHoursSpecification`, and `hospitalAffiliation` for a doctor. Do not put patients' data into markup.

**Real estate**: `RealEstateListing` for the listing page, with the property in `about` (`Apartment`, `House`, `SingleFamilyResidence`, `Accommodation`). Give `numberOfRooms`, `floorSize` (`QuantitativeValue` with `unitCode: MTK` for m² or `FTK` for ft²) and `address`. Put the price in `offers`; for rent, use `UnitPriceSpecification` with `unitCode: MON`.

**Classifieds**: the thing on sale, `Product` or `Car`/`Vehicle`, with `offers.itemCondition` (`NewCondition`, `UsedCondition`) and `offers.seller` (`Person` or `Organization`). For a car, give `brand`, `model`, `vehicleModelDate` and `mileageFromOdometer` (`unitCode: KMT` or `SMI`). Do not publish a private seller's phone or address in markup; name and city are enough.

**Blog**: `BlogPosting`. Required: **`headline`**. Also give `datePublished`, `dateModified`, `author` (a `Person` with `url`, or an `Organization`), `publisher` (`['@id' => $page->organizationId()]`), `image`, `mainEntityOfPage` and `keywords`.

Dates are ISO 8601 (`2026-12-01`, `2026-12-01T19:00:00+01:00`), addresses absolute (`https://…`), prices numbers (`'120.00'`), currencies ISO 4217 (`EUR`), enumerations full addresses (`https://schema.org/InStock`).

### Testing your markup

`Atrium\Seo\Schema\Validator` checks a JSON-LD document against the schema.org types in `packages/seo/resources/schema/vocabulary.php`:

- that every type is known;
- that every property belongs to its type or a parent;
- that the properties search engines require are there;
- that addresses, dates, numbers, currencies and enumeration members are well formed.

It returns one line per problem, or nothing when the document is valid:

```php
use Atrium\Seo\Schema\Validator;

$html = $this->get('/events/launch')->getContent();
preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

$this->assertSame([], Validator::errors($m[1]));
```

A module whose type is not in the file teaches it to the validator in its tests:

```php
Validator::extend(['ExerciseGym' => ['parents' => ['SportsActivityLocation'], 'required' => ['name', 'address']]]);
```

## Redirects

**SEO → Redirects** sends an old address to a new one:

| From | To | Code | Meaning |
|---|---|---|---|
| `/old-page` | `/new-page` | 301 | Moved for good; search engines move the page's weight |
| `/sale` | `/catalog` | 302 | For a while; the old address stays in search |
| `/blog/*` | `/news/*` | 301 | A whole section: `/blog/first` → `/news/first` |
| `/discontinued` | — | 410 | Gone for good; search engines drop it faster than a 404 |
| `/partner` | `https://partner.example.org/` | 301 | To another site |

307 and 308 are also there, for forms that must keep their method.

- **Letter case and trailing slashes do not matter**: `/Old-Page/` matches `/old-page`.
- **Query parameters**: `/old` also matches `/old?utm_source=mail`, and the visitor's `?utm_source=mail` goes along to the new address. A source with a query (`/list?page=2`) matches only that query, in any order of parameters.
- **Chains are walked in one hop.** With `/a → /b → /c`, a visitor to `/a` goes straight to `/c`. The list shows the chain next to the redirect so it can be shortened. The visitor gets the status of the first redirect.
- **Loops are refused.** A redirect that would lead back to its own address (`/c → /a` with `/a → /b → /c` already there) is not saved. The form says which addresses go round. A loop that came in some other way (the database, an old import) is not followed, and the SEO check reports it.
- The panel (`/admin`), the API, and anything but GET and HEAD are never redirected.
- Each site has its own redirects.
- The list counts the visits of each redirect and shows the last one. A redirect nobody has used for a year can go.

### Importing from CSV

Old sites come with hundreds of redirects. **SEO → Redirect imports → Import CSV** takes a CSV file:

```csv
source,target,status
/old-page,/new-page
/sale,/catalog,302
/discontinued,,410
```

- The delimiter is guessed: comma, semicolon or tab. Excel's semicolon files with a BOM work as they are.
- A first line that is not an address is a header.
- A line without a status takes the default chosen in the form.
- Existing redirects are kept unless **Overwrite** is on.
- Lines that would close a loop, have an unknown code or have no target are skipped. Open the import to see the report, with the line number and reason for each.

The file goes into the private disk (`storage/app/private/seo-imports`). It is never served to the public.

From the command line:

```bash
php artisan atrium:seo:redirects:import redirects.csv --site=2 --status=301 --overwrite
```

## The 404 log

**SEO → 404 log** lists the addresses visitors asked for and did not find:

- one row per address;
- how many times it was asked for;
- the last page that linked to it;
- when that was.

**Create a redirect** opens the redirect form with the address filled in. When the redirect is saved, the address leaves the log.

- Requests for `*.map`, `/.well-known/*`, `/wp-*` and `/xmlrpc.php` are not logged; they are bots. Change the list in `atrium-seo.not_found.ignore`.
- The log keeps the 5,000 most recent addresses per site (`atrium-seo.not_found.keep`).
- Switched off with `ATRIUM_SEO_404_LOG=false`.

## The SEO check

**SEO → SEO check** goes through every public page of the site in every language. It lists each problem with a link to the form of the record to fix:

| Check | Found when |
|---|---|
| No title | The page's title is empty (no name and no template) |
| Same title as other pages | Two pages of one language share a title; the row names the others |
| Title too long | Over 60 characters (`atrium-seo.check.title_max`); search engines cut it |
| No description | No description from the SEO tab, a template or the text |
| Same description as other pages | Two pages of one language share a description |
| No H1 | The record has no name in that language, so the page has no heading |
| Picture without alt | An `<img>` in the text without `alt`, or with an empty one |
| Heavy picture | A picture in the text or of the record over 300 KB (`atrium-seo.check.image_max_kb`) |
| Closed page in sitemap | An address of `sitemap.xml` that `robots.txt` closes |
| Broken link | A link in the text to an address of the site that is not a page, a redirect, a route or a file |
| Redirect chain | A redirect that leads into another |
| Redirect loop | Redirects that go round |

Click a check's name to see only that kind of problem (`?check=title_long`).

The check runs when the list is opened and the content has changed since the last run, or the last run is over an hour old. On a big site, turn that off with `ATRIUM_SEO_CHECK_INLINE=false` and schedule the command instead:

```php
// routes/console.php
Schedule::command('atrium:seo:check')->dailyAt('04:00');
```

```bash
php artisan atrium:seo:check            # every site
php artisan atrium:seo:check --site=2   # one site
```

## Settings

`php artisan vendor:publish --tag=atrium-seo-config` copies `config/atrium-seo.php`:

| Key | Default | What it does |
|---|---|---|
| `kinds` | catalog, news, stores, FAQ | Model → kind of page, for the default templates |
| `schema` | the built-in providers | `SchemaProvider` classes |
| `sitemap_sources` | `[]` | `SitemapSource` classes |
| `indexnow.enabled` | `ATRIUM_SEO_INDEXNOW`, true | IndexNow on or off |
| `indexnow.endpoints` | Bing, Yandex | Where to send |
| `indexnow.throttle_minutes` | 10 | Do not send an address twice within this time |
| `not_found.enabled` | `ATRIUM_SEO_404_LOG`, true | The 404 log on or off |
| `not_found.ignore` | bots' addresses | Paths not logged, `*` for anything |
| `not_found.keep` | 5000 | Rows kept per site |
| `check.inline` | `ATRIUM_SEO_CHECK_INLINE`, true | Run the check when the list is opened |
| `check.stale_minutes` | 60 | How old a run may be |
| `check.title_max` | 60 | Longest title |
| `check.image_max_kb` | 300 | Heaviest picture |

The interface and the default templates are in 15 languages. `php artisan vendor:publish --tag=atrium-seo-lang` copies them to change.

## For module authors

**Default templates for your kind of page.** Name a kind for your model, then ship `lang/{language}/templates.php` with it, or reuse one of the module's kinds (`product`, `article`, `section`, `place`, `default`):

```php
config()->set('atrium-seo.kinds.'.Tour::class, 'product');
```

**Pages in the sitemap.** Records of a model that implements `Atrium\Admin\Contracts\Sitemapable`, or that the theme draws, are there already.

- For their pictures, implement `Atrium\Seo\Contracts\SitemapImages`. Without it the module takes `detail_picture`, `preview_picture`, `picture`, `image`, `photo`, `cover`, `photos` and `gallery`.
- For addresses that are not records (a calendar's days, a tag's page), add a `SitemapSource`:

```php
final class CalendarDays implements SitemapSource
{
    public function urls(string $language): iterable
    {
        foreach ($this->days() as $day) {
            yield ['loc' => url("/events/{$day->format('Y-m-d')}"), 'lastmod' => $day->toAtomString()];
        }
    }
}

config()->push('atrium-seo.sitemap_sources', CalendarDays::class);
```

**Hooks** the module uses, for a module of your own to meet it without depending on it:

| Hook | Kind | What it carries |
|---|---|---|
| `atrium.head` | filter, core | HTML added to `<head>` of public pages |
| `atrium.seo.fields` | filter, core | The fields of every record's SEO tab |
| `atrium.sitemap.entries` | filter, core | A record's sitemap entries; return `[]` to leave it out, or add `images` |
| `atrium.seo.schema` | filter | The provider classes for a page |
| `atrium.record.saved` | action, core | Sends the record's addresses to IndexNow |
