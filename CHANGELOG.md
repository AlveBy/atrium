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
