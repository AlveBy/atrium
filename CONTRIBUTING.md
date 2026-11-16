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

## Before you open a pull request

```bash
vendor/bin/pint            # code style
vendor/bin/phpstan analyse # static analysis
php artisan test           # PHPUnit: app + all packages
php artisan serve &        # then, in another terminal:
php artisan dusk           # browser tests
```

- Tests are PHPUnit, not Pest. Every change that a user can see needs a Dusk test; everything else needs a PHPUnit test.
- `declare(strict_types=1);` in every PHP file.
- Never commit data from a real site. Bitrix fixtures are synthetic.

## Translations

Atrium ships in 15 languages. Interface strings live in `lang/{locale}/`. Only English and Russian were written by hand; the rest started as machine translation and need native speakers. Fixing a translation is one of the best first contributions. A test checks that every locale has every key, so a new string must be added to all of them.

To add a new language, copy `lang/en`, translate it, add the locale to the list in `config/atrium.php` and add a `README.{locale}.md`.

## Commit messages

Describe what changed for the user, not which file you touched.
