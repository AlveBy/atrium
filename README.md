# Atrium

One admin panel for any number of multilingual sites, built on Laravel 13 — with a migrator that moves an existing 1C-Bitrix site over.

> Work in progress. The full README in 15 languages arrives with the first release.

- **Multi-site** — one admin, one database, sites resolved by domain.
- **Multilingual** — 15 interface languages; content languages are added from the admin, any column of a module can be translatable.
- **Roles and permissions** — who sees which module and may create, edit or delete in it; the API keys follow the same rights.
- **SEO and counters without code** — Google Tag, Yandex Metrica and verification tags per site from the admin; `sitemap.xml` and `robots.txt` built by the core.
- **1C-Bitrix migrator** — infoblocks, properties, sections, forms, list layouts, SEO and media become Laravel models, migrations and data.
- **Public theme** — a neutral site that renders every module out of the box.
- **Tested** — PHPUnit and Laravel Dusk.

To see it with content, install with the demo shop: a catalog with every field type, news in three languages and a second site.

```bash
php artisan atrium:install --demo
```

Or try it with nothing but Docker:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

Then open http://localhost:8000/admin and sign in as `admin@example.com` with the password `password` (for local trials only). After a `git pull`, run `docker compose up` again: it applies new migrations and the admin bundle. To start over, delete `database/database.sqlite`.

What comes next — modules you just install, importers from WordPress and Drupal, an online shop, 1C and Bitrix24 — is in the [roadmap](ROADMAP.md).

## License

MIT — see [LICENSE](LICENSE).
