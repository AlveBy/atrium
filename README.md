# Atrium

**English** · [Русский](docs/readme/README.ru.md) · [Українська](docs/readme/README.uk.md) · [Беларуская](docs/readme/README.be.md) · [Қазақша](docs/readme/README.kk.md) · [Oʻzbekcha](docs/readme/README.uz.md) · [Deutsch](docs/readme/README.de.md) · [Français](docs/readme/README.fr.md) · [Español](docs/readme/README.es.md) · [Português (Brasil)](docs/readme/README.pt-BR.md) · [Italiano](docs/readme/README.it.md) · [Polski](docs/readme/README.pl.md) · [Türkçe](docs/readme/README.tr.md) · [简体中文](docs/readme/README.zh-CN.md) · [日本語](docs/readme/README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml) [![coverage 100%](https://img.shields.io/badge/coverage-100%25-brightgreen)](docs/testing.md)

One admin panel for any number of multilingual sites, built on Laravel 13, with a migrator that moves an existing 1C-Bitrix site over.

You describe a module once, as a model with its fields and list columns. Atrium gives it a list with search and sorting, a form with tabs, a menu entry, an API and, with the theme, public pages.

![The products list of the demo shop](docs/images/admin-list.webp)

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

| The form of a record | Search in the admin |
|---|---|
| ![The form of a record](docs/images/admin-form.webp) | ![Search in the admin](docs/images/admin-search.webp) |

| Menus | Dashboard |
|---|---|
| ![Menus](docs/images/admin-menus.webp) | ![Dashboard](docs/images/admin-dashboard.webp) |

| The site, light theme | The site, dark theme |
|---|---|
| ![The site, light theme](docs/images/site-light.webp) | ![The site, dark theme](docs/images/site-dark.webp) |

## Quality

- More than 6,500 PHPUnit tests and more than 80 browser tests on Laravel Dusk run on every pull request.
- On SQLite, MySQL and PostgreSQL, under PHP 8.3 and 8.4.
- Every package has 100% of its lines covered, and CI refuses a change that leaves a line without a test.
- Changed code needs a mutation score of 100%: Infection alters each changed line, and a test has to fail for every alteration.
- Nothing is excluded from these gates: no ignore annotations, no skipped mutators.
- Every push is checked for dependencies with known vulnerabilities and for secrets in the git history.

[How the tests and the gates work](docs/testing.md).

## Try it

With Docker, nothing else is needed:

```bash
git clone -b stable https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

The first start takes a few minutes. Then open http://localhost:8000/admin and sign in as `admin@example.com` with the password `password` (for local trials only). The site is at http://localhost:8000.

Without Docker you need PHP 8.3 or newer and Composer. The database is SQLite by default, and the admin comes prebuilt, so neither MySQL nor Node is needed:

```bash
git clone -b stable https://github.com/AlveBy/atrium.git
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
            TextField::make('name')->label(__('atrium::fields.standard.name'))->required(),
            SlugField::make('code')->label(__('atrium::fields.standard.code'))->from('name')->unique('articles', 'code'),
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            ContentField::make('detail_text')->label(__('atrium::fields.standard.detail_text')),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('name')->label(__('atrium::fields.standard.name'))->sortable()->searchable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
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
- [A public demo: Docker, a reset every hour, what a visitor may do, where to host it](docs/demo.md)
- [Configuration: mail and the sign-in code, files, API tokens, AI assistants over MCP](docs/configuration.md)
- [AI writing help: free and paid providers, descriptions, SEO, alt texts, translations](docs/ai.md)
- [SEO: titles, hreflang, sitemaps, schema.org, redirects, the SEO check](docs/seo.md)
- [Menus: header and footer, a tree of links, items that unfold a module](docs/menus.md)
- [Forms: a builder in columns, without reloading the page, in a window, your own markup and fetch](docs/forms.md)
- [Embeds: a form or a block inside a text, and how a module registers its own type](docs/embeds.md)
- [Blocks: a phone number or a banner written once and shown in templates and texts](docs/blocks.md)
- [Search: the results page, the admin box, what is searched and how](docs/search.md)
- [Your own module: fields, columns, tabs, permissions, languages, tests, a package](docs/modules.md)
- [Extensions: the platform design — the no-code builder, formulas, plugins, outside apps, the plan](docs/extensions.md)
- [Custom fields: add fields to any module from the admin, filters and facets on 100 000 records](docs/custom-fields.md)
- [Import and export: CSV and Excel for any module, columns, a check before writing, the console and a schedule](docs/import-export.md)
- [Record history: the activity log of every module, who changed what and when, without passwords](docs/history.md)
- [Cache: fast pages with dozens of properties, Redis, the page cache, CDN headers](docs/cache.md)
- [Moving from 1C-Bitrix, step by step](docs/bitrix.md)
- [Moving from WordPress: connect the database, move, check, the 301 map](docs/wordpress.md)
- [Backups: databases and files to several places, schedules, health checks, restore](docs/backup.md)
- [Errors: PHP, queue and JavaScript errors in the site's database, alerts, Sentry and your own drivers](docs/errors.md)
- [Speed and uptime: server timings, visitors' Web Vitals, uptime checks with alerts](docs/monitoring.md)
- [Integrations: the base of the 1C and Bitrix24 connectors, a queue with retries, an exchange log, alerts to administrators](docs/integrations.md)
- [Bitrix24: form submissions become leads or deals, the chat widget on the site, one pasted webhook](docs/bitrix24.md)
- [1C: the catalogue from 1C:Enterprise over the standard CommerceML exchange, an exchange screen](docs/1c.md)
- [CDN: pictures, resized copies and WebP/AVIF, cache purge, uploads to S3](docs/cdn.md)
- [Two-factor sign-in: authenticator apps, passkeys, codes, the sign-in log](docs/two-factor.md)
- [Social sign-in: providers, app registration, redirect URLs](docs/social-login.md)

## Packages

| Package | What it is |
|---|---|
| [`alveby/atrium`](packages/admin) | The admin panel |
| [`alveby/atrium-theme`](packages/theme/README.md) | The public theme |
| [`alveby/atrium-bitrix`](packages/bitrix/README.md) | The 1C-Bitrix migrator |
| [`alveby/atrium-ai`](packages/ai/README.md) | AI writing help in the admin |
| [`alveby/atrium-custom-fields`](packages/custom-fields/README.md) | Fields added to any module from the admin, with filters and facets |
| [`alveby/atrium-seo`](packages/seo/README.md) | Meta templates, hreflang, sitemaps, schema.org, redirects and an SEO check |
| [`alveby/atrium-docs`](packages/docs/README.md) | Documentation pages from Markdown, with versions and search |
| [`alveby/atrium-backup`](packages/backup/README.md) | Backups of the databases and files, restore from the admin |
| [`alveby/atrium-errors`](packages/errors/README.md) | Error tracking in the site's database, alerts, Sentry |
| [`alveby/atrium-monitoring`](packages/monitoring/README.md) | Server and visitor speed, uptime and alerts, with no outside service |
| [`alveby/atrium-integrations`](packages/integrations/README.md) | The base of the connectors: a queue with retries, an exchange log, an alert on failure |
| [`alveby/atrium-bitrix24`](packages/bitrix24/README.md) | Form submissions to Bitrix24 as leads or deals, the chat widget |
| [`alveby/atrium-1c`](packages/onec/README.md) | Products, prices and stock from 1C over CommerceML |
| [`alveby/atrium-news`](packages/news/README.md) | News |
| [`alveby/atrium-pages`](packages/pages/README.md) | Static pages, with an import of 1C-Bitrix pages |
| [`alveby/atrium-catalog`](packages/catalog/README.md) | Product catalog with nested sections |
| [`alveby/atrium-brands`](packages/brands/README.md) | Brands with logos |
| [`alveby/atrium-stores`](packages/stores/README.md) | Stores and offices on a map |
| [`alveby/atrium-team`](packages/team/README.md) | People, departments and contacts |
| [`alveby/atrium-faq`](packages/faq/README.md) | Questions and answers |
| [`alveby/atrium-tracker`](packages/tracker/README.md) | Tasks and projects for the people who work in the admin |
| [`alveby/atrium-forms`](packages/forms/README.md) | Form builder and submissions |
| [`alveby/atrium-blocks`](packages/blocks/README.md) | Reusable blocks: a phone number or a banner written once, shown anywhere |
| [`alveby/atrium-reviews`](packages/reviews/README.md) | Reviews and ratings with moderation |
| [`alveby/atrium-2fa`](packages/two-factor/README.md) | Two-factor and passwordless sign-in |
| [`alveby/atrium-social-login`](packages/social-login/README.md) | Sign-in with Google, Microsoft, Apple, GitHub, Telegram and others |
| [`alveby/atrium-solutions`](packages/solutions/README.md) | Ready-made sites installed in one command |
| [`alveby/atrium-menus`](packages/menus/README.md) | Menus built in the admin |
| [`alveby/atrium-search`](packages/search/README.md) | Site search across every module |
| [`alveby/atrium-wordpress`](packages/wordpress/README.md) | The WordPress importer: posts, pages, custom post types, SEO and translations, with a check and a 301 map |

The packages are on Packagist:

```bash
composer require alveby/atrium:^0.2
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
