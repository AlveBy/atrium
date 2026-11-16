# Custom fields

[English](custom-fields.md) · [Русский](custom-fields.ru.md)

`alveby/atrium-custom-fields` lets editors add fields to any module from the admin, without a migration or a line of code: a colour and sizes for products, a "Season" for tours, a subtitle for news. The fields appear in the module's form, list, API and MCP tools, and the ones marked "Filterable" can drive a shop's filter panel with counts on 100 000 products.

- [Install](#install)
- [Adding a field](#adding-a-field)
- [Types](#types)
- [Where the values live](#where-the-values-live)
- [Reading and writing in code](#reading-and-writing-in-code)
- [Filters, sorting and facets](#filters-sorting-and-facets)
- [Hot fields: a real column](#hot-fields-a-real-column)
- [Languages and sites](#languages-and-sites)
- [API and MCP](#api-and-mcp)
- [Speed](#speed)
- [Full-text search with Scout](#full-text-search-with-scout)

## Install

```bash
composer require alveby/atrium-custom-fields
php artisan atrium:module:install alveby/atrium-custom-fields
```

This creates two tables, `custom_fields` (the definitions) and `custom_field_index` (the filter index), and adds a **Fields** button to the list of every module.

With `--demo` (or `atrium:install --demo`) and the catalog installed, the demo products get five filterable fields: weight, battery life, waterproof, connectivity and warranty. Their names and options are written for every interface language (`resources/demo/{locale}/fields.php`), and the shop's main language (`APP_LOCALE`) picks the units: pounds for English, a US shop, kilograms for every other language. A language without demo texts gets the English ones.

## Adding a field

Open a module's list, press **Fields** and **Add a field**. A field has:

| | |
|---|---|
| Name | The label in the form and the list, per content language |
| Code | Latin letters, digits and `_`: the name in the API, in filters and in templates. It may not be a column of the module's table or one of its own fields |
| Type | See [Types](#types). The module, the code and the type are fixed once the field exists |
| Linked module | For a link: the module whose records it picks |
| Hint | A line under the field in the form |
| Options | For a list: one option per line, `value = Label` or just the label |
| Tab | The tab of the module's form it goes on |
| Required, Show in the list | |
| Filterable | The values are copied into the filter index: filters, facet counts, ranges |
| Sortable | The list and the API can be sorted by it |
| Searchable | The list search looks into it (lines and texts) |
| Translatable | A value per content language (lines and texts) |
| Per site | A value per site: a price or a stock that differs between sites |
| Site | Show the field on one site only |

The module's table gets a JSON column `custom` with its first field. A module may add it in its own migration instead:

```php
$table->json('custom')->nullable();
```

## Types

| Type | Value | Filter index |
|---|---|---|
| `string` | A line, up to 255 characters | text |
| `text` | Several lines | — |
| `number` | An integer | number |
| `decimal` | A number with a fraction | number |
| `boolean` | Yes or no | number (0/1) |
| `date` | `YYYY-MM-DD` | date |
| `select` | One option | text |
| `multiselect` | Several options | text, a row per option |
| `link` | The id of a record of another module | number |
| `file`, `image` | A file URL | — |

## Where the values live

A record keeps its values in its `custom` column:

```json
{"color": "red", "sizes": ["s", "m"], "subtitle": {"en": "Crunchy", "de": "Knackig"}, "stock": {"1": 5, "2": 0}}
```

A translatable value is an object by language, a per-site value an object by site id. Reading the column needs no join, and a module without fields pays nothing.

For filters and sorting the values of "Filterable" and "Sortable" fields are also kept in `custom_field_index`, one row per value: `entity_type`, `entity_id`, `field_id`, `site_id`, `locale` and one of `value_num`, `value_str`, `value_date`, with indexes on `(field_id, site_id, locale, value, entity_id)`. The index is updated whenever a record is saved or deleted, however it is saved (the admin, the API, a seeder, an import job), and rebuilt with:

```bash
php artisan atrium:custom-fields:reindex
php artisan atrium:custom-fields:reindex "Atrium\Catalog\Models\Product" --field=color
```

Ticking "Filterable" on an existing field fills its index rows by a queued job.

## Reading and writing in code

```php
use Atrium\CustomFields\CustomFields;

$fields = app(CustomFields::class);

$fields->get($product, 'color');              // in the current site and language
$fields->get($product, 'subtitle', 'de');     // a missing translation falls back to the default language
$fields->set($product, 'color', 'blue');
$product->save();
```

Or with the trait on the model:

```php
use Atrium\CustomFields\Concerns\HasCustomFields;

class Product extends Model
{
    use HasCustomFields;
}

$product->custom('color');
$product->setCustom('color', 'blue')->save();
```

In a Blade template: `{{ $product->custom('color') }}`, or `app(CustomFields::class)->get($product, 'color')` for a model without the trait.

## Filters, sorting and facets

```php
Product::query()
    ->where('active', true)
    ->whereCustom('color', 'red')                    // =, or any of a list
    ->whereCustom('weight', '<=', 5)                 // =, !=, <, <=, >, >=, like
    ->whereCustomBetween('price', 10, 50)
    ->orderByCustom('rating', 'desc')                // records without a value go last
    ->paginate(24);
```

A filter panel sends everything at once, in one shape:

```php
$filters = $request->input('filter', []);
// ['color' => ['red', 'blue'], 'sizes' => ['m'], 'price' => ['min' => 10, 'max' => 50]]

$products = Product::query()->where('active', true)->whereCustomFilters($filters)->paginate(24);

$facets = CustomFacets::for(Product::class, Product::query()->where('active', true))->filters($filters);

$facets->counts(['color', 'sizes']);
// ['color' => ['red' => 120, 'blue' => 75, ...], 'sizes' => ['m' => 80, ...]]
$facets->ranges(['price']);
// ['price' => ['min' => 9.9, 'max' => 120.0]]
```

Each field is counted with every filter but its own, so "Blue" keeps its count while "Red" is ticked. A filter is `id IN (SELECT entity_id FROM custom_field_index WHERE …)`, a count is one `GROUP BY` on the index, whatever the database.

Filtering by a field that is neither "Filterable" nor promoted throws a `LogicException` that says so: scanning JSON on every request is what the index is there to avoid.

## Hot fields: a real column

The fields read on every page of a big catalogue (a price, a stock) can move into a real indexed column of the module's table:

```bash
php artisan atrium:custom-fields:promote "Atrium\Catalog\Models\Product" price
```

The command adds the column with an index, moves the values out of the JSON and drops the field's index rows. `whereCustom()`, `orderByCustom()`, the facets, the form, the list and the API keep working with the column, and it can be used as a plain column too (`->where('price', '<', 50)`). A translatable, per-site or multiple field stays in the JSON.

## Languages and sites

A translatable field is edited with the form's language switch, like the module's own fields. The index has a row per content language; a language without a translation gets the default language's text, so a filter in German finds what the German page shows.

A per-site field has a value per site, and filters and facets look at the current site's. A field limited to one site appears only in that site's forms and lists.

## API and MCP

Custom fields are fields of the module: `POST /api/v1/catalog_products` and `PATCH` take them, the responses return them, `GET /api/v1/catalog_products?filter[color]=red,blue&filter[price][min]=10&sort=rating&direction=desc` filters and sorts by them, `search=` looks into the searchable ones. They are listed in the API documentation (`/api/v1`, `openapi.json`) and in the MCP tool `describe_module`, so an AI assistant fills them like any other field.

## Speed

A filter panel on 100 000 products (four fields: a colour of ten, one or two sizes of five, a weight and a rating), the median of seven runs on a laptop (Apple M-series, PHP 8.4, databases in Docker):

| | Products found | Filtered list, a page of 24 sorted by rating | Facets: 2 counts and 1 range | Rebuilding the index |
|---|---|---|---|---|
| MySQL 8.4 | 2 565 | 185 ms | 374 ms | 13.2 s |
| PostgreSQL 17 | 2 565 | 98 ms | 210 ms | 10.4 s |
| SQLite | 2 565 | 54 ms | 76 ms | 4.3 s |

Run it yourself:

```bash
CUSTOM_FIELDS_BENCHMARK=100000 vendor/bin/phpunit packages/custom-fields/tests/Benchmark
```

with `DB_CONNECTION=mysql` or `pgsql` and the `DB_*` settings of that database.

The benchmark runs `ANALYZE` on the index after rebuilding it, as the database does by itself a few seconds after a bulk change. Right after `atrium:custom-fields:reindex` of a large module, MySQL may need those seconds (or `ANALYZE TABLE custom_field_index`) before the filters are this fast. SQLite keeps no statistics, and needs none here.

## Full-text search with Scout

The index is for exact filters, ranges and counts. For typo-tolerant search over many fields, add [Laravel Scout](https://laravel.com/docs/scout) with Meilisearch or Typesense and put the values into the search document:

```php
use Atrium\CustomFields\CustomFields;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use Searchable;

    public function toSearchableArray(): array
    {
        $fields = app(CustomFields::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            ...collect($fields->fields($this))
                ->mapWithKeys(fn ($field) => [$field->code => $fields->value($this, $field)])
                ->all(),
        ];
    }
}
```

Declare the filterable codes as filterable attributes in the engine's index settings and use `Product::search('crunchy')->where('color', 'red')`. Scout re-indexes a record on save, so custom values follow it.
