# Atrium roadmap

**English** · [Русский](ROADMAP.ru.md)

Atrium is an admin panel for Laravel that installs with one command, runs any number of sites and speaks 15 languages from day one. This page is where the project goes after the first release. There are no dates, only an order: modules → importers from other CMSs → shop → 1C and Bitrix24. Each version builds on the one before it: importers, the shop and integrations are modules themselves.

## Focus now

Until 0.1 is out, work goes only into the core and what every site needs (pages, forms and submissions, news, catalog, menus and search, SEO, custom fields, access rights, two-factor sign-in, the AI assistant, the error log and monitoring, cache, backups, admin themes and site skins), the tests, and importers from other CMSs, and every importer is tried on real sites before it is called done. Other modules wait: a module someone asks for in Issues comes first.

## Principles

- **Free and open.** The core and every official module are free, under MIT.
- **Everything beyond the core is a module.** The shop, the gallery, the WordPress importer, the 1C connector are separate packages. The core stays small; you install only what you use.
- **A module is installed, not wired in.** `composer require` plus one command: the module shows up in the menu, its tables exist, demo data is there. No code changes in your project.
- **Every module can do what the core does:** 15 interface languages, translatable content, multiple sites, PHPUnit and Dusk tests.
- **Moving in is a feature.** Each popular CMS gets an importer with a before/after report and a 301 redirect map, so the site keeps its search traffic.

## 0.1 — first release (in progress)

The admin core is ported and covered by tests. Still to do:

- ~~interface languages~~ done: 15 of them, each admin picks one in the header;
- ~~API keys~~ already there: an "API tokens" screen issues a key limited to chosen modules and actions, and `/api/v1` describes itself;
- ~~roles and permissions~~ done: a role says which modules an administrator may view, create, edit and delete, the panel's own sites, languages and administrators included; the menu, the buttons and the API follow it, and a token never has more rights than its owner today. Superadmins need no role and are the only ones who manage roles;
- ~~an MCP server on top of the same API and keys~~ done: an AI assistant (Claude, ChatGPT, Cursor) connects to `/mcp` with an API token and reads and edits the modules — "add three news items", "raise prices in this category by 5%" — with exactly the rights of its key;
- ~~content languages~~ done: add a language in the admin, list a model's columns in `$translatable`, each language gets its own addresses (`/de/…`) and `hreflang`;
- ~~multiple sites~~ done: sites are added in the admin, picked by domain or by a folder on it (`example.com/site2/`), each with its own languages; a module becomes per-site with one trait;
- ~~counters and verification codes~~ done: per site in the admin — Google Tag, Yandex Metrica, Search Console / Webmaster / Bing meta tags, any snippet in `<head>` or `<body>`; nobody has to touch the templates;
- ~~`sitemap.xml` and `robots.txt`~~ done: built by the core for every site, every language, with `hreflang`; off production robots.txt always says `Disallow: /`;
- ~~the 1C-Bitrix migrator~~ done (`alveby/atrium-bitrix`): models, migrations and admin forms are built from the Bitrix database (tabs, field order and list columns as the editors had them in Bitrix); data, files, SEO and the media library move over; `atrium:bitrix:parity` checks before against after, `atrium:bitrix:sync` catches up with edits while the old site is still live; tested on MySQL, PostgreSQL and SQLite. Static pages move with the Pages module in 0.2;
- ~~a neutral public theme~~ done (`alveby/atrium-theme`): draws every module and migrated iblock from its admin form, light and dark, with a language switcher; contrast is checked by a test;
- ~~own tabs for custom fields~~ done: the **Tabs** button of a module's list adds a tab to its form, a field picks it, the tab appears at its sort number and goes away with its last field, and a form with no tabs gets them with the first one;
- record history in every module, with no code of the module's own: ~~an activity log~~ done ([docs](docs/history.md)): who created, changed or deleted a record, when, from the panel, the API, an import or a module's button, the changed fields from what to what, never a password; a journal narrowed by module, administrator and days, and a **History** button on every form. Still to do: revisions with a diff and "restore this version", a trash with restore and permanent delete, and publishing and unpublishing on a schedule;
- ~~demo data~~ done: `php artisan atrium:install --demo` adds a small shop with every field type, news in three languages and a second site;
- ~~the README in 15 languages~~ done: English and Russian by hand, the rest machine-translated, with screenshots; a test keeps the commands and links of every translation in step with English.

Docs for the release (Markdown in the repo, `docs/`, in English and Russian), ~~all four~~ done:

- [**Installation:**](docs/installation.md) on your own server, on shared hosting with a control panel (cPanel, ISPmanager: no Docker, no Node on the server), in Docker.
- [**Configuration:**](docs/configuration.md) config, mail, two-factor sign-in, API tokens, files.
- [**Writing a module:**](docs/modules.md) from `atrium:make-module` to a package on Packagist: fields, columns, tabs, permissions, translations, tests.
- [**Moving from Bitrix:**](docs/bitrix.md) step by step, checked with `atrium:bitrix:parity`.

## 0.2 — modules that just install

The base for everything after it: the shop, the importers and the integrations are modules themselves.

**Mechanics**
- ~~A module is a Composer package with `"type": "atrium-module"` and a description in `extra.atrium`~~ done: resources, public pages, migrations, demo seeder ([docs](docs/modules.md#a-module-as-a-package)); settings come with module settings below.
- ~~`php artisan atrium:module:install {name}` / `atrium:module:remove {name}`~~ done: migrations, permissions, menu entry; removal asks whether to keep the data. Modules link to each other softly ([docs](docs/modules.md#links-to-other-modules)). Assets of a module's own come with the first module that needs them.
- A "Modules" screen in the admin: installed modules (enable, disable, configure) and available ones, a catalogue read from Packagist by the `atrium-module` type, described in the admin's language, with the install command.
- A module template repository: structure, tests, CI, translations — press "Use this template" and start writing.
- **Submodules.** A module can extend another one without touching its code: add fields and tabs to its form, columns to its list, its own screens inside the parent's menu section, its own settings on the parent's settings screen. A submodule installs only next to its parent and goes away with it. Payment methods, carriers and discounts are submodules of the shop; "Ratings" can be a submodule of "Reviews".

**What modules need from the core**
- Permissions for installed modules: a module declares its own actions beyond view, create, edit and delete ("publish", "export"), and they appear in the role form. Roles per site: the same administrator edits one site and only reads another (roles themselves are in 0.1).
- Module settings: a settings screen the module describes with fields, the same way it describes a record form.
- Hooks, as in WordPress but built on Laravel events, so modules and the site itself change behaviour without editing anyone's code:
  - **actions** — react to something: `Atrium::on('articles.saved', …)`, "form submitted", "order paid", "site switched";
  - **filters** — change something on its way: the fields and tabs of a form, the columns and query of a list, the data of a record before it is saved (mutators) and after it is read, an API response, the menu, the sitemap, the page `<head>`;
  - every hook point is listed in the docs, generated from the code, so the list never goes stale.
- ~~Embeds in the editor~~ done: an editor puts a form into any text with the **+** button of the visual editor, and a module registers more types (`Hook::register(Embeds::REGISTRY, …)`); the site swaps each placeholder for the current item, so a cached text is never stale. Reusable blocks are in `alveby/atrium-blocks`. See [docs/embeds.md](docs/embeds.md);
- Map fields that are maps: pick a point on Yandex Maps or Google Maps, find it by address, draw an area; keys are set in the admin. On the site, a `<x-atrium::map>` component shows one or many records (offices, shops, properties) on the chosen map.
- Queues and a log: background jobs for modules, with retries and an error screen.

**Official modules**
| Module | What it does |
|---|---|
| Pages | Static pages with a tree and templates; imports Bitrix static pages |
| News / Blog | Feed, categories, tags, ~~RSS~~ (done: `/news.xml` per site and language, see [docs/feeds.md](docs/feeds.md)) |
| Gallery | Photo and video albums on the site |
| Forms and leads | Form builder, submissions in the admin, e-mails, spam protection |
| Menus | Site menus by drag and drop, per site and language. An item can be dynamic: point it at a module (shop categories, news sections) and it unfolds into that module's tree on the site, always up to date, with a depth limit and a starting section |
| ~~Blocks~~ | done (`alveby/atrium-blocks`): a phone number, an address or a banner written once, in the editor with **+** and in templates with `@block('phone')`, per language and site, cached |
| Banners and sliders | Placements on pages, display schedule |
| Reviews | With moderation and ratings |
| Classifieds | A board of ads posted by visitors: categories, photos, moderation, the author's account, expiry; paid placement later as a submodule |
| Analytics | Beyond the counters of 0.1: visits and sources from Yandex Metrica and Google Analytics on the dashboard, goals sent by themselves when a form is submitted or an order is placed |
| FAQ | With structured data for search engines |
| SEO | Redirects, meta tag templates, broken link report (sitemap.xml and robots.txt are in the core) |
| Search | Across all modules of a site, language-aware |
| Media library | A shared file library with folders (part of the core today) |

## Ready-made sites

A ready-made site is a set of modules, theme settings and demo content installed with one command: `php artisan atrium:install --preset=realty`. Each appears as soon as the modules it needs are there.

| Site | What is in it |
|---|---|
| Real estate | Properties (sale, rent, new builds) with a filter by type, rooms, area, price and district, every property on a map, agents, viewing requests; export feeds for property portals |
| Catalogue site | Products or services with sections and a filter, no cart: "request a price" and "order" buttons go to Forms. The shop's catalogue (0.4) without the rest of the shop |
| Classifieds | The Classifieds module with a theme for it: rubrics, search, the author's account |
| Company site | Pages, news, services, team, contacts with a map, forms |

## 0.3 — moving from other CMSs

One pipeline, many sources. Each importer starts as a package of its own (`alveby/atrium-bitrix`, `alveby/atrium-wordpress`); what they really share moves to `alveby/atrium-import` once a third one shows it.

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
| ~~WordPress~~ | done (`alveby/atrium-wordpress`, [Moving from WordPress](docs/wordpress.md)): straight from the database — posts to News, pages to Pages, custom post types to generated modules, categories, tags, authors, attachments, ACF, Yoast/Rank Math, WPML/Polylang languages; `atrium:wordpress:parity` checks before against after, `atrium:wordpress:redirects` writes the 301 map, `atrium:wordpress:sync` catches up; tested on MySQL, PostgreSQL and SQLite. From a WXR export: later |
| Drupal | 7 (end of life, so the most people want out) and 10/11: nodes, taxonomy, fields, path aliases |
| Joomla | articles, categories, menus |
| MODX | resources, template variables |
| Tilda | from the export: pages and images |

After WordPress the order follows requests in Issues.

## 0.4 — online shop

`alveby/atrium-shop` and its submodules. Each installs on its own: if you only need a catalogue with enquiries, install only the catalogue. Everything below except the core is a submodule of the shop, so a third party can add a payment method or a discount rule the same way.

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

**Running the shop**
| Submodule | What it does |
|---|---|
| ~~Import and export~~ | Done: every module of the admin imports from and exports to CSV and Excel (columns guessed, a check before writing, a report by row, the console and a schedule; the catalog adds articles, sections and photos on top) — [docs/import-export.md](docs/import-export.md). Still to do: stock by warehouse |
| Notifications | New orders and status changes to Telegram and e-mail for the staff |
| Documents | Invoices, receipts and delivery notes for orders, from the Documents module (below) |
| E-commerce analytics | Views, cart and purchases sent to Yandex Metrica and Google Analytics as e-commerce events |

**Documents** (`alveby/atrium-documents`) installs without the shop too: people who sell services need invoices and contracts as well.
- Templates: built in the admin with the same visual editor, or uploaded as a ready DOCX with fields like `{{ client.name }}`. Data comes from any record — an order, a client, a lead, a deal. Output is PDF and DOCX.
- Contracts: from a template for a client, with their own numbering, statuses (draft, sent, signed), a term and a renewal reminder, amendments and annexes, a scan of the signed copy.
- Invoices: for an order or by hand — lines, taxes, currency, the amount in words in the document's language, a payment link through the payment modules; marked paid by itself when the money arrives.
- Counterparties: companies and sole traders with their details, each with their contracts and invoices.
- National forms come as per-country submodules so they stay out of everyone else's way: the Russian VAT invoice (счёт-фактура), UPD and acceptance act, the Belarusian act and waybill, EU VAT invoices. E-invoicing networks later, on request.

**Moving a shop** from Bitrix (its e-commerce module), WooCommerce and OpenCart: products, variants, prices, stock, customers, orders.

## 0.5 — 1C and Bitrix24

**1C**
- ~~Exchange over the standard CommerceML 2 protocol, the same one Bitrix uses. Stock 1C configurations connect to Atrium without changes on the 1C side: only the site address in the exchange settings changes.~~ done (`alveby/atrium-1c`, [docs](docs/1c.md)): login and password in the module's settings, files in chunks and in zip, the old `/bitrix/admin/1c_exchange.php` address too.
- ~~From 1C: catalogue, properties, images, prices, stock.~~ done: sections, products, features, pictures, prices and in stock / not in stock, matched by the 1C identifier so a repeat updates; a big file is imported in steps. To 1C: orders and their statuses: wait for the shop (1C is answered that there are no orders).
- ~~An exchange screen in the admin: when it last ran, what came in, errors.~~ done: **Modules → 1C exchange** and the exchange log.

**Bitrix24**
- Form submissions and orders become leads or deals, customers become contacts. ~~Form submissions → a lead, or a contact with a deal~~ done (`alveby/atrium-bitrix24`, [docs](docs/bitrix24.md)), with a per-form choice of the field mapping; orders and customers wait for the shop.
- Deal stages come back to the site (order paid, shipped): waits for the shop.
- ~~The Bitrix24 open channels / live chat widget on the site.~~ done: paste the widget code in the module's settings.
- ~~Connected with an inbound webhook: paste the URL and it works.~~ done: stored encrypted, with a **Check connection** button.

**Shared by all integrations:** ~~a queue with retries, an exchange log, an alert to the admin on failure~~ done (`alveby/atrium-integrations`, [docs](docs/integrations.md)): five attempts with growing pauses, a log screen with secrets hidden, one letter per integration per hour. Other connectors (amoCRM and the like) can be built on the same base on request.

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
