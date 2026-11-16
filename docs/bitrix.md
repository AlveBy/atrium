# Moving from 1C-Bitrix

[English](bitrix.md) · [Русский](bitrix.ru.md)

`alveby/atrium-bitrix` moves the content of a Bitrix site to Atrium: iblocks become modules with the same tabs, field order and list columns the editors had, and the data, files, SEO and media library come along. Then it proves that nothing was lost.

The Bitrix database is only read. The old site keeps working until you switch the domain.

- [What you need](#what-you-need)
- [1. Connect the Bitrix database](#1-connect-the-bitrix-database)
- [2. Bring the files](#2-bring-the-files)
- [3. Move](#3-move)
- [4. Check](#4-check)
- [5. Catch up and switch](#5-catch-up-and-switch)
- [What does not move yet](#what-does-not-move-yet)

## What you need

- An installed Atrium ([Installing](installation.md)), best on your own computer first: the move is repeatable, and the live site comes later.
- A copy of the Bitrix database (a dump from the hosting panel or `mysqldump`), imported into a MySQL your Atrium can reach. Bitrix does not need to run.
- The Bitrix `/upload` folder, or FTP access to it.

```bash
composer require alveby/atrium-bitrix --dev
php artisan vendor:publish --tag=atrium-bitrix-config
```

## 1. Connect the Bitrix database

Add a connection named `bitrix` to `config/database.php`, next to the others:

```php
'bitrix' => [
    'driver' => 'mysql',
    'host' => env('BITRIX_DB_HOST', '127.0.0.1'),
    'port' => env('BITRIX_DB_PORT', '3306'),
    'database' => env('BITRIX_DB_DATABASE', 'bitrix'),
    'username' => env('BITRIX_DB_USERNAME', 'root'),
    'password' => env('BITRIX_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

and the values to `.env`. Check what is there:

```bash
php artisan atrium:bitrix:analyze
```

It lists the iblock types, iblocks, their properties and record counts. If iblock codes carry the theme's prefix (`mytheme_news`), list it in `strip_prefixes` in `config/atrium-bitrix.php`, so the table becomes `news` and the model `News`.

## 2. Bring the files

Copy Bitrix's `upload` folder to `public/upload` of the new site, or fetch it over FTP (set `BITRIX_FTP_HOST`, `BITRIX_FTP_USER`, `BITRIX_FTP_PASSWORD` and `BITRIX_FTP_ROOT` first):

```bash
php artisan atrium:bitrix:download:upload
```

## 3. Move

```bash
php artisan atrium:bitrix:migrate
```

runs the whole sequence: the sites, migrations from the iblocks, `migrate`, models with their admin forms, the data, SEO and the media library. Each step can also run on its own, in this order:

```bash
php artisan atrium:bitrix:migrate:sites
php artisan atrium:bitrix:generate:migrations
php artisan migrate
php artisan atrium:bitrix:generate:models
php artisan atrium:bitrix:migrate:data
php artisan atrium:bitrix:migrate:seo
php artisan atrium:bitrix:migrate:medialibrary
```

Every command asks which iblocks to take; with `--no-interaction` it takes all of them.

`migrate:sites` moves the sites (`b_lang`) with their domains. A site's `DIR` becomes its folder: `/site2/` opens the site at `example.com/site2/` as before. Atrium keeps the default site at the root; if the Bitrix default site lives in a folder (`/ru/`), the command warns, and its old addresses need a redirect.

The models land in `app/Models`. List the ones the editors need in `config/atrium.php` → `resources`, and they are in the admin's menu:

```php
'resources' => [
    App\Models\News::class,
    App\Models\Catalog::class,
    App\Models\CatalogSection::class,
],
```

Generated models are meant to be regenerated, not edited: what you change on purpose goes to `config/atrium-bitrix.php` (`extra_fields`, `hidden_list_columns`, `extra_list_columns`), and after a regeneration look at `git diff app/Models`.

## 4. Check

```bash
php artisan atrium:bitrix:parity
php artisan atrium:bitrix:verify
```

`parity` compares every iblock and property with the models and the copied rows: counts, columns, field types, required flags, list options, form tabs and order, list columns, and every site's folder, domains and active flag. An empty report means nothing was lost; `--format=markdown` writes it to a file for the client. `verify` compares counts and spot-checks a few records of every iblock (`--sample=5`).

That checks the data. Whether every page of the old site opens on the new one is checked against the live old site: [MIGRATION-QA.md](../packages/bitrix/MIGRATION-QA.md) is the checklist.

## 5. Catch up and switch

Editors keep working in Bitrix while you build the new site. Before switching, bring their edits over with a fresh dump:

```bash
php artisan atrium:bitrix:sync --audit
php artisan atrium:bitrix:sync
```

`--audit` shows what differs and writes nothing; without it, only new and changed elements are copied, and `--prune` removes what Bitrix deleted. Columns listed in `sync_protected` keep the new site's value.

The migrator is a development dependency, so the last `sync` runs where you did the move. Then carry that database over to the live site ([Installing](installation.md)) and point the domain at it.

## What does not move yet

- **Static pages**: `.php` files outside iblocks (contacts, about). They move with the "Pages" module in version 0.2; until then carry them over by hand. The checklist helps find them.
- **Templates and components**: the new site is drawn by the public theme (`alveby/atrium-theme`) from the modules' forms, or by your own Blade templates.
- **Users, orders and the shop**: the migrator moves content. The shop and 1C exchange are on the [roadmap](../ROADMAP.md).
