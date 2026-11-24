# Extensions: the platform

[English](extensions.md) · [Русский](extensions.ru.md)

This is the design of Atrium as a platform. There is a small core, and on top of it a builder and plugins. From them anyone can put together the system they need (a site, a CRM, a tracker, a warehouse, finance) without a programmer. The design starts with the principle everything else follows. It then says what is in the code today and what is missing, and ends with the order of the pull requests that build it.

It is a design, not a guide. What is built is marked **built** with its pull request. The guides ([modules](modules.md), [custom fields](custom-fields.md), [integrations](integrations.md)) stay the reference for what works today.

- [The first principle: a builder of plugins on a small core](#the-first-principle-a-builder-of-plugins-on-a-small-core)
- [Principles](#principles)
- [The core and the plugins](#the-core-and-the-plugins)
- [What there is](#what-there-is)
- [The shape of the platform](#the-shape-of-the-platform)
- [The core contracts](#the-core-contracts)
- [Level 1. Data: the builder and templates](#level-1-data-the-builder-and-templates)
- [Level 2. One language: formulas, rules and plugins](#level-2-one-language-formulas-rules-and-plugins)
- [Level 3. Outside apps in any language](#level-3-outside-apps-in-any-language)
- [Level 4. Plugins in PHP from verified authors](#level-4-plugins-in-php-from-verified-authors)
- [Reports, charts and documents](#reports-charts-and-documents)
- [Towards an ERP, step by step](#towards-an-erp-step-by-step)
- [The site builder](#the-site-builder)
- [Importers make entities](#importers-make-entities)
- [Solutions](#solutions)
- [Extensions: install, trust, remove](#extensions-install-trust-remove)
- [Security](#security)
- [Stages and pull requests](#stages-and-pull-requests)

## The first principle: a builder of plugins on a small core

Atrium is not "everything in one box". It is a builder: install one solution and you get that solution, not a combine of everything. Three words say what it must be.

**A builder of plugins on a small core.** The core is:

- users and roles, permissions, settings;
- the builder and the formula language;
- the extension catalogue, the event catalogue and the points of extension;
- the API.

Everything else is a plugin you can install and remove. That includes the site and the site builder, the CRM, the shop, the tracker, the parts of an ERP and the importers. A built-in module goes through the same points as anyone else's.

A plugin declares what it needs: other plugins and the core version. That is checked before it is installed or updated. Removing a plugin cleanly takes away what it brought: its tables, permissions, menu items, schedules and settings. Its data can be exported first.

**Free.** There are no paid editions, activation keys or licences, no price per user, and no limits on records, users or runs. Publishing to the extension catalogue and installing from it are free. The list of what Atrium will never charge for is published.

**Secure.**

- **A manifest of permissions.** A plugin says up front what it wants to reach: tables, events, routes, outside addresses, files and the schedule. The person installing it sees that list and confirms it.
- **Signatures and checksums.** Packages are signed, and every file is checked against its sum.
- **Two levels of trust.** *Verified* authors may ship PHP. *Community* code runs only as data, in the safe plugin language, or as an outside app over OAuth.
- **Automatic checks.** Every publication gets static analysis and a check for dependencies with known holes.
- **Separation.** Every plugin has its own table prefix and its own permissions.
- **A stable API.** The plugin API follows semantic versioning, with a deprecation period.
- **An audit log.** What plugins do is written to it.

**Simple.**

- One click in the admin installs an extension, also on shared hosting without SSH, from signed archives.
- A failed install or update rolls itself back.
- No-code is the main road; code is only for what no-code cannot express.
- A project template installs a whole set of plugins with their settings in one click.

## Principles

1. **One set of extension points.** What Atrium does itself goes through the same points as a plugin. Its own gadgets, events, actions and screens are registered the way anyone else's are. A point Atrium does not use itself will rot.
2. **Each level stands on the one below.** What the builder makes is the same definition a plugin registers. An outside app sees the same events and the same API as a plugin. An entity built with the mouse gets search, import, export, an API and a history exactly like a module written in code. Those features read the module, and they do not care who wrote it.
3. **Data before code.** What can be data is data: an entity, a field, a view, a status, a formula, a rule, a print form, a dashboard.
   - Data is stored in the database with versions.
   - It is exported as a template, handed out through the catalogue and installed without Composer.
   - An update of Atrium does not break data the way it breaks patched code.
4. **Safe by construction.**
   - The builder makes data only.
   - Formulas, rules and community plugins are written in one language, and its compiler is the security boundary: what the language cannot express cannot happen.
   - PHP is for verified authors.
   - An outside app sees only what its scopes allow, and its screens live in a frame.
5. **One language of our own, for everything.** Formulas, rules made of blocks and plugins written as text all compile to one intermediate representation (IR).
   - A formula looks like Excel; a plugin looks like a small subset of JavaScript; a rule is blocks.
   - Each can be opened as the other.
   - This replaces the earlier decision to use Symfony ExpressionLanguage and to put Lua or JavaScript into a sandbox (see [why](#why-a-language-of-our-own)).
6. **The same gates.** Everything here ships with coverage and mutation score at 100%, in 15 languages, with contrast of at least 4.5:1, and never with colour as the only sign. The compiler of the language also has its own suite of tests that try to escape the sandbox.

## The core and the plugins

This table shows what `packages/admin` holds today and where each part goes. "Core" is what every installation needs, whatever it is for. The rest moves into plugins, the way the site already lives in `pages`, `news`, `menus`, `forms`, `seo` and `theme`.

| Part | Today | Goes to |
|---|---|---|
| Users, roles, permissions, two-factor sign-in, profile | `packages/admin`, `two-factor` | Core (two-factor joins it: no installation should go without it) |
| Settings, languages, sites | `packages/admin` | Core |
| Modules, the catalogue of modules | `Atrium\Admin\Modules`, the **Modules** screen | Core, as the [extension catalogue](#extensions-install-trust-remove) |
| Hooks, the event catalogue, the points in the panel | `Atrium\Admin\Hooks`, `Atrium\Admin\Extensions` | Core |
| API, tokens, MCP | `packages/admin` | Core |
| Files and the media library | `packages/admin` | Core: every entity may hold files |
| Import and export, activity log, versions | `Atrium\Admin\Transfer`, [#115](https://github.com/AlveBy/atrium/pull/115), [#117](https://github.com/AlveBy/atrium/pull/117) | Core |
| Custom fields | `alveby/atrium-custom-fields` | Core, merged into the builder: an entity is "an empty module plus fields" |
| The builder, the formula language | not built | Core |
| SEO of records, slugs and their history, robots and the sitemap | `SeoMeta`, `Slug`, `SlugHistory`, `SeoFilesController` in `packages/admin` | Plugin: the site |
| Theme, skins, slots of the site, embeds, page timing and the speed gadgets | `ThemeController`, `Hook::slot()`, `Atrium\Admin\Embeds`, `PageTiming` | Plugin: the site |
| Captcha | `CaptchaController` | Plugin: forms (sign-in keeps its own throttle) |
| Pages, news, menus, forms, catalogue, brands, stores, team, FAQ, reviews, search | their own packages | Plugins of the site (they already are) |
| Backups, errors, monitoring | their own packages | Plugins |
| Integrations, Bitrix, Bitrix24, 1C, WordPress, AI, social sign-in | their own packages | Plugins |
| Solutions | `alveby/atrium-solutions` | The installer moves to the core as templates; each solution becomes a package of its own |

**Moving out without breaking an installation.** Parts move one step at a time, each step its own pull request (track M below):

1. A plugin declares what it requires (`extra.atrium.requires`: other plugins with versions, and the core version). The catalogue checks it before install and update.
2. A plugin declares what it owns: the table prefix, permissions, menu items, schedules and settings. Removal can then take it all away and offer an export first.
3. A part leaves `packages/admin` for a plugin **with its table names and class names kept**.
   - The old classes stay as aliases with a deprecation notice for the deprecation period.
   - The tables are not renamed, so no data moves.
4. The umbrella package `alveby/atrium` requires the moved plugin until 1.0. `composer update` brings it along, and an existing site sees no difference. A new installation may leave it out.
5. The changelog of each step says what moved, and what to do if a site imports the old class directly.

## What there is

What the code on `main` and in the open pull requests already gives an extension author.

| Piece | Where | What it gives | What it lacks for a platform |
|---|---|---|---|
| Modules as Composer packages | `Atrium\Admin\Modules`, [modules.md](modules.md#a-module-as-a-package) | `type: atrium-module`, the `extra.atrium` manifest (models, pages, migrations, demo, settings, permissions, submodules), `atrium:module:install` / `remove`, switching off and on | Installed from the console only; no dependencies between modules, no manifest of permissions, no clean removal of what a module owns |
| Catalogue of modules | **Modules** screen | Packages of type `atrium-module` from Packagist, alveby's first, with the commands that install them | Only modules; no templates or apps; no install button; no descriptions, screenshots or trust levels |
| Hooks | `Atrium\Admin\Hooks\Hooks`, the `Hook` facade | Actions (`fire` / `on`, Laravel events by name), filters (`apply` / `filter`), registries (`register` / `registered` / `resolve`), slots of the site's templates | — (the event catalogue gives them names and payloads) |
| Record hooks | `AdminController`, `RecordWriter` | `atrium.record.data` (refuse with `Refused`), `atrium.record.saved`, `atrium.record.deleting`, `atrium.record.deleted` for every module; the panel, the API and MCP alike | — (the catalogue names them per module) |
| Points in the panel | `atrium.module.fields`, `columns`, `filters`, `tabs`, `actions`, `bulk_actions`, `links`, `atrium.admin.menu` | A package adds fields, list columns, filters, tabs, buttons on a record and over checked records, buttons above a list and menu items to any module | Each is drawn by the panel's own components; a package brings no Vue of its own |
| HTML on a form | `DisplayField` | Server HTML (a Blade view) on the edit form, in any tab | Only on the form, only HTML |
| Dashboard gadgets | `Atrium\Admin\Dashboard\Gadget`, `atrium.dashboard.gadgets` | A tile with its data; the set comes from the config | — (PR 3 lets packages add them) |
| Screens of a package | `packages/admin/resources/js/Pages` | Inertia pages | Only pages inside `packages/admin` are found, and the admin is a prebuilt bundle |
| Slots of the site | `Hook::slot()`, `@atriumSlot` | HTML in nine places of the public theme | — |
| Embeds | `Atrium\Admin\Embeds`, [embeds.md](embeds.md) | A registry of things a text can embed (a form, a block) | — |
| Blocks | [#104](https://github.com/AlveBy/atrium/pull/104) | Reusable pieces of text or HTML, edited in one place, shown in templates and inside any text | Not a page builder: no layout, no settings per block, no data from entities |
| Page templates | [#106](https://github.com/AlveBy/atrium/pull/106) | Blade templates for a record's public page, picked per record | Written in Blade, not assembled |
| Custom fields | `alveby/atrium-custom-fields`, [custom-fields.md](custom-fields.md) | Fields of 11 types added to any module from the admin, stored in the `custom` JSON column; filters, sorting, search, facets, own tabs ([#103](https://github.com/AlveBy/atrium/pull/103)), API and MCP | Only on modules that exist: no new entities; the types are a fixed enum |
| Ready-made sites | `alveby/atrium-solutions` | A folder with `solution.json` (modules, skin, menu, pages, palette, labels, URLs), content written per country in 15 languages and a seeder in PHP, installed by `atrium:solution:install`; a catalogue with screenshots (`--json`) | Sites only; the content is put in by PHP code, so a solution cannot be made in the admin or exported from it |
| Views of a list, kanban | [#116](https://github.com/AlveBy/atrium/pull/116) | `getAdministrationViews()` and the `atrium.module.views` filter, `Atrium\Admin\Views\View`; `KanbanView` by a select or a link field; the chosen view per administrator | A calendar view |
| Activity log, versions | [#115](https://github.com/AlveBy/atrium/pull/115), [#117](https://github.com/AlveBy/atrium/pull/117) | Every save and delete of every module, with who, from where and what changed; versions of every record, compare, restore | An actor that is a plugin or an app |
| Import and export | `Atrium\Admin\Transfer`, [import-export.md](import-export.md) | CSV and Excel for every module, a check before writing, the console, a schedule | Structure and data of a project in one file |
| API and tokens | `routes/api.php`, `ApiTokenController`, `/api/v1` | REST for every module, OpenAPI, tokens with abilities `{module}:read` and `{module}:write`, an MCP server | Tokens are made by a person for a person; no apps, no OAuth |
| Integrations | `alveby/atrium-integrations`, [integrations.md](integrations.md) | `IntegrationJob` with retries, the exchange log (secrets masked), alerts; Bitrix24 and 1C stand on it | No webhooks of its own, in or out |
| Yandex Market feed | [#100](https://github.com/AlveBy/atrium/pull/100) | A YML feed of the shop's catalogue for Yandex Market (`MarketFeed`) | Nothing to do with extensions. To keep the two apart, the place plugins come from is called the **extension catalogue** here and in the code, never "the Market" |

So the base is good for a programmer who writes a module. Everyone else is missing five things:

- a **list of events** that a person or a program can pick from (PR 2);
- a way for a package to bring **its own interface** into the prebuilt admin (PR 3 to 6);
- **entities that are data** rather than code;
- **one language** for formulas, rules and plugins;
- a **catalogue** that installs, checks and removes all of it safely.

## The shape of the platform

The levels run from the safest to the most powerful. A person goes down only as far as the task needs.

```text
 1  Data                        entities · fields · links · views · statuses · rules as blocks · print forms · dashboards · templates
 2  One language                formulas (Excel-like) · rules as text · community plugins · compiled to PHP, later to JavaScript
 3  Outside apps (any language) OAuth with scopes · webhooks · REST · panels in a signed frame
 4  Plugins in PHP              verified authors only · service provider · points in the panel · actions · field types
 ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
    Core contracts              event catalogue · points in the panel · actions · field types · the IR · permissions
    Core                        users and roles · settings · extension catalogue · API · files · history · import/export
```

The levels do not call each other. Each of them talks to the core contracts, and the built-in features are registered there too.

## The core contracts

The contracts are the public API of the platform. Their names and payloads follow semantic versioning:

- Within 0.x, a deprecated name keeps working for at least two minor versions. It says so in the log, in the changelog and in `php artisan atrium:extensions:check`.
- From 1.0 on, a name is removed only in a major version, and fields are only ever added.

### The event catalogue

**Built**: PR 2 ([#120](https://github.com/AlveBy/atrium/pull/120)).

Every event a subscriber may hear is declared once, with a label and a payload. Modules keep firing hooks as they do now (`Hook::fire('atrium.tracker.task.status_changed', $task, $from, $to)`). The declaration says what that hook means to the outside.

```php
use Atrium\Admin\Extensions\Event;
use Atrium\Admin\Facades\Hook;

// In the service provider's boot(), while the module is installed:
Hook::register('atrium.events', 'tracker.task.status_changed', Event::make('atrium.tracker.task.status_changed')
    ->label('atrium-tracker::events.status_changed')   // text or a translation key
    ->subject(Task::class)                              // the first argument is the record
    ->payload(fn (Task $task, string $from, string $to): array => ['from' => $from, 'to' => $to]));
```

- The **key** (`tracker.task.status_changed`) is what people and programs see: in a rule, in a webhook subscription, in the API. The **hook** is the name the module fires.
- The **subject** is the record the event is about. It decides who may hear it:
  - a webhook or an app gets the event only with read access to the subject's module;
  - a rule only fires for records it can see.
- The **payload** is plain JSON data, built from the hook's arguments. It is what a webhook sends, so it must not hold secrets.
- **Record events** are declared for every module by the core: `{module}.created`, `{module}.updated` and `{module}.deleted`, from `atrium.record.saved` and `atrium.record.deleted`. An entity made in the builder gets them the moment it exists.
- When a declared hook fires, the catalogue turns it into an **occurrence** and fires the single action `atrium.event.occurred` with it. Rules, webhooks and apps listen to that action alone. An occurrence has:
  - an id, for idempotency;
  - the key;
  - the subject (module and id);
  - the payload;
  - the actor: a user, an app, a plugin or nobody;
  - the site and the time.

```json
{
  "id": "0f9f2a8e-6c1b-4a0e-9d55-8b6a8f0d1e23",
  "event": "tracker.task.status_changed",
  "occurred_at": "2026-11-25T10:15:00+00:00",
  "site": 1,
  "actor": {"type": "user", "id": 7},
  "subject": {"module": "tasks", "id": 42},
  "data": {"from": "todo", "to": "in_progress"}
}
```

`php artisan atrium:events` lists the catalogue. Later it will also be listed by `GET /api/v1/events` and on the screen of rules.

### Points in the panel

The panel already takes fields, columns, filters, tabs, record and bulk buttons, buttons above a list and menu items from any package (the `atrium.module.*` filters). Three points are added, and the built-in pieces move onto them:

| Point | Registry | Where it is drawn |
|---|---|---|
| Dashboard gadget | `atrium.ui.gadgets` | A tile on the start page. The built-in gadgets are registered here; `atrium.dashboard.gadgets` stays the layout. **Built**: PR 3 ([#123](https://github.com/AlveBy/atrium/pull/123)) |
| Record panel | `atrium.ui.panels` | A block beside or under a record's form, or a tab of its own, for one module or for all. **Built**: PR 4 ([#124](https://github.com/AlveBy/atrium/pull/124)) |
| Screen | `atrium.ui.pages` | A page of its own in the panel, with a menu item and a permission |

What such a point shows is one of three kinds of **content**:

- **HTML** from the server (a Blade view), as `DisplayField` does. It works without building anything.
- A **component**: a Vue component from the package's own prebuilt ES module.
  - The admin hands Vue, Inertia, its components and translations to such modules (`window.Atrium`), so a package does not rebuild the admin.
  - The package publishes its file to `public/vendor/{package}` and names it in the registry `atrium.ui.scripts`.
  - This is for verified authors only, because a component is code that runs in the administrator's browser.
- A **frame**: an outside app's page in an `<iframe>` with a signed context (see [level 3](#level-3-outside-apps-in-any-language)). This is how community code shows an interface.

Each point takes a permission (`->permission('tracker.view')`), a condition (`->when(fn (Model $record, AdminUser $user): bool => …)`) and an order.

### Actions

What a rule may do is a registry, `atrium.automation.actions`, and the built-in actions are in it:

- change a field, or move to a status;
- create or update a record;
- notify, or send a webhook;
- make a PDF from a print form;
- ask for an approval;
- run a record button (any `RecordAction` a module already has);
- wait.

A plugin adds its own: a class with the settings the builder shows (the same `Field` classes as a form) and `run(Occurrence $occurrence, array $settings)`. In the language, an action is a function the plugin may call if its manifest allows it.

### Field types

The custom field types are an enum today. They become a registry, `atrium.fields.types`. Each type says how its value is stored and normalised, which form field draws it, and how it is filtered, sorted, imported, exported and described in the API. Money with a currency, a phone, an address, a user, a rating, a status, a formula, a roll-up and an auto-number are all types added this way, the built-in ones included.

## Level 1. Data: the builder and templates

This is the main level. The bar is set by the smart processes of Bitrix24, by Airtable and by Notion, and by the complaints people make about them. A **Builder** section in the admin is for a superadmin and for roles with `builder.manage`; within a project, it is also for that project's own administrator.

### Entities

An entity is a section of the panel made with the mouse: "Deals", "Candidates", "Cars", "Patients". It has a name in the content languages, a key, an icon and a place in the menu. It also has switches: sections (a tree), pages on the site, per site, translatable, SEO and files.

**Storage.** Each entity is a real table, `atrium_entity_{key}`.

- The builder creates it with the system columns: `id`, `site_id`, `name`, `status`, `sort`, `active`, `created_by`, `updated_by`, the dates and the `custom` JSON column.
- The person's fields are **custom fields**, with the same storage, filters, facets, sorting, search and "hot" real columns as on a module in code. So the builder is "an empty module plus custom fields".
- It is a table per entity, not one table for all. Each entity gets its own indexes and its own size, and an export or a drop touches one table. This is how Bitrix24 keeps smart processes.

**No limit on rows.** There is no limit on records. The builder is designed for 100,000 records in an entity and more:

- a field that is filtered or sorted becomes a real indexed column by itself (a "hot" field);
- past the first pages, lists page by key rather than by offset;
- counts on large lists are cached;
- roll-ups are stored and recomputed when data changes, not on every read.

A test seeds 100,000 records and checks the list, a filter, the kanban and a roll-up against time limits.

**A class per entity.** The panel knows its modules by Eloquent class.

- The builder writes a two-line class per entity (`final class Deal extends Atrium\Builder\Entity {}`) into `bootstrap/cache/atrium-entities`, and an autoloader loads it.
- The file is made again from the database whenever it is missing (`php artisan atrium:entities:cache`). No `eval` is used.
- `Entity` implements `Administrable` and reads its fields, columns, tabs and views from the definition in the database (cached).

From then on, search, import and export, the API and MCP, the activity log and versions, the trash, the kanban and the permissions all work for it as for any module.

**Definitions have versions.** Every change of an entity, a field, a view, a formula or a rule is a new version of the definition. It records who changed it and what it was before, with a button to go back.

**Moving between installations.** An entity, or a set of them, is exported as one file with its structure and, if wanted, its data. It is then imported on another site: definitions first, then the records, with links mapped by key and files included. Running it twice does not double anything.

### Links, roll-ups and table parts

- **Links.** A field may link to another entity or to a module in code (a product, a task, a user).
  - It can link to one record (a column `{field}_id`) or many (one shared table `atrium_entity_links`: from, field, to).
  - A linked record shows its counterpart's list in a tab.
- **Table parts.** A link may have **fields of its own**, such as quantity and price on the line between an invoice and a product.
  - A link with fields, shown as an editable table inside the record, is a **table part**: the lines of a document.
  - Its columns may be formulas (`Quantity * Price`).
- **Look-ups and roll-ups.** Take a field from a linked record, or a sum, count, average, minimum or maximum over linked records, with a condition ("only paid").
  - Roll-ups are stored and recomputed when a linked record changes, so lists filter and sort by them.

### Lists, forms and views

- **Columns**: which ones, in which order, and the default sort. Saved views ("My deals", "Overdue") are kept for a user, for a role or for everybody.
- **Forms**: tabs and the order of fields with the mouse, required fields, hints and default values.
  - **Conditional forms**: a field or a tab is shown only when a formula says so ("Company" only when "Client type" is "company").
  - **Dependent lists**: the choices of one field are filtered by another ("City" by "Country"). This works on public forms too.
- **Views**: **table**, **kanban** by a status or a select field, and **calendar** by a date field, then **gallery**, **Gantt** and **map**. They are the `View` classes of [#116](https://github.com/AlveBy/atrium/pull/116), so a module in code gets them too.

### Statuses and transitions

A **status** is a field type made of stages. Each stage has a name, a colour and an icon (never colour alone). Final stages are marked as success or failure. The type also says which stage may go to which, and which roles may move a record.

On every stage, the type says which fields are **required**, which are **locked** (read-only), and what happens on entering it (a rule). The kanban's columns are the stages.

### Rules: when → if → do

A rule has a **trigger**, a **condition** and **steps**. It is made of blocks and stored as the [IR](#the-ir).

- **When** — the trigger is one of:
  - an event from the catalogue: any module's, the builder's entities' own, or a status change;
  - a form sent on the site;
  - a schedule ("every Monday at 9:00");
  - a date field being reached ("3 days before `Due`");
  - an **incoming webhook**.
- **If** — a condition in the [formula language](#formulas): `Amount > 10000 AND Status = "Won"`.
- **Do** — steps from the actions registry, in order, with branches, loops over linked records and pauses ("in 3 days, if not paid").

How rules run:

- **No limits on runs.** Rules run on the queue, one at a time per record. A run is idempotent per occurrence. A rule does not start itself again deeper than a set number of levels.
- **A log of every run**: what fired, what each step did, and where and why it failed. A failed run has a **retry** button.
- **A dry run** on a chosen record shows every step with what it would do, and changes nothing.
- **An incoming webhook** per rule: a signed address that creates a record, or updates the one found by a key field (an order number, an email).

**Robots that notify** are rules whose step is "notify". The step says:

- whom: a user, a role, the person in a field, or the author;
- how: a notification in the panel or mail (Telegram and others are plugins);
- what: a text with fields of the record.

### Permissions

The permissions every module has (view, create, change, delete and its own) are there for an entity at once. These are added for every module, whether made in the builder or in code:

- **on a row**: only own records (by the author or by a "responsible" field), or the records a formula allows (`Department = User.Department`);
- **on a field**: hidden or read-only for a role ("the salary is seen by the accountant");
- **on a view**: who sees a saved view or a dashboard;
- **an administrator of a project**: a role that manages the entities, views and rules of one project and nothing else.

The API, MCP, import, export, search, reports and rules respect the same permissions. A record a person may not see is not in their report either.

### Project templates

A template is a project in one file. It holds entities, fields, links, views, statuses, formulas, rules, roles, print forms, dashboards, the menu, pages of the site and, if wanted, sample records. It also lists the plugins it needs (`"requires": {"alveby/atrium-tracker": "^0.3"}`), and has its texts in 15 languages.

- It is installed in one click, plugins and settings together.
- A newer version shows what it will change before it does, and keeps the data.
- When it is removed, it asks whether to keep the data.
- A project made in the builder is exported as a template with one button, so templates are made the same way as everything else.

A template is data, not code: it is installed without Composer and can be shared freely. It is published as a signed archive in the extension catalogue, or as a Composer package of type `atrium-template`. [Solutions](#solutions) are templates with a demo and instructions.

## Level 2. One language: formulas, rules and plugins

### Why a language of our own

The first version of this design took Symfony ExpressionLanguage for formulas and left scripts for later, in Lua or JavaScript inside a sandbox. What is now asked of formulas does not fit that:

- function names in Russian and in English;
- fields by their names, with spaces (`Quantity * Price`, `Дата отгрузки`);
- `;` between arguments, as in a Russian Excel;
- roll-ups over related records;
- errors that name the line and the field;
- a sum that explains what it is made of;
- versions.

One language is also wanted for formulas, rules and plugins, so that a rule made of blocks can be opened as text and back.

A sandbox around Lua or QuickJS needs a PHP extension that shared hosting does not have. It also limits a process, not what the code may reach. So Atrium has one small language of its own, and its compiler is the security boundary.

### The IR

Formulas, rules and plugin code are parsed into one **intermediate representation**: a tree of typed nodes, versioned like a contract. The nodes are values, field references, calls of functions from the registry, operators, conditions, loops over a list and steps of actions. Everything else is a front end that produces it or a back end that consumes it:

```text
 Excel-like formula ─┐                   ┌─ interpreter (formulas, rules) with limits
 rule of blocks ─────┼──► IR (typed) ────┼─ compiler to PHP (plugins)
 plugin as text ─────┘   + manifest      └─ compiler to JavaScript (forms, charts in the browser)
```

- **Calls.** A node can call only functions of the registry `atrium.language.functions`: the formula functions, the actions, and the read and write API of entities. Each function says which permission of the manifest it needs.
- **Checks before running.** The IR is checked for types, names of fields, permissions and depth of nesting. An error points to the line and column of the source, or to the block.
- **Limits.** It runs with limits on steps, time, memory, and the number of reads and writes. A loop that does not end is stopped, not left to hang the site.

### Formulas

There is one language of formulas in the whole system: in fields, in the lines of documents, in print forms, in reports and charts, and in conditions of forms, statuses and rules. Learn it once, use it everywhere.

- **Like Excel.** Functions are as in Excel, with Russian and English names: `СУММ` / `SUM`, `ЕСЛИ` / `IF`, `ВПР` / `VLOOKUP`, `ОКРУГЛ` / `ROUND`, `ДАТАМЕС` / `EDATE`. Fields are named, not addressed: `Quantity * Price`.
- **Related records.** `SUM(Lines.Amount)` and `COUNT(Tasks; Status = "Done")` are roll-ups over linked records, with a condition.
- **Columns and totals.** A formula may work on a whole column, not only on one row: in a report's total row, or in a chart series.
- **Clear errors.** Not `#VALUE!`, but "in line 12 the field Price is empty, and the formula needs a number". Every error names the line and the field.
- **Shows how it was counted.** Any sum in a document opens into the formula and the values it was made of, down to the linked records.
- **Safe.** A formula cannot read data its author may not see, hang, or eat the server. Time and memory are limited, and it reaches only what permissions allow.
- **Versions.** A formula is stored with its history: who changed it, and how it counted before.
- **Moved in.** Importers translate the formulas of Airtable and Bitrix24 into ours.

Formulas are used in:

- **computed fields**, stored on save so that lists filter and sort by them;
- **roll-ups**;
- **default values** (`TODAY() + 14`);
- **checks** (`End >= Start`, with the message to show);
- **visibility** and **requirement** of fields;
- **conditions** of rules and transitions;
- **columns of table parts**;
- **series of charts** and **totals of reports**.

A plugin adds functions to the registry.

### The plugin language

When rules are not enough, a plugin is written as text in a safe subset of JavaScript, with TypeScript types allowed.

- **In the language**: `let` and `const`, functions, `if`, `for … of`, objects and arrays, and the functions of the registry.
- **Not in it**: files, raw SQL, `eval`, the network beyond the addresses of the manifest, PHP's globals, classes of the host, and `while` without a bound.
- **How it compiles**: the compiler translates it to the IR, then the IR to PHP for the server, and later to JavaScript for the browser.

```js
// A rule's step in a community plugin: the manifest allows tasks:read, worklogs:write
export function onTaskDone(task) {
  const hours = sum(task.worklogs, w => w.hours)
  if (hours > task.estimate * 1.5) {
    notify(task.owner, t("Task :key took :hours h", { key: task.key, hours }))
  }
}
```

- **Sandbox at compile time.** What the plugin may call is decided by the compiler from the manifest, not only checked by a guard at runtime. A call outside the manifest is a compile error. The PHP it produces calls only `Atrium\Language\Runtime`, which checks permissions and counts steps, time and memory again.
- **Errors point to the source.** A compile or runtime error shows the plugin's line, not the generated PHP.
- **An editor in the admin**, with:
  - highlighting;
  - completion of fields, functions and events;
  - errors as you type;
  - a dry run on a record.
- **The compiler is the security boundary.** It has a suite of tests that try to get out and must fail: reaching a file, the database, a global or an object of the host, looping forever, exhausting memory. Like everything else, it has coverage and MSI at 100%, and it runs on every change of the compiler.
- **PHP stays** for verified authors (level 4). The language is for community plugins and, through blocks, for people who do not program.

### When each part comes

| Part | Pull request | Why then |
|---|---|---|
| The IR, the formula parser and its interpreter | PR 7 | Fields, roll-ups and conditions in stage 3 need formulas |
| Rules stored as the IR | PR 14 | Rules made of blocks are the second front end; one store for both |
| The compiler of the IR to PHP, the runtime, the sandbox tests | PR 31 | It comes after the core it calls: API, events, permissions, points |
| The text syntax and the editor | PR 32 | It needs the compiler, and the registry of functions to complete from |
| The compiler to JavaScript | PR 33 | Conditional forms and charts work in the browser without a request |

## Level 3. Outside apps in any language

This works as Connect apps do for Jira. An app runs on its own server, in any language, and works with Atrium over HTTP only. It cannot break the site, and it sees only what it was allowed to see. This is the way for community code that needs more than the plugin language.

- **Registration.** In **Apps**, a superadmin adds an app from the extension catalogue or by the address of its manifest. The manifest (`atrium-app.json`) gives the name, icon, addresses, the scopes it asks for, the events it wants and its panels. The install screen lists what the app will see and do, and it is installed only after a confirmation.
- **OAuth 2.0** with the authorization code and PKCE, with tokens per installation.
  - **Scopes** are the abilities tokens have now (`{module}:read`, `{module}:write`), plus `events:read`, `webhooks:write` and `ui:panels`.
  - A token never gives more than its scopes, nor more than the user who installed the app had.
- **Webhooks.** An app (or a person, in the admin) subscribes to keys of the catalogue.
  - Each occurrence is sent as the JSON above by the queue of `alveby/atrium-integrations`, with retries, the exchange log and alerts.
  - It is signed: `X-Atrium-Signature: sha256=HMAC(secret, timestamp + "." + body)`. The time and the occurrence id are in headers, so a replay or a double is noticed.
  - A subscription that fails a set number of times in a row is paused, and its owner is told.
  - An event is sent only if the app may read its subject's module.
- **Panels in a frame.** An app's panel, gadget or screen is a frame on one of the [points in the panel](#points-in-the-panel).
  - Its address gets a short-lived token signed with the app's secret (JWT, HS256, five minutes). The token carries the site, the user, the app, the place, the record, the language and the colour theme.
  - A small script (`atrium-app-bridge.js`) lets the frame change its height, show a message, open a page of the panel, and ask the form to reload the record.
  - The frame is sandboxed, and the panel's `frame-src` allows only the app's own origins.
- **REST API.** What there is now, plus `GET /events`, the subscriptions and `GET /me`. The builder's entities appear in OpenAPI by themselves.

Apps that run on Atrium's servers, as Forge apps do on Atlassian's, are not planned. The plugin language already covers code that runs safely inside the site.

## Level 4. Plugins in PHP from verified authors

A plugin in PHP is a Composer package of type `atrium-module`, the same as a module. A module with models is a plugin, and a plugin may have no models at all. Everything it adds is registered in its service provider's `boot()`, while it is installed, through the core contracts and the hooks:

```php
public function boot(): void
{
    if (! app(Modules::class)->isInstalled('acme/atrium-tracker-time')) {
        return;
    }

    // An event of its own, for rules, webhooks and apps:
    Hook::register('atrium.events', 'time.logged', Event::make('atrium.time.logged')
        ->label('acme-time::events.logged')->subject(TimeEntry::class));

    // Listening to another module's event:
    Hook::on('atrium.tracker.task.status_changed', StopTimerWhenDone::class);

    // A column in the tracker's task list and a panel beside the task:
    Hook::filter('atrium.module.columns', AddLoggedColumn::class);
    Hook::register('atrium.ui.panels', 'time.task', Panel::make('time.task')->for(Task::class)
        ->label('acme-time::panels.time')->html('acme-time::panels.task')->side());

    // A gadget, an action for rules and a function for formulas:
    Hook::register('atrium.ui.gadgets', 'time.week', WeekGadget::class);
    Hook::register('atrium.automation.actions', 'time.log', LogTime::class);
    Hook::register('atrium.language.functions', 'logged_hours', LoggedHours::class);
}
```

- **The public API** is the contracts (`Atrium\Admin\Extensions\*`), the keys of the event catalogue and the IR. A plugin requires the version it was written for (`"alveby/atrium": "^0.3"`).
- **Its interface** is HTML from Blade, or Vue components from its own prebuilt module (see [content](#points-in-the-panel)). A Vite preset builds such a module, with Vue and the admin left outside it.
- **Tests**: it is tested with Orchestra Testbench, as Atrium's packages are, with the same gates.
- **Trust**: a plugin in PHP is trusted code. It runs inside the site with the site's rights, and its manifest cannot be enforced at runtime. That is why:
  - only verified authors may publish one;
  - a person reads its code whenever its permissions change;
  - the automatic checks look for calls its manifest does not declare.

## Reports, charts and documents

By what people say, this is the emptiest niche. No builder makes a chart series out of a formula, as Excel does, and BI tools are blamed for not counting on top of totals. All of it stands on the formula language and the permissions of stage 3.

- **Pivot reports.** A table grouped by rows and columns, with subtotals and formulas in the total row, like a pivot table in Excel but with live data. It counts **after grouping**:
  - the share of the total;
  - a measure over a measure ("revenue per manager");
  - a running total;
  - the change on the previous period.
- **A series is a formula.** Each series of a chart is a formula over the data: a sum, a share, the difference from last month, a moving average. "Plan", "actual" and "actual / plan × 100" are three series of one chart, without a separate table.
- **What if.** A slider or a field on a dashboard ("rate", "price growth, %") that formulas read. Charts are counted again at once.
- **Readable by everybody.** Series differ by the shape of their points, the dash of their lines and their labels, not only by colour. Text has contrast of at least 4.5:1.
- **Dashboards from gadgets.** A dashboard is assembled with the mouse from gadgets.
  - The basic gadgets are a number, a chart, a list and a report.
  - Plugins bring ready ones: team velocity, cumulative flow, cycle time, burn-down, the sales funnel, stock on hand.
  - Each person may have their own dashboard; shared ones are given to a role.
  - The gadgets plug into the registry from PR 3.
- **Print forms and PDF.** A template in `.docx` or `.xlsx` with fields, loops over table parts and conditions, edited in a Word- and Excel-like editor.
  - It is turned into PDF, Word or Excel.
  - It runs from a button on the record, in bulk, or as a step of a rule ("when the invoice is issued, make the PDF and mail it").
  - Ready ones come with solutions: invoice, act, contract.
- **Where they are shown**: on a dashboard, inside a record, in a document and its PDF, and on a page of the site as a block.

## Towards an ERP, step by step

The ERP level is added in steps on top of the builder; the core is not remade for it. Each step is a pull request of its own (track E, or the main line where noted) and is useful by itself.

| Step | What it gives | Where |
|---|---|---|
| Computed fields and roll-ups | Sum, count, minimum, maximum over related records, with a condition | PR 7, PR 11 |
| Table parts | Lines of a document with their own columns and formulas; totals, discounts and VAT counted from them | PR 11 |
| Auto-numbering | Series per type of document and per period (`INV-2026-0001`), without gaps, given out inside the transaction that saves | E1 |
| VAT, rounding and currencies | A rule per document: with VAT or without, per line or on the total, the precision of the currency; the rate kept as of the document's date; tests for a one-cent difference | E2 |
| Print forms | `.docx` / `.xlsx` templates to PDF | PR 27 |
| Registers and posting | **Accumulation registers** keep balances and turnovers (stock, money, settlements); **information registers** keep values over time (prices, rates). A document is **posted** into registers by rules made of blocks, and unposting reverses it. As in 1C, any balance opens into the documents it was made of | E3 |
| Multi-step approvals | An "approve" step in rules: one by one or all at once, by a role or by the person in a field, with a deadline, a substitute and the history of decisions | E4 |
| A closed period | Documents and registers before a date cannot be changed or posted. Who may lift the lock is configured, and lifting it is written to the log | E5 |
| Permissions on a record and a field | As in [permissions](#permissions) | PR 15 |
| Pivot reports | As in [reports](#reports-charts-and-documents) | PR 24 |
| Dashboards | As in [reports](#reports-charts-and-documents) | PR 26 |
| Bank statements | 1CClientBankExchange, CSV, MT940, CAMT; a payment finds its invoice by tax number, amount and purpose | A plugin of the finance solution |

## The site builder

The site is a plugin, and its builder is a track of its own (S). It assembles pages from blocks and takes its data from the same entities as the rest of the system. What others charge for is in the free plugin: lists and cards from your own data, forms into your own tables, many languages, SEO and full export.

It is built on what exists:

- blocks ([#104](https://github.com/AlveBy/atrium/pull/104));
- page templates ([#106](https://github.com/AlveBy/atrium/pull/106));
- the theme and its skins (`packages/theme`);
- pages, menus, forms and SEO;
- the slots and the embeds.

How it works:

- **A page is data**: a tree of sections, columns and blocks with their settings. It is stored with versions on the page record and rendered by Blade on the server. A page template from #106 may become such a tree.
- **Block types are a registry** (`atrium.site.blocks`). Each type has its settings (the same `Field` classes as a form), its Blade view, its styles and scripts, and a preview.
  - Built-in types: text, picture, gallery, button, columns, a reusable block from #104, a form, a menu, a map.
  - Three types bring the builder's entities to the site without code: a **list** (an entity, a filter, a sort, a page size, a card template), a **card** (the public page of a record) and a **filter** (the facets of custom fields).
- **The editor** has:
  - drag and drop of blocks;
  - settings beside the page;
  - a layout for phone, tablet and desktop, with settings per width;
  - a draft, a preview, publishing, and going back to an earlier version.
- **Fast by construction.** A page loads the styles and scripts of the blocks it has, and no more. Pictures are in webp, at the size they are shown.
- **Themes and templates come from the extension catalogue** and are checked like any plugin. A theme is styles and Blade views; a site template is pages, menus, forms and settings.
- **Menus, forms and SEO** are edited from the same screen. A page has its place in a menu, its title, description, social picture and redirects.

| # | Pull request | Useful at once because |
|---|---|---|
| S1 | The site moves out of the core: SEO, slugs, theme, slots and embeds go into `alveby/atrium-site`, with their names kept | The core gets smaller; the site is a plugin like the rest (step 3 of the [move-out](#the-core-and-the-plugins)) |
| S2 | A page as a tree of blocks, the block registry, rendering, the built-in static blocks | Pages are assembled, not written in HTML |
| S3 | The editor: drag and drop, settings, preview, draft, versions | People build pages themselves |
| S4 | Layout per width: phone, tablet, desktop | Pages look right on a phone |
| S5 | Data blocks: list, card and filter from entities | The builder's entities appear on the site without code |
| S6 | Forms into entities and rules; dependent lists on the site | A request from the site lands in your own table and starts a rule |
| S7 | Menus and SEO from the editor | One screen for a page |
| S8 | Themes and site templates in the extension catalogue | A new look or a ready site in one click |

S1 may start at once. S2 to S4 come after PR 6 (screens) and PR 8 (field types for block settings). S5 and S6 come after PR 12 (views) and PR 14 (rules).

## Importers make entities

People do not leave a system they dislike because they fear for their data, so importers are part of the platform, not an extra. From now on an importer creates **entities of the builder**, not PHP models. That covers Bitrix, WordPress, Jira, Bitrix24 and 1C now, and later Airtable, Notion and Tilda.

- **A Bitrix information block is an entity.**
  - Its properties become fields, by the type rules the importer has now (`LIST_TYPE` and `FILE_TYPE` included).
  - Its sections become a tree.
  - Its element and section forms give the tabs and the order of fields.
  - Its list settings give the columns.
  - Its texts and SEO stay as they are now.
- **The same rules for all importers**:
  - a dry run with a report comes first;
  - a second run does not double anything, and brings over what appeared since;
  - an interrupted run carries on from where it stopped;
  - at the end there is an honest list of what did not move, and why;
  - the result is checked by counts per table.

**From a generated model to an entity.** This is for sites that have already moved, with models written by the importer (track I):

1. `php artisan atrium:entities:adopt "App\Models\News"` writes an entity definition from the model: its table, its real columns as "hot" fields, its custom fields, tabs, columns and views. A dry run first prints what it will produce.
2. The entity **keeps the table and its name**, so no data is copied.
3. The model's class name is kept as the entity's class (`final class News extends Entity {}`). The morph types in slugs, SEO, the activity log and versions keep pointing at it, and public templates keep working.
4. Anything the model has in code that the builder cannot express yet (a method a template uses, a relation of a special kind) is listed. The model is not adopted until that is moved into a formula, a rule or a small plugin.
5. Until the model file is deleted, the step can be undone with `--revert`.

| # | Pull request | Useful at once because |
|---|---|---|
| I1 | `atrium:entities:adopt`: a model into an entity, with a dry run and `--revert` | Sites that have already moved get the builder for their data |
| I2 | The Bitrix importer creates entities | A new move from Bitrix needs no generated code |
| I3 | The WordPress importer creates entities | The same for WordPress |
| I4 | Jira into the tracker's template (with the tracker's session) | Atrium Tracker as a solution |

## Solutions

A solution is a ready system made only of builder parts: a **template** (entities, views, formulas, rules, print forms, dashboards, pages of the site). Only where no-code cannot express something does it add a **small plugin**, in the plugin language if possible. There is no separate code per solution. If one cannot be built without its own models and controllers, something is missing in the core, and that is fixed in the core.

**It builds on `alveby/atrium-solutions`.** Today a solution is a folder with `solution.json` (modules, skin, menu, pages, palette, labels, URLs), content written for each country in 15 languages, and a seeder in PHP.

- **What is kept**: `solution.json`, the per-country content and the `Country` data (people, addresses, phones, prices), the catalogue with screenshots, and installing with one command or one click.
- **What changes**:
  - the seeder in PHP becomes the template's sample data, so a solution can be made in the admin, then exported and published without code;
  - `modules` becomes `requires`, a list of plugins with versions.

Every solution has:

- a **demo**: the sample data, and a page on the demo site;
- **instructions** and **screenshots**;
- a **version**, the **versions of the core** it works with, and the plugins it needs;
- an **upgrade without data loss**: a newer version shows what it will change in entities and rules, and keeps the records.

**Built, exported and published in an evening.** A test runs this end to end, in CI:

1. In a clean installation, make a small solution in the builder: two entities, a link, a formula, a view, a rule and a page.
2. Export it, and check the archive.
3. Publish it into a local catalogue.
4. Install it into a second clean installation, and compare.

### The first three, and their order

| Solution | What is in it | What it proves in the core |
|---|---|---|
| A simple CRM | Contacts, companies, deals in a pipeline, invoices with formulas and a print form, mail, requests from the site | Links, roll-ups, table parts, print forms, permissions on a row |
| Atrium Tracker, moved in from Jira | Tasks, sprints, boards, versions, time tracking as in Tempo, reports, the Jira import | Statuses, views, gadgets, rules, permissions, 100,000 records |
| A company site, moved in from Bitrix | Pages, news, catalogue, request forms, SEO, the import with addresses kept | The site builder, importers into entities, adoption of models |

**The order is the CRM, then the tracker, then the site.**

- **The CRM first.**
  - It is the exit check of stage 3: "a CRM is built without code".
  - It needs nothing but the builder, and has no import to wait for.
  - It exercises links, roll-ups, table parts, formulas and permissions on rows all at once.
  - It is also the first project template, "CRM for a studio", shipped with the first working builder.
- **The tracker second.**
  - It is the stress test of the builder: statuses with rules, many views, gadgets of velocity and cumulative flow, 100,000 tasks.
  - Its session keeps going in parallel on its own models, built on the points of stage 1, and moves onto entities as soon as stage 3 is there.
  - Its reports need stage 4.
- **The site last.**
  - It has the most moving parts: the whole site track, importers into entities and adoption of models.
  - It has live sites that must not break.
  - The site already works today as modules in code, so waiting costs nothing, while rushing could cost a live site.

### Project templates as a showcase

Each one is a package of the extension catalogue, exported from the builder and installed in one click. Each arrives with the part of the plan it needs:

| Template | Comes with |
|---|---|
| **CRM for a studio** | The first working builder (after PR 15) |
| **Task tracker** | The tracker's template |
| **Warehouse** | The registers (E3) |
| **Business card site** | The site builder (S5) |
| **Booking with a master** | The calendar view and rules |

### Atrium Tracker on the builder

Agreed with the tracker's session, the tracker becomes **a template plus a plugin**:

- **The template**:
  - projects, tasks, sprints, versions and worklogs as entities;
  - the workflow as statuses with transitions;
  - boards, backlog and calendar as views;
  - notifications and automations as rules;
  - its dashboards from gadgets.
- **The plugin** covers what no-code cannot express: sprint reports and velocity, timesheets as in Tempo, the query language over tasks, and the Jira importer.

Until stage 3, the tracker keeps its PHP models. It builds only on the platform's points: the event catalogue, gadgets, panels and screens. It keeps its business logic out of the models, in services a rule or the plugin can call. That way `atrium:entities:adopt` can later turn its models into entities without rewriting them.

## Extensions: install, trust, remove

The **Modules** screen becomes **Extensions**: one catalogue of plugins, templates, solutions, themes and apps. It draws on Packagist (types `atrium-module` and `atrium-template`) and on the index on the Atrium site, which adds descriptions, screenshots, ratings and trust levels. It is never called "the Market", so as not to be confused with the Yandex Market feed of [#100](https://github.com/AlveBy/atrium/pull/100).

### The manifest of permissions

Every extension declares what it wants to reach. The install screen shows that in plain words before the person confirms:

| Permission | Example | Enforced by |
|---|---|---|
| Tables | its own (by its prefix) and other modules': `deals:read`, `invoices:write` | the compiler and the runtime of the language; OAuth scopes for an app |
| Events | hears `deals.updated`, declares `time.logged` | the event catalogue |
| Routes | adds `/booking` to the site, `/admin/time` to the panel | the router: declared prefixes only |
| Outside addresses | `api.telegram.org` | the runtime's HTTP client: no other host |
| Files | its own folder on the uploads disk | the runtime's file functions |
| Schedule | `daily 03:00` | the scheduler: declared entries only |

An update that asks for more shows only the new permissions and waits for a confirmation. Until then, the old version keeps working.

### Trust and checks

- **Two levels of trust.**
  - *Verified*: an author whose identity has been checked. They may ship PHP and Vue, and a person reads their code whenever its permissions change.
  - *Community*: anyone. They may ship data, the plugin language and outside apps, but nothing that runs unconfined in PHP or in the admin's browser.
- **Signed and summed.** The catalogue's index is signed with Atrium's key, and every package with its author's key (Ed25519). Every file in an archive is listed with its SHA-256. A site checks both before it unpacks anything.
- **Checked on publishing**, automatically:
  - the manifest against what the code does (calls, tables, addresses);
  - static analysis (PHPStan at the strictest level, for PHP);
  - dependencies with known holes;
  - compatibility with the core versions it claims;
  - the 15 languages;
  - the contrast of its colours.
- **Its own prefix and permissions.** A plugin's tables start with its prefix (`acme_time_`), and its permissions are named after it (`acme-time.view`). Atrium's own packages keep their tables for now; new ones follow the rule.
- **Kept safe after publishing.**
  - A plugin that is no longer updated is marked as such.
  - A vulnerability can be reported through its own channel.
  - A dangerous version can be revoked in the index. Sites then switch it off on their next check and tell the superadmin.
- **An audit log** of what extensions do: installed, updated, removed, permissions confirmed. Every record an app or a plugin changes goes into the activity log of [#115](https://github.com/AlveBy/atrium/pull/115), with the plugin or app as the actor.

### Install in one click, roll back by itself

**Data and the plugin language** (templates, solutions, community plugins, themes) come as a signed archive. The site:

1. downloads it into a staging folder;
2. checks the signatures and sums;
3. checks requirements and versions;
4. shows the manifest;
5. on confirmation, applies it: definitions of entities in a transaction, and files swapped in by renaming a folder.

No code from the archive runs during install. If anything fails, the definitions roll back and the old folder comes back.

**PHP plugins from verified authors** need their dependencies resolved against the site's `composer.lock`. A web request must not do that: it is slow, it fails on most shared hosting, and it would run code from the internet with the web server's rights. So there are two ways, and both need the superadmin to confirm with the second factor:

- **With Composer on the server** (`atrium.extensions.composer`), the commands run in a **queued job**. A backup is made first (`alveby/atrium-backup`), and the previous `composer.lock` is kept for a rollback.
- **On hosting without SSH**, the plugin comes as a **signed archive**.
  - This works only for a plugin whose dependencies are all packages Atrium already ships; the catalogue checks that on publishing.
  - The plugin is put into `plugins/{vendor}/{name}` and loaded by Atrium's own autoloader.
  - An update swaps the folder and keeps the previous one.
  - A plugin's migrations must have `down()`. If a migration fails, the installer runs it and brings back the old folder.

A plugin that needs other libraries can be installed only with Composer, and the catalogue says so on its page.

**Removal** takes away what the plugin declared it owns: tables by its prefix, permissions, menu items, schedules and settings. It offers to export the plugin's data first. Records of other modules that linked to it keep their own values.

## Security

| Level | Trust | What limits it |
|---|---|---|
| The builder and templates | A person with `builder.manage`, or a project administrator | Makes data only; the same permissions as any module; no code |
| Formulas and rules | Anyone who may edit a field or a rule | The IR: only the functions of the registry, the author's own permissions, limits on steps, time and memory |
| Community plugins | None beyond the manifest | The compiler (calls outside the manifest do not compile), the runtime (permissions and limits again), signed archives, the sandbox tests |
| Outside apps | None | OAuth scopes, the installing user's own permissions, signed webhooks, a sandboxed frame, a signed context with a short life |
| PHP plugins | Full, like any package | Verified authors only, code read on every change of permissions, automatic checks, signed packages, the second factor to install |

## Stages and pull requests

Each pull request is small and useful by itself, with its tests, its documentation and its 15 languages. There are no dates here, only the order. Every stage stands on the one before and ends with a check that can be seen from outside.

The numbers are names, not the order. PR 1 to 19 keep the numbers of the first version of this plan; new ones take the next free number and are placed in their stage. Tracks M (move-out), S (site), E (ERP) and I (importers) run beside the main line once their stage allows.

Inside a stage the order follows who is waiting. The tracker needs panels, then gadgets, then screens, then components, so PR 6 (screens, with HTML first) comes before PR 5 (components). PR 9 (outgoing webhooks) needs only PR 2, so it may come right after stage 1.

**Stage 1. The foundation.** Check: a module adds a screen and a gadget without touching the core.

| # | Pull request | Useful at once because |
|---|---|---|
| 1 | The design (`docs/extensions.md`, `.ru.md`) ([#118](https://github.com/AlveBy/atrium/pull/118)) | Everyone builds against the same plan |
| 20 | This update: the first principle, core and plugins, one language, reports, ERP, site builder, importers, solutions, extensions | The plan says what the product is |
| 2 | **Event catalogue**: `Event`, `atrium.events`, record events for every module, `Occurrence`, `atrium.event.occurred`, `atrium:events` ([#120](https://github.com/AlveBy/atrium/pull/120)) | The tracker declares its events; any package hears `deals.created` without filtering every save |
| 3 | **Gadgets from packages**: `atrium.ui.gadgets`, HTML gadgets, the built-in ones moved onto it ([#123](https://github.com/AlveBy/atrium/pull/123)) | A package (the tracker's "My tasks") puts a tile on the dashboard |
| 4 | **Record panels**: `atrium.ui.panels`, beside or under the form or as a tab ([#124](https://github.com/AlveBy/atrium/pull/124)) | A plugin shows its block on another module's record |
| 5 | **Components from packages**: `window.Atrium`, `atrium.ui.scripts`, the Vite preset, an example plugin | Verified plugins bring Vue without rebuilding the admin |
| 6 | **Screens from packages**: `atrium.ui.pages` with HTML or a component | Backups, errors, monitoring and the tracker keep their screens in their own packages |
| M1 | Requirements of a plugin: other plugins and the core version, checked on install and update | Nothing installs on a core it does not fit |
| M2 | What a plugin owns, and removal that takes it away after an export | Removing a plugin leaves nothing behind |

**Stage 2. Formulas and integrations.** Check: an invoice with formulas in its lines shows what its sum is made of.

| # | Pull request | Useful at once because |
|---|---|---|
| 7 | **Formulas**: the IR, the Excel-like parser with Russian and English names, the interpreter with limits, errors with line and field, "how it was counted", versions, the function registry | One language for everything after it |
| 8 | **Field types as a registry** in custom fields, with money, formula and roll-up types | Plugins add types; the status, auto-number and table part build on it |
| 9 | **Outgoing webhooks**: subscriptions in the admin, signed sending through integrations | Any outside system hears the catalogue, before apps exist |

**Stage 3. The builder core.** Check: a simple CRM is built without a line of code.

| # | Pull request | Useful at once because |
|---|---|---|
| 10 | **Entities**: definitions with versions, the table per entity, `Entity`, the generated classes, the menu, no limit on rows (the 100,000-record test) | A person makes a section in the admin, with search, import, the API and history at once |
| 11 | **Links, roll-ups and table parts** | Deals linked to companies; invoices with lines and totals |
| 12 | **Views and forms**: saved views, kanban and calendar for entities, conditional forms and dependent lists | Pipelines, schedules, and forms that follow the answers |
| 13 | **Statuses and transitions**, with fields required or locked per stage | Stages with rules of movement |
| 14 | **Rules**: triggers, conditions, steps stored as the IR, the actions registry, the queue, the log with retry, the dry run, incoming webhooks | "When → if → do" and robots that notify |
| 15 | **Permissions**: on a row, a field and a view; an administrator of a project | Sales people see only their own deals |
| 21 | **Moving entities**: structure and data in one file between installations | A project goes from a test site to the live one |

**Stage 4. Reports and documents.** Check: a chart "actual / plan × 100" is built without a separate table.

| # | Pull request | Useful at once because |
|---|---|---|
| 24 | **Pivot reports**: grouping, subtotals, formulas in totals, share of total and measure over measure | Reports people now make in Excel |
| 25 | **Charts**: a series is a formula, what-if inputs, series told apart without colour | The chart from a formula, as in Excel |
| 26 | **Dashboards from gadgets**: per person and per role, assembled with the mouse, builder gadgets (number, chart, list, report) | Each person sees what matters to them |
| 27 | **Print forms**: `.docx` / `.xlsx` templates with fields, loops and conditions, PDF, a rule step | Invoices and acts without a paid add-on |

**Stage 5. Extensions and apps.** Check: a third-party plugin passes the catalogue's checks.

| # | Pull request | Useful at once because |
|---|---|---|
| 16 | **Templates**: export and install of a project with its plugins and settings, update with a preview, sample data | A project in one click |
| 17 | **The extension catalogue**: one screen for plugins, templates, solutions, themes and apps; the manifest of permissions; signed archives; install in one click with rollback; trust levels | Extensions reach a site without the console, safely |
| 28 | **Checks on publishing**: manifest against code, static analysis, vulnerable dependencies, compatibility, languages, contrast; revocation | A stranger's plugin is checked before anyone installs it |
| 29 | **PHP plugins from the admin**: the queued Composer job with the second factor, signed archives for hosting without SSH, rollback | Verified plugins install on any hosting |
| 18 | **Apps**: registration by manifest, OAuth with scopes | Outside apps in any language |
| 19 | **Frames**: signed context, the bridge script | Apps put their panels into the admin |
| 30 | **Plugins and apps in the audit log** as actors | You can see what an extension did |

**Stage 6. Solutions and the language.** Check: three solutions are built from parts of the core, without code of their own.

| # | Pull request | Useful at once because |
|---|---|---|
| 34 | **Solutions as templates**: `atrium-solutions` installs templates, seeders become sample data, the end-to-end "made in an evening" test in CI | A solution is made in the admin and published without code |
| 35 | **A simple CRM** and the template "CRM for a studio" | The first exemplar; may be done right after stage 3 |
| 36 | **Atrium Tracker** as a template plus a plugin, with the Jira import (I4) | The stress test of the builder |
| 37 | **A company site** moved in from Bitrix (S1–S8, I1, I2) | The site as a solution |
| 31 | **The compiler of the IR to PHP**, the runtime, the sandbox tests | Community plugins run safely in the site |
| 32 | **The text syntax and the editor** | Plugins written as text, with highlighting and hints |
| 33 | **The compiler to JavaScript** | Conditions and charts in the browser |

**Tracks.**

| Track | Pull requests | Starts after |
|---|---|---|
| M. Moving out of the core | M1, M2 (stage 1), then S1, then one part a step: captcha into forms, custom fields into the builder, two-factor into the core | Stage 1 |
| S. The site builder | S1–S8, [above](#the-site-builder) | S1 at once, S2 after PR 8, S5 after PR 14 |
| E. Towards an ERP | E1 auto-numbering, E2 VAT and currencies, E3 registers and posting, E4 approvals, E5 a closed period | Stage 3 |
| I. Importers | I1 adoption of models, I2 Bitrix, I3 WordPress, I4 Jira | PR 10 for I1–I3; the tracker's template for I4 |

PR 22 and PR 23 are kept free for whatever stage 3 shows is missing.
