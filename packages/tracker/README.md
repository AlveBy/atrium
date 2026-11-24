# Atrium Tracker

Tasks for [Atrium](https://github.com/AlveBy/atrium): projects, tasks with comments, attachments and a history, mail on assignment and on a change of status. It is for the people who work in the admin panel: administrators with Atrium roles and permissions. Visitors of the site have no part in it.

## Install

```bash
composer require alveby/atrium-tracker
php artisan atrium:module:install alveby/atrium-tracker
```

Add `--demo` to fill it with two projects and ten tasks.

`atrium:module:install` creates the tables, the permissions and the menu entry; `atrium:module:remove alveby/atrium-tracker` turns it off and asks whether to keep the data.

The full guide, in [English](../../docs/tracker.md) and [Russian](../../docs/tracker.ru.md): projects, tasks, statuses, mail, settings and hooks.

## Tests

From the repository root:

```bash
vendor/bin/phpunit packages/tracker/tests
```

The tests cover the forms, the rules, the history, the mail, the settings and the demo, on SQLite, MySQL and PostgreSQL.

## Contributing

This repository is a read-only copy of `packages/tracker` in [AlveBy/atrium](https://github.com/AlveBy/atrium). Issues and pull requests go there.

## License

MIT
