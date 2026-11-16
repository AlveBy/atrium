# Changelog

All notable changes are listed here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), versions follow [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Laravel 13 skeleton with PHPUnit 12, Laravel Dusk, Pint and Larastan.
- Package skeletons: `alveby/atrium`, `alveby/atrium-bitrix`, `alveby/atrium-theme`.
- CI on PHP 8.3 and 8.4 against SQLite, MySQL and PostgreSQL, plus a Dusk job.
- Admin panel (`alveby/atrium`): list a model in `config/atrium.php` → `resources` and it gets CRUD pages, routes (`admin.{key}.*`) and a menu entry, with no generated controllers or Vue pages.
- Prebuilt admin bundle in `packages/admin/dist`, published to `public/vendor/atrium`; the admin has its own Inertia root view and does not touch the app's frontend.
- Commands: `atrium:install`, `atrium:user`, `atrium:make-module`.
- Two-factor sign-in by e-mail code, API tokens with a self-describing `/api/v1`, file manager, dashboard gadgets.
- Package tests (Testbench) for modules, menu, sign-in and commands; Dusk tests for sign-in and a full create → search → edit → delete round trip.
- The admin panel speaks 15 languages: en, ru, uk, be, kk, uz, de, fr, es, pt_BR, it, pl, tr, zh_CN, ja. Each admin picks one in the header (it is kept on the account); guests get their browser's language. English and Russian are written by hand, the rest are machine translations awaiting native review.
- One set of lang files feeds PHP (`__('atrium::…')`) and Vue (`$t()`, `$tc()` with Laravel's plural rules); a site can override any line with `vendor:publish --tag=atrium-lang`. A test checks every language against English: keys, placeholders, plural forms.
- Menu labels in `config/atrium.php` may be translation keys.
- Content languages. A built-in "Languages" module (code, name, active, default, sort order) appears in every panel without configuration; a site starts with its `app.locale` as the only, default language.
- Translatable columns: `use Atrium\Admin\Traits\Translatable` and list them in `protected array $translatable` (JSON columns, spatie/laravel-translatable). The form gets a language switch with error counts per language; only the default language is required, the others fall back to it. Unique rules (such as a page code) apply within one language. The list shows the default language and searches all of them; the API takes either a plain value (default language) or an object keyed by language code, and documents it.
- `Route::localized(fn () => …)` serves public routes in the default language as is and in other active languages under `/{code}/…`; `ContentLanguages::route()` builds the right address and `<x-atrium::hreflang :urls="…" />` prints the alternates with `x-default`.
- `atrium:make-module` generates translatable models and JSON columns. The demo `Article` is translatable and has a public page at `/articles/{code}`.
- Slugs drop accents (`über` → `uber`) instead of turning them into dashes.
- [ROADMAP.md](ROADMAP.md): installable modules, importers from other CMSs, an online shop, 1C and Bitrix24, a multilingual project site.

### Fixed
- Saving an administrator ended in a server error (it redirected to a route that does not exist).
- The administrator edit page did not open.
