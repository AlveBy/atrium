# Import and export

[English](import-export.md) · [Русский](import-export.ru.md)

Every module of the admin imports its records from CSV and Excel (xlsx) files and exports its list to them: news, pages, products, a module of your own. Every row is saved the way the module's form saves it, with the same validation, custom fields, SEO, languages and hooks, so an import can do nothing the form could not. A file exported from a list goes back in as it is, with no edits.

- [In the admin](#in-the-admin)
- [Export](#export)
- [The file](#the-file)
- [Columns](#columns)
- [Finding records again](#finding-records-again)
- [Trees: sections and categories](#trees-sections-and-categories)
- [Images](#images)
- [The check and the report](#the-check-and-the-report)
- [Large files](#large-files)
- [From the console and on a schedule](#from-the-console-and-on-a-schedule)
- [Who may import and export](#who-may-import-and-export)
- [For module authors](#for-module-authors)
- [Settings](#settings)

## In the admin

**Import** is a button above a module's list (News → Import, Pages → Import, Products → Import):

1. Upload a file. The first rows are shown with a field for every column, guessed from the column names.
2. Change what the guess got wrong, choose how records are found again and what to do with them, and press **Check**. The check reads the whole file and writes nothing.
3. The report says how many records will be created and updated, how many rows are skipped and which rows have errors and why. Fix the file and upload it again, or change the mapping and check again.
4. **Import** writes what the check showed. The same report follows, with the real numbers.

**Stop** halts a check or an import; what has been imported stays. The page keeps the last 20 imports of the module with their results.

## Export

**Export CSV** and **Export Excel (.xlsx)**, next to Import, download the module's list as it is on the screen: the search and the filters are applied, the sort is kept. Nothing is cut off at the page: every record the list matches is written.

The file has a header row and a row for each record: the id first, then every field the form has, in the order of the form, under the form's own labels. A field kept per content language has a column for each language ("Name (English)", "Name (Deutsch)"). A tree field (a section, a category) is written as a path, several values in a cell are joined with `|`, a checkbox is `1` or `0`, a date is `2026-03-05` or `2026-03-05 14:30:00`.

Import the file back and every row finds its record by the id and changes nothing. Edit a few cells first and the import updates just those records: the quickest way to change prices or texts in bulk.

A text cell that starts with `=`, `+`, `-`, `@`, a tab or a carriage return (and is not a plain number) is written with a leading apostrophe, so that a spreadsheet shows it as text instead of running it as a formula; the import takes the apostrophe off again.

## The file

- CSV or xlsx (Excel 2007 and later; an old `.xls` can be saved as xlsx or CSV in Excel). Only the first sheet of a workbook is read.
- The first row holds the column names; every other row is a record. Empty rows are left out.
- CSV in UTF-8 (with or without a BOM) or windows-1251, separated by `;`, `,` or a tab: both are recognised by themselves.
- Up to 50 MB by default (`atrium.import.max_size`).

## Columns

A column can fill any field of the module's form that takes text: name, code, price, active, sort, dates, select and multiselect values (by value or by name), a record of another module (by id or name), repeatable values, texts, pictures and galleries, SEO fields, custom fields, a tree field by its name or path. A field kept per content language takes one column per language.

The guess reads a column name as a field's label in any of the admin's 15 languages, as the field's key (`price`, `external_id`) or as a usual synonym that a module has added (`SKU`, `Артикул` for products). A column the guess does not know is left out until you choose a field for it. When a file with the same columns was imported before, its mapping and options are offered again.

Values:

- **An empty cell leaves the field as it is.** To keep a record's price, leave the cell empty; nothing is erased by accident.
- Numbers: `1 299,50`, `1,299.50` and `1.299,50` are all 1299.50.
- Yes and no: `1`/`0`, `yes`/`no`, `да`/`нет`, `true`/`false`, `Y`/`N`, `+`/`-`. A checkbox that stores a value of its own (`YES`) takes the same words.
- Dates: `2026-03-05`, `05.03.2026`, `05/03/2026`, with a time when the field has one (a date-only field drops it); Excel dates are read as dates.
- Several values in a cell (multiselect, repeatable): separated by `|` or new lines. Galleries also by commas, spaces or `;`.

## Finding records again

**Find records by** is the column that says which record a row is: `id`, `code` or whatever else the module offers (products: article number, code, external ID). A record with the same value is updated, one that is not there is created. If two records share the value, the older one is updated. Export writes the id, so an exported file finds its records without any choice.

**What to do**: create new records and update existing ones (the default), only create new ones (rows of records that exist are skipped), or only update existing ones (rows of records that are not there are skipped — handy for a price list that must not add products).

An update is partial: only the columns of the file are validated and written. A row that creates a record needs what the form needs, a name at least.

## Trees: sections and categories

A module whose form has a tree field (a product's section, a page's parent) reads it as a name anywhere in the tree (`Jackets`) or as a path from the top (`Clothes / Jackets`), case aside. A name that two items share must be written as a path; the report says so. The separator is `/` by default and can be changed in the **Separator** option.

Whether missing items are created is the module's choice. The catalog offers **Create the sections that are not there**: the missing ones of a path are made, each inside the one before, with a code made from the name as the form makes it. Without it a row with an unknown section fails.

## Images

A picture or gallery cell holds URLs or paths:

- **A URL** (`https://…`) is downloaded into the media library (a collection named like the import) and the record gets the stored file. An address is downloaded once: importing the same file again reuses it. Only JPEG, PNG, GIF and WebP are kept, judged by the content; up to 10 MB each (`image_max_size`), 10 seconds each (`image_timeout`).
- **A path** is taken when the file is already on the site: from the site's root (`/upload/catalog/1.jpg`) or on the public disk (`catalog/1.jpg`). Paths that leave the site (`..`, other schemes) are refused.

Only addresses on the internet are fetched. A host that resolves to a private, loopback, link-local or reserved address (the server itself, its network, a cloud's metadata) is refused, and every redirect, three at most, is checked the same way. A file cannot make the server knock on its own network. The check looks at whether an address is one but downloads nothing.

## The check and the report

The check runs every row through the same validation as the import, without writing: a record named in two rows of the file is counted as created once and updated once, as the import will do it, and tree items that would be created are counted too. A row fails when the form would refuse it (with the form's own messages), when a value is not one of a field's options, when a tree item or a picture is not there. The first 500 rows with reasons are listed (`errors`), the rest are only counted.

A row that fails does not stop the others. A database error on a row is logged and the row fails. A crash of the import as a whole (the disk filled up) stops it with a reason; what was imported stays.

## Large files

The file is read a chunk of 100 rows at a time (`chunk`) from where the last step stopped, so a file of 100 000 rows needs no more memory than one of 100.

- **With a queue worker** (`QUEUE_CONNECTION` other than `sync`) a job moves the import for up to 20 seconds (`seconds`) and queues the next step itself.
- **Without one** the progress page moves it for 5 seconds (`request_seconds`) every second while it is open, and the scheduler does it every minute (`atrium:import --pending`, scheduled by the admin; `php artisan schedule:run` must be in cron, as Laravel asks).

Only one of them works on an import at a time. Export reads the list in chunks of 500 records (`export.chunk`) and writes the file as it goes.

## From the console and on a schedule

```bash
php artisan atrium:import news storage/news.xlsx
php artisan atrium:import catalog_products https://supplier.example/price.csv --key=external_id --mode=update
php artisan atrium:import catalog_products price.csv --like=12 --check
php artisan atrium:export pages storage/pages.csv
php artisan atrium:export news --format=xlsx --search=spring --filter=active=1
```

`atrium:import {module} {source}`: the module is the key in the admin's address (`news`, `pages`, `catalog_products`), the source is a file or a URL (downloaded for up to two minutes).

- `--like=12`: map the columns as import number 12 did (matched by column name) and take its options. Without it the columns are guessed.
- `--map="Цена опт=price"`: a column and the field it fills; repeat it for more. `--map="Comment="` leaves a column out. The column is split from the field at the last `=`.
- `--key=id`, `--key=code`, …: the column that finds records again; `--mode=both|create|update`.
- `--option=create_sections=1`: an option of the module, `name=value` (the catalog's, the tree separator `--option=separator=">"`, and any a module of your own adds). An unknown name is refused.
- `--check`: only check and report.

It runs to the end and prints the report and every row that did not import. The exit code is 1 when a row failed or the import crashed, so a scheduled price list that went wrong is noticed:

```php
// routes/console.php
Schedule::command('atrium:import catalog_products https://supplier.example/price.csv --like=12')
    ->dailyAt('06:00')
    ->emailOutputOnFailure('shop@example.com');
```

The imports from the console are in the admin's list too, with "console" as who ran them.

`atrium:export {module} {file?}` writes the whole list, or what `--search`, `--filter=name=value` (repeat it) and `--sort`/`--direction` pick, as in the list's address. Without a file it writes `{module}-{date}.csv` in the current folder; the format is taken from the file's extension or from `--format=csv|xlsx`.

## Who may import and export

Import and export are permissions of their own, **Import** and **Export** in each module's row of the role form (`news.import`, `news.export`): an editor who may change records may not import a thousand of them or take the whole list away unless the role says so. A superadmin may always. The buttons are shown only to those who may.

## For module authors

A module imports and exports by default, from its `getAdministrationFields()`: no code is needed. A model can take part in three ways.

**Opt out.** A model that must not be imported, or exported, says so, and its routes and buttons disappear:

```php
public static function isImportableInAdmin(): bool
{
    return false;
}

public static function isExportableInAdmin(): bool
{
    return false;
}
```

The defaults are in `InteractsWithAdmin`: importable when records can be both created and edited, exportable always. The built-in Site and Language modules are export only.

**Keys.** The columns that find a record again, in order of priority (the default is `['id', 'code']`):

```php
public static function getImportKeys(): array
{
    return ['article', 'code', 'external_id', 'id'];
}
```

**More in the mapping, and options.** Two hooks, both given the model's class:

```php
use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Transfer\ImportOption;

// The columns of the mapping, array<string,Target>: add synonyms to one, or replace it.
Hook::filter('atrium.import.targets', static fn (array $targets, string $model): array => $model === Product::class
    ? [...$targets, 'price' => $targets['price']->withSynonyms(['cost', 'цена'])]
    : $targets);

// A setting of the import page, of the console's --option, and what the targets read.
Hook::filter('atrium.import.options', static fn (array $options, string $model): array => $model === Product::class
    ? [...$options, new ImportOption('create_sections', 'Create the sections that are not there', ImportOption::BOOLEAN, false)]
    : $options);
```

A target can also read a cell in its own way (`resolve`) and write it back in its own way (`export`). `packages/catalog/src/ProductImport.php` keeps everything specific to products — the synonyms, the section target that creates missing sections, the option — on top of this mechanism.

## Settings

`php artisan vendor:publish --tag=atrium-config` copies `config/atrium.php`; the keys are under `import` and `export`:

| Key | Default | |
|---|---|---|
| `import.directory` | `storage/app/private/atrium-imports` | where uploaded files wait, normalized to UTF-8 CSV; deleted when the import is done or stopped (`ATRIUM_IMPORT_DIRECTORY`) |
| `import.max_size` | `51200` | the largest upload, KB |
| `import.chunk` | `100` | rows read at a time |
| `import.seconds` | `20` | how long a queue job or a scheduled step works |
| `import.request_seconds` | `5` | how long the progress page's step works |
| `import.errors` | `500` | rows listed in the report |
| `import.image_timeout` | `10` | seconds to download a picture |
| `import.image_max_size` | `10240` | the largest picture, KB |
| `export.chunk` | `500` | records read from the database at a time |
