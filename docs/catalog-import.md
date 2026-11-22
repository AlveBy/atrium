# Importing products

[English](catalog-import.md) · [Русский](catalog-import.ru.md)

The catalog (`alveby/atrium-catalog`) imports products from CSV and Excel (xlsx) files: a supplier's price list, a spreadsheet from the warehouse, an export from an old shop. Every row is saved the way the product form saves it, with the same validation, custom fields, SEO, languages and hooks, so an import can do nothing the form could not.

- [In the admin](#in-the-admin)
- [The file](#the-file)
- [Columns](#columns)
- [Finding products again](#finding-products-again)
- [Sections](#sections)
- [Images](#images)
- [The check and the report](#the-check-and-the-report)
- [Large files](#large-files)
- [From the console and on a schedule](#from-the-console-and-on-a-schedule)
- [Who may import](#who-may-import)
- [Settings](#settings)

## In the admin

**Products → Import** (the button above the list):

1. Upload a file. The first rows are shown with a field for every column, guessed from the column names.
2. Change what the guess got wrong, choose how products are found again and what to do with them, and press **Check**. The check reads the whole file and writes nothing.
3. The report says how many products will be created and updated, how many rows are skipped and which rows have errors and why. Fix the file and upload it again, or change the mapping and check again.
4. **Import** writes what the check showed. The same report follows, with the real numbers.

**Stop** halts a check or an import; what has been imported stays. The list on the first screen keeps the last 20 imports with their results.

## The file

- CSV or xlsx (Excel 2007 and later; an old `.xls` can be saved as xlsx or CSV in Excel). Only the first sheet of a workbook is read.
- The first row holds the column names; every other row is a product. Empty rows are left out.
- CSV in UTF-8 (with or without a BOM) or windows-1251, separated by `;`, `,` or a tab: both are recognised by themselves.
- Up to 50 MB by default (`atrium-catalog.import.max_size`).

## Columns

A column can fill any field of the product form that takes text: name, code, article number, external ID, price, active, sort, dates, label and colours (by value or by name), brand (by id or name), features, texts, pictures and photos, SEO fields, custom fields, and the section by its name or path. A field kept per content language takes one column per language: "Name (English)", "Name (Deutsch)".

The guess reads a column name as a field's label in any of the admin's 15 languages, as the field's key (`price`, `external_id`) or as a usual synonym: `SKU`, `Артикул`, `Цена`, `Category`, `Image`, `Description`. A column the guess does not know is left out until you choose a field for it. When a file with the same columns was imported before, its mapping and options are offered again.

Values:

- **An empty cell leaves the field as it is.** To keep a product's price, leave the cell empty; nothing is erased by accident.
- Numbers: `1 299,50`, `1,299.50` and `1.299,50` are all 1299.50.
- Yes and no: `1`/`0`, `yes`/`no`, `да`/`нет`, `true`/`false`, `Y`/`N`, `+`/`-`.
- Dates: `2026-03-05`, `05.03.2026`, `05/03/2026`, with a time when the field has one; Excel dates are read as dates.
- Several values in a cell (colours, features): separated by `|` or new lines. Photos also by commas, spaces or `;`.

## Finding products again

**Find products by** is the column that says which product a row is: the article number (the default), the code, or the external ID (a supplier's or 1C's code, stored in its own field). A product with the same value is updated, one that is not there is created. If two products share the value, the older one is updated.

**What to do**: create new products and update existing ones (the default), only create new ones (rows of products that exist are skipped), or only update existing ones (rows of products that are not there are skipped — handy for a price list that must not add products).

An update is partial: only the columns of the file are validated and written. A row that creates a product needs what the form needs, a name at least.

## Sections

A section is found by its name anywhere in the tree (`Jackets`) or by its path from the top (`Clothes / Jackets`), case aside. A name that two sections share must be written as a path; the report says so. The separator is `/` by default and can be changed.

**Create the sections that are not there** creates the missing ones of a path, each inside the one before, with a code made from the name as the form makes it. Without it a row with an unknown section fails.

## Images

A picture or photos cell holds URLs or paths:

- **A URL** (`https://…`) is downloaded into the media library (a collection named "Import products") and the product gets the stored file. An address is downloaded once: importing the same file again reuses it. Only JPEG, PNG, GIF and WebP are kept, judged by the content; up to 10 MB each (`image_max_size`), 10 seconds each (`image_timeout`).
- **A path** is taken when the file is already on the site: from the site's root (`/upload/catalog/1.jpg`) or on the public disk (`catalog/1.jpg`). Paths that leave the site (`..`, other schemes) are refused.

Only addresses on the internet are fetched. A host that resolves to a private, loopback, link-local or reserved address (the server itself, its network, a cloud's metadata) is refused, and every redirect, three at most, is checked the same way. A file of products cannot make the server knock on its own network. The check looks at whether an address is one but downloads nothing.

## The check and the report

The check runs every row through the same validation as the import, without writing: a product named in two rows of the file is counted as created once and updated once, as the import will do it, and sections that would be created are counted too. A row fails when the form would refuse it (with the form's own messages), when a value is not one of a field's options, when a section or a picture is not there. The first 500 rows with reasons are listed (`errors`), the rest are only counted.

A row that fails does not stop the others. A database error on a row is logged and the row fails. A crash of the import as a whole (the disk filled up) stops it with a reason; what was imported stays.

## Large files

The file is read a chunk of 100 rows at a time (`chunk`) from where the last step stopped, so a file of 100 000 rows needs no more memory than one of 100.

- **With a queue worker** (`QUEUE_CONNECTION` other than `sync`) a job moves the import for up to 20 seconds (`seconds`) and queues the next step itself.
- **Without one** the progress page moves it for 5 seconds (`request_seconds`) every second while it is open, and the scheduler does it every minute (`atrium:catalog:import --pending`, already scheduled by the module; `php artisan schedule:run` must be in cron, as Laravel asks).

Only one of them works on an import at a time.

## From the console and on a schedule

```bash
php artisan atrium:catalog:import storage/price.xlsx
php artisan atrium:catalog:import https://supplier.example/price.csv --key=external_id --mode=update
php artisan atrium:catalog:import price.csv --like=12 --check
```

- `source`: a file or a URL (downloaded for up to two minutes).
- `--like=12`: map the columns as import number 12 did (matched by column name) and take its options. Without it the columns are guessed.
- `--map="Цена опт=price"`: a column and the field it fills; repeat it for more. `--map="Comment="` leaves a column out. The column is split from the field at the last `=`.
- `--key=article|code|external_id`, `--mode=both|create|update`, `--create-sections`, `--separator=">"`.
- `--check`: only check and report.

It runs to the end and prints the report and every row that did not import. The exit code is 1 when a row failed or the import crashed, so a scheduled price list that went wrong is noticed:

```php
// routes/console.php
Schedule::command('atrium:catalog:import https://supplier.example/price.csv --like=12')
    ->dailyAt('06:00')
    ->emailOutputOnFailure('shop@example.com');
```

The imports from the console are in the admin's list too, with "console" as who ran them.

## Who may import

Importing is a permission of its own, **Products → Import** in the role form (`catalog_products.import`): an editor who may change products may not import a thousand of them unless the role says so. A superadmin may always. The import button is shown only to those who may.

## Settings

`php artisan vendor:publish --tag=atrium-catalog-config` copies `config/atrium-catalog.php`:

| Key | Default | |
|---|---|---|
| `import.directory` | `storage/app/private/atrium-catalog-imports` | where uploaded files wait, normalized to UTF-8 CSV; deleted when the import is done or stopped (`ATRIUM_CATALOG_IMPORT_DIRECTORY`) |
| `import.max_size` | `51200` | the largest upload, KB |
| `import.chunk` | `100` | rows read at a time |
| `import.seconds` | `20` | how long a queue job or a scheduled step works |
| `import.request_seconds` | `5` | how long the progress page's step works |
| `import.errors` | `500` | rows listed in the report |
| `import.image_timeout` | `10` | seconds to download a picture |
| `import.image_max_size` | `10240` | the largest picture, KB |
