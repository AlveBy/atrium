# Atrium

**English** · [Русский](docs/readme/README.ru.md) · [Українська](docs/readme/README.uk.md) · [Беларуская](docs/readme/README.be.md) · [Қазақша](docs/readme/README.kk.md) · [Oʻzbekcha](docs/readme/README.uz.md) · [Deutsch](docs/readme/README.de.md) · [Français](docs/readme/README.fr.md) · [Español](docs/readme/README.es.md) · [Português (Brasil)](docs/readme/README.pt-BR.md) · [Italiano](docs/readme/README.it.md) · [Polski](docs/readme/README.pl.md) · [Türkçe](docs/readme/README.tr.md) · [简体中文](docs/readme/README.zh-CN.md) · [日本語](docs/readme/README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

One admin panel for any number of multilingual sites, built on Laravel 13, with a migrator that moves an existing 1C-Bitrix site over.

You describe a module once, as a model with its fields and list columns. Atrium gives it a list with search and sorting, a form with tabs, a menu entry, an API and, with the theme, public pages.

![The products list of the demo shop](docs/images/admin-list.png)

> **Before the first release.** Atrium works and is covered by tests, but version 0.1 is not out yet and the packages are not on Packagist. Try it from this repository.

## What it does

- **Multi-site.** One admin and one database for many sites, each picked by its domain or by a folder on it (`example.com/site2/`) and with its own languages.
- **Multilingual.** The admin speaks 15 languages, and each administrator picks one. Content languages are added in the admin, any column of a module can be translatable, and each language gets its own addresses (`/de/…`) and `hreflang`.
- **20 field types.** Text, numbers, dates, lists, links to other modules, images, galleries, files, a visual editor, maps, repeatable values. Forms have tabs, as in 1C-Bitrix.
- **Roles and permissions.** Who sees which module and what they may do in it: view, create, edit, delete.
- **SEO without code.** Titles and descriptions for every record, readable addresses with redirects from old ones, `sitemap.xml` and `robots.txt` for each site. Google Tag, Yandex Metrica and verification codes are set per site in the admin.
- **API.** Tokens limited to chosen modules and actions; `/api/v1` describes itself. AI assistants (Claude, ChatGPT, Cursor) connect to `/mcp` with the same token and exactly its rights.
- **Sign-in code by email.** Two-factor sign-in is on by default.
- **1C-Bitrix migrator.** Infoblocks, properties, sections, forms, list layouts, SEO and the media library become Laravel models, migrations and data, and a report compares the old site with the new one.
- **Public theme.** A neutral site that draws every module from its admin form, light and dark, readable with any kind of colour vision.
- **Tested.** PHPUnit on SQLite, MySQL and PostgreSQL, browser tests with Laravel Dusk.

| The form of a record | The site, light and dark |
|---|---|
| ![A product form with tabs](docs/images/admin-form.png) | ![A product page](docs/images/site-light.png) ![A news item in German, dark theme](docs/images/site-dark.png) |

## Try it

With Docker, nothing else is needed:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

The first start takes a few minutes. Then open http://localhost:8000/admin and sign in as `admin@example.com` with the password `password` (for local trials only). The site is at http://localhost:8000.

Without Docker you need PHP 8.3 or newer and Composer. The database is SQLite by default, and the admin comes prebuilt, so neither MySQL nor Node is needed:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

The installer asks for the first administrator's name, email and password. The sign-in code is sent by email; on a local machine without mail it is at the end of `storage/logs/laravel.log`, or turn it off with `ATRIUM_2FA_ENABLED=false` in `.env`.

`--demo` adds a small shop: products with every field type, brands, stores, news in three languages and a second site. To start over, delete `database/database.sqlite` and install again.

## Your first module

```bash
php artisan atrium:make-module Article
php artisan migrate
```

This writes a model, a migration and a line in `config/atrium.php`, and "Articles" appears in the menu. The model describes its form and list:

```php
class Article extends Model implements Administrable
{
    use InteractsWithAdmin, Translatable;

    protected array $translatable = ['name', 'code', 'preview_text', 'detail_text'];

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            SlugField::make('code')->label('Code')->from('name')->unique('articles', 'code'),
            CheckboxField::make('active')->label('Active')->default(true),
            ContentField::make('detail_text')->label('Detail text'),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('name')->label('Name')->sortable()->searchable(),
            BooleanColumn::make('active')->label('Active'),
        ];
    }
}
```

To give the module pages on the site, add it to `public_urls` in `config/atrium.php`:

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## Moving from 1C-Bitrix

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

The Bitrix database is only read. The tabs, field order and list columns come out as the editors had them in Bitrix. `atrium:bitrix:sync` catches up with edits made on the old site while it is still live. Step by step: [packages/bitrix/README.md](packages/bitrix/README.md).

## Documentation

- [Installation: your own server, hosting with ISPmanager or cPanel, Docker](docs/installation.md)
- [Configuration: mail and the sign-in code, files, API tokens, AI assistants over MCP](docs/configuration.md)
- [AI writing help: free and paid providers, descriptions, SEO, alt texts, translations](docs/ai.md)
- [Your own module: fields, columns, tabs, permissions, languages, tests, a package](docs/modules.md)
- [Moving from 1C-Bitrix, step by step](docs/bitrix.md)
- [Backups: databases and files to several places, schedules, health checks, restore](docs/backup.md)
- [CDN: pictures, resized copies and WebP/AVIF, cache purge, uploads to S3](docs/cdn.md)

## Packages

| Package | What it is |
|---|---|
| [`alveby/atrium`](packages/admin) | The admin panel |
| [`alveby/atrium-theme`](packages/theme/README.md) | The public theme |
| [`alveby/atrium-bitrix`](packages/bitrix/README.md) | The 1C-Bitrix migrator |
| [`alveby/atrium-ai`](packages/ai/README.md) | AI writing help in the admin |
| [`alveby/atrium-docs`](packages/docs/README.md) | Documentation pages from Markdown, with versions and search |
| [`alveby/atrium-backup`](packages/backup/README.md) | Backups of the databases and files, restore from the admin |
| [`alveby/atrium-news`](packages/news/README.md) | News |
| [`alveby/atrium-pages`](packages/pages/README.md) | Static pages, with an import of 1C-Bitrix pages |
| [`alveby/atrium-catalog`](packages/catalog/README.md) | Product catalog with nested sections |
| [`alveby/atrium-brands`](packages/brands/README.md) | Brands with logos |
| [`alveby/atrium-stores`](packages/stores/README.md) | Stores and offices on a map |
| [`alveby/atrium-team`](packages/team/README.md) | People, departments and contacts |
| [`alveby/atrium-faq`](packages/faq/README.md) | Questions and answers |
| [`alveby/atrium-forms`](packages/forms/README.md) | Form builder and submissions |
| [`alveby/atrium-reviews`](packages/reviews/README.md) | Reviews and ratings with moderation |
| [`alveby/atrium-solutions`](packages/solutions/README.md) | Ready-made sites installed in one command |

Once 0.1 is on Packagist, an existing Laravel 13 app gets Atrium with:

```bash
composer require alveby/atrium
php artisan atrium:install
```

## Requirements

- PHP 8.3 or newer
- Laravel 13
- SQLite, MySQL 8 or PostgreSQL

## Languages

The admin panel speaks English, Russian, Ukrainian, Belarusian, Kazakh, Uzbek, German, French, Spanish, Portuguese (Brazil), Italian, Polish, Turkish, Chinese (Simplified) and Japanese. English and Russian were written by hand; the others started as machine translation, and this README too. Corrections from native speakers are one of the best first contributions: see [CONTRIBUTING.md](CONTRIBUTING.md).

## How it started

Atrium began in December 2025 at [AlveSoft](https://alve.by) as our own admin panel to replace 1C-Bitrix on a client's site, [arkaim.by](https://arkaim.by). We wanted an admin that editors find as familiar as Bitrix, built on plain Laravel, and a migrator that carries the content over without losing anything. Since the summer of 2026 the panel has run that site in production: editors work in it every day, and the migrator moved the whole catalog, pages and forms across.

In November 2026 we decided to give it to everyone. We took out everything that belonged to that one site and published the rest under the MIT license, so that anyone can use it, change it and build their sites on it for free.

## Next

The [roadmap](ROADMAP.md): modules that install with one command, ready-made sites, importers from WordPress and Drupal, an online shop, 1C and Bitrix24.

## Contributing

[CONTRIBUTING.md](CONTRIBUTING.md) explains how to set up the project, run the tests and translate. Report security issues as described in [SECURITY.md](SECURITY.md).

## License

MIT, see [LICENSE](LICENSE).

If Atrium helped you, please keep the "Made with Atrium" link in the footer of your site. This is a request, not a condition of the licence: MIT only requires that the copyright notice stays in copies of the code.
