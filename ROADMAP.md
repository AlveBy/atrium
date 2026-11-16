# Atrium roadmap

**English** · [Русский](ROADMAP.ru.md)

Atrium is an admin panel for Laravel that installs with one command, runs any number of sites and speaks 15 languages from day one. This page is where the project goes after the first release. There are no dates, only an order: modules → importers from other CMSs → shop → 1C and Bitrix24. Each version builds on the one before it: importers, the shop and integrations are modules themselves.

## Principles

- **Free and open.** The core and every official module are free, under MIT.
- **Everything beyond the core is a module.** The shop, the gallery, the WordPress importer, the 1C connector are separate packages. The core stays small; you install only what you use.
- **A module is installed, not wired in.** `composer require` plus one command: the module shows up in the menu, its tables exist, demo data is there. No code changes in your project.
- **Every module can do what the core does:** 15 interface languages, translatable content, multiple sites, PHPUnit and Dusk tests.
- **Moving in is a feature.** Each popular CMS gets an importer with a before/after report and a 301 redirect map, so the site keeps its search traffic.

## 0.1 — first release (in progress)

The admin core is ported and covered by tests. Still to do:

- interface languages (15), switchable per admin;
- ~~content languages~~ done: add a language in the admin, list a model's columns in `$translatable`, each language gets its own addresses (`/de/…`) and `hreflang`;
- ~~multiple sites~~ done: sites are added in the admin, picked by domain, each with its own languages; a module becomes per-site with one trait;
- analytics and verification codes from the admin, per site: Google Tag, Yandex Metrica, Search Console / Webmaster meta tags, any snippet in `<head>` or `<body>` — nobody has to touch the templates;
- `sitemap.xml` (every site, every language, with `hreflang`) and `robots.txt` built by the core;
- the 1C-Bitrix migrator (`alveby/atrium-bitrix`);
- a neutral public theme (`alveby/atrium-theme`), light and dark;
- demo data: `php artisan atrium:install --demo`;
- the README in 15 languages.

Docs for the release (Markdown in the repo, `docs/`):

- **Installation:** on your own server, on shared hosting with a control panel (cPanel, ISPmanager: no Docker, no Node on the server), in Docker.
- **Configuration:** config, mail, two-factor sign-in, API tokens, files.
- **Writing a module:** from `atrium:make-module` to a package on Packagist: fields, columns, tabs, permissions, translations, tests.
- **Moving from Bitrix:** step by step, checked with `atrium:bitrix:parity`.

## 0.2 — modules that just install

The base for everything after it: the shop, the importers and the integrations are modules themselves.

**Mechanics**
- A module is a Composer package with `"type": "atrium-module"` and a description in `extra.atrium`: what it registers (resources, menu, permissions, settings, migrations, demo seeder).
- `php artisan atrium:module:install {name}` / `atrium:module:remove {name}`: migrations, assets, permissions, menu entry; removal asks whether to keep the data.
- A "Modules" screen in the admin: installed modules (enable, disable, configure) and available ones, a catalogue read from Packagist by the `atrium-module` type, described in the admin's language, with the install command.
- A module template repository: structure, tests, CI, translations — press "Use this template" and start writing.

**What modules need from the core**
- Roles and permissions: who sees which module and what they may do (view, edit, delete), optionally limited to some sites.
- Module settings: a settings screen the module describes with fields, the same way it describes a record form.
- Events and hooks: "record saved", "form submitted", "order paid", so modules can talk without knowing about each other.
- Queues and a log: background jobs for modules, with retries and an error screen.

**Official modules**
| Module | What it does |
|---|---|
| Pages | Static pages with a tree and templates |
| News / Blog | Feed, categories, tags, RSS |
| Gallery | Photo and video albums on the site |
| Forms and leads | Form builder, submissions in the admin, e-mails, spam protection |
| Menus | Site menus by drag and drop, per site and language. An item can be dynamic: point it at a module (shop categories, news sections) and it unfolds into that module's tree on the site, always up to date, with a depth limit and a starting section |
| Banners and sliders | Placements on pages, display schedule |
| Reviews | With moderation and ratings |
| FAQ | With structured data for search engines |
| SEO | Redirects, meta tag templates, broken link report (sitemap.xml and robots.txt are in the core) |
| Search | Across all modules of a site, language-aware |
| Media library | A shared file library with folders (part of the core today) |

## 0.3 — moving from other CMSs

The Bitrix migrator becomes `alveby/atrium-import`: one pipeline, many sources.

**One pipeline for every source**
1. Analyse: what the old database holds — content types, fields, categories, languages, sites.
2. Modules: models and admin forms are generated from what was found.
3. Data: records, files, SEO, users.
4. Check: a before/after report per table and field; an empty report means nothing was lost.
5. URLs: a 301 redirect map from old addresses to new ones.
6. Catch-up sync: while the old site is still live, only changes are moved.

**Sources**
| CMS | What moves |
|---|---|
| 1C-Bitrix | already there: iblocks, sections, properties, form layouts, media library, SEO |
| WordPress | from a WXR export or straight from the database: posts, pages, categories, tags, custom post types, ACF, Yoast/Rank Math, WPML/Polylang languages |
| Drupal | 7 (end of life, so the most people want out) and 10/11: nodes, taxonomy, fields, path aliases |
| Joomla | articles, categories, menus |
| MODX | resources, template variables |
| Tilda | from the export: pages and images |

After WordPress the order follows requests in Issues.

## 0.4 — online shop

`alveby/atrium-shop` and its modules. Each installs on its own: if you only need a catalogue with enquiries, install only the catalogue.

**Core**
- Catalogue: products, categories, properties, filters, variants (size, colour), stock per warehouse.
- Prices: several price types, currencies with rates, taxes.
- Cart and checkout, customer account, order history.
- Orders in the admin: statuses, an e-mail to the customer on each status, printing.

**Payment and delivery** (each method is a module)
- Payment: Stripe, YooKassa, bePaid, CloudPayments; others on request or from the community.
- Delivery: pickup, courier, zones and rates; carriers as modules.

**Marketing**
| Module | What it does |
|---|---|
| Discounts | Rules per product, category, cart, order total, customer group |
| Coupons | Single and multi-use codes, limits, dates |
| Bonuses | Points per order, paying part of an order with points, loyalty tiers |
| Promotions | A promotion page with products, dates and a countdown |
| Gift cards | Selling and redeeming |
| Wishlist and compare | |
| Product reviews | Ratings, photos, only buyers can review |
| Abandoned carts | Reminder e-mails |
| Feeds | Yandex Market (YML), Google Merchant |

**Moving a shop** from Bitrix (its e-commerce module), WooCommerce and OpenCart: products, variants, prices, stock, customers, orders.

## 0.5 — 1C and Bitrix24

**1C**
- Exchange over the standard CommerceML 2 protocol, the same one Bitrix uses. Stock 1C configurations connect to Atrium without changes on the 1C side: only the site address in the exchange settings changes.
- From 1C: catalogue, properties, images, prices, stock. To 1C: orders and their statuses.
- An exchange screen in the admin: when it last ran, what came in, errors.

**Bitrix24**
- Form submissions and orders become leads or deals, customers become contacts.
- Deal stages come back to the site (order paid, shipped).
- The Bitrix24 open channels / live chat widget on the site.
- Connected with an inbound webhook: paste the URL and it works.
- "Form → lead" is simple and almost everyone needs it, so it may ship earlier, together with the Forms module in 0.2.

**Shared by all integrations:** a queue with retries, an exchange log, an alert to the admin on failure. Other connectors (amoCRM and the like) can be built on the same base on request.

## Project website and documentation

The address is **atrium.alve-soft.com**. Alongside 0.2, as soon as the Pages, Menus and Search modules exist.

- The site runs on Atrium itself: the best demo, and proof that it all works.
- 15 languages, like the admin.
- The docs are the same Markdown files from the repo's `docs/`; the site renders them with a Docs module, versioned per release. Fixing a typo is a pull request.
- A live admin demo: no sign-up, touch anything, the database resets every hour.
- A module catalogue: official and community modules, with languages, versions and the install command.
- "Order a module": describe what you need, and the Atrium team builds it as a module.
- Until the site is up, the docs live on GitHub.

## 1.0 — stable

- The core's public API is frozen: modules written for 1.x work on every 1.x.
- An upgrade guide from 0.x.
- A security audit.
- A support policy: which Laravel and PHP versions, how long each Atrium version lives.

## How packages reach Packagist

Development happens in one repository (a monorepo). On release each package is pushed automatically to its own read-only repository (`AlveBy/atrium-shop` and so on), which Packagist reads. Users install only what they need.

Ideas and votes are welcome in [Issues](https://github.com/AlveBy/atrium/issues).
