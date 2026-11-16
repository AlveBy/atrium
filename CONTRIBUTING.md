# Contributing to Atrium

Thanks for helping. Atrium is a monorepo: the demo app lives in the root, the packages in `packages/`.

| Package | Path | Packagist |
|---|---|---|
| Admin panel | `packages/admin` | `alveby/atrium` |
| 1C-Bitrix migrator | `packages/bitrix` | `alveby/atrium-bitrix` |
| Public theme | `packages/theme` | `alveby/atrium-theme` |

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm install && npm run build
```

With Docker instead of a local PHP: `docker compose up` installs everything, fills the demo shop and serves it on http://localhost:8000; run commands in it with `docker compose exec app php artisan …` or `docker compose exec app vendor/bin/phpunit`.

## Before every push

Run what GitHub Actions runs, on your machine. A push that has not passed it must not reach main:

```bash
vendor/bin/pint && php scripts/copyright-header.php --fix   # fix style and headers first
bin/ci --changed           # lint, PHPStan, PHPUnit of the packages your change can break
```

`bin/ci` runs Pint, the copyright header check, PHPStan and PHPUnit, each package in its own process, on SQLite and — when Docker is running or a server is there — on MySQL and PostgreSQL, in throwaway databases of its own. `--changed` limits PHPUnit to the packages you touched and the ones that require them; without it every package runs, which is what CI does. Add `--dusk` for the browser tests (it serves the app and ChromeDriver on free ports). `bin/ci --help` lists the rest; `composer ci` is the same.

- Tests are PHPUnit, not Pest. Every change that a user can see needs a Dusk test; everything else needs a PHPUnit test.
- `declare(strict_types=1);` in every PHP file.
- Every source file starts with the Atrium copyright header (`This file is part of Atrium …`). Pint writes it into PHP files, `php scripts/copyright-header.php --fix` into the rest, and `tests/Unit/CopyrightHeaderTest.php` fails without it. Translations get it too; built bundles (`dist`), JSON, XML, Markdown, SVG and the stubs that `atrium:make-module` copies into your application do not.
- Never commit data from a real site. Bitrix fixtures are synthetic.

## Translations

The admin panel speaks 15 languages. Its strings live in `packages/admin/lang/{locale}/*.php`, one file per screen, and the same files feed PHP (`__('atrium::fields.gallery.add')`) and Vue (`$t('fields.gallery.add')`). English is the source. Russian was written by hand; the other 13 languages started as machine translation and each file says so at the top — native speakers are very welcome to fix them. That is one of the best first contributions.

These are the panel's own interface languages. The languages a site's content is written in are something else: they are added by editors in the admin (Languages) and stored in the `admin_languages` table.

Rules that the tests check (`packages/admin/tests/Unit/TranslationsTest.php`):

- every language has exactly the keys of English;
- placeholders (`:name`, `:count`) are the same as in English;
- plural strings (`':count file|:count files'`) have as many forms as the language needs: 1 for Japanese, Chinese, Turkish, Kazakh and Uzbek, 3 for Russian, Ukrainian, Belarusian and Polish, 2 for the rest.

A new string goes into every language at once; if you can't translate it, put the English text there and mention it in the pull request.

To add a language: copy `packages/admin/lang/en` to the new code, translate it, add the code to `locales` in `packages/admin/config/atrium.php` and its plural count to `TranslationsTest::PLURAL_FORMS`. If Laravel's plural rule for it is not one of the above, add it to `resources/js/Support/plural.js` too — a test compares that file with Laravel.

The README is translated too: English is `README.md`, the others are `docs/readme/README.{locale}.md`. Fix a translation freely; a change to the English README goes into every language (`tests/Unit/ReadmeTest.php` fails while the commands, code or links differ).

Sites can override any line without forking: `php artisan vendor:publish --tag=atrium-lang` copies the files to `lang/vendor/atrium`.

## Commit messages

English, in the [Conventional Commits](https://www.conventionalcommits.org/) form: `feat(admin): …`, `fix(bitrix): …`, `docs: …`, `test: …`, `chore: …`. The scope is the package (`admin`, `bitrix`, `theme`) or left out. Describe what changed for the user, not which file you touched.

## Versions and releases

Atrium follows [Semantic Versioning](https://semver.org/). Until 1.0 a minor version (0.2 → 0.3) may break things and says so in the changelog; a patch (0.2.0 → 0.2.1) never does.

All packages are released together under one version. To release:

1. Move the `[Unreleased]` notes in `CHANGELOG.md` under `## [x.y.z] - YYYY-MM-DD` and add the compare link at the bottom.
2. Commit (`chore: release x.y.z`), tag `vx.y.z`, push the tag.
3. The `release` workflow publishes the GitHub release with those notes; Packagist picks up the tag.
