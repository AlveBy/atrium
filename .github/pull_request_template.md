## What and why

<!-- What changes for a user of Atrium, and why. Link the issue: Fixes #… -->

## How it is tested

- [ ] PHPUnit (`php artisan test`)
- [ ] Dusk, if anything changes on screen (`php artisan dusk`)
- [ ] `vendor/bin/pint` and `vendor/bin/phpstan analyse` are clean
- [ ] New interface text is in `lang/en` and `lang/ru`; the other languages are filled in (machine translation is fine)
- [ ] A note for the release is in `changelog/{section}/{name}.md`, not in `CHANGELOG.md` (see `changelog/README.md`)
