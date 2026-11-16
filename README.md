# Atrium

One admin panel for any number of multilingual sites, built on Laravel 13 — with a migrator that moves an existing 1C-Bitrix site over.

> Work in progress. The full README in 15 languages arrives with the first release.

- **Multi-site** — one admin, one database, sites resolved by domain.
- **Multilingual** — 15 interface languages; content languages are added from the admin, any column of a module can be translatable.
- **SEO and counters without code** — Google Tag, Yandex Metrica and verification tags per site from the admin; `sitemap.xml` and `robots.txt` built by the core.
- **1C-Bitrix migrator** — infoblocks, properties, sections, forms, list layouts, SEO and media become Laravel models, migrations and data.
- **Public theme** — a neutral site that renders every module out of the box.
- **Tested** — PHPUnit and Laravel Dusk.

What comes next — modules you just install, importers from WordPress and Drupal, an online shop, 1C and Bitrix24 — is in the [roadmap](ROADMAP.md).

## License

MIT — see [LICENSE](LICENSE).
