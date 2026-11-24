# Extensions: the platform

[English](extensions.md) · [Русский](extensions.ru.md)

This is the design of Atrium as a platform: a product that others extend from outside, the way a marketplace works, and in which a person who does not program builds the project they need right in the admin. It starts with what the code already has, then says what is missing on four levels, and ends with the order of the pull requests that build it.

It is a design, not a guide: what is built is marked **built** with the pull request, and the guides ([modules](modules.md), [custom fields](custom-fields.md), [integrations](integrations.md)) stay the reference for what works today.

- [Principles](#principles)
- [What there is](#what-there-is)
- [The shape of the platform](#the-shape-of-the-platform)
- [The core contracts](#the-core-contracts)
- [Level 1. Without code: the builder](#level-1-without-code-the-builder)
- [Level 2. Formulas and conditions](#level-2-formulas-and-conditions)
- [Level 3. Plugins in PHP](#level-3-plugins-in-php)
- [Level 4. Outside apps in any language](#level-4-outside-apps-in-any-language)
- [Security](#security)
- [Pull requests, in order](#pull-requests-in-order)

## Principles

1. **One set of extension points.** What Atrium does itself goes through the same points as a plugin: its own dashboard gadgets, events and actions are registered the way a stranger's are. A point Atrium does not use itself rots.
2. **Each level stands on the one below.** What the builder makes is the same definition a PHP plugin registers; an outside app sees the same events and the same API as a plugin. An entity built with the mouse is searched, imported, exported, given an API and a history exactly like a module written in code, because those features read the module and do not care who wrote it.
3. **Data before code.** What can be data is data: an entity, a field, a view, a status, a rule. Data is stored in the database, exported as a template, handed out through the Market and installed without Composer.
4. **Safe by construction.** The builder and the formulas cannot run code. A PHP plugin is trusted: a superadmin installs it with Composer, like any package. An outside app is not trusted: it sees only what its scopes allow, and its screens live in a frame.
5. **No language of our own.** Formulas use a ready, sandboxed expression language (Symfony ExpressionLanguage). If scripts are ever needed, a known language (Lua or JavaScript) goes into a sandbox; Atrium does not invent one.
6. **The same gates.** Everything here ships with coverage and mutation score at 100%, in 15 languages, with contrast of 4.5:1 and never with colour as the only sign.

## What there is

What the code on `main` and in the open pull requests already gives an extension author.

| Piece | Where | What it gives | What it lacks for a platform |
|---|---|---|---|
| Modules as Composer packages | `Atrium\Admin\Modules`, [modules.md](modules.md#a-module-as-a-package) | `type: atrium-module`, the `extra.atrium` manifest (models, pages, migrations, demo, settings, permissions, submodules), `atrium:module:install` / `remove`, switching off and on | Installed from the console only |
| Catalogue of modules | **Modules** screen | Packages of type `atrium-module` from Packagist, alveby's first, with the commands that install them | Only modules; no templates or apps; no install button; no descriptions, screenshots or a "verified" mark |
| Hooks | `Atrium\Admin\Hooks\Hooks`, the `Hook` facade | Actions (`fire` / `on`, Laravel events by name), filters (`apply` / `filter`), registries (`register` / `registered` / `resolve`), slots of the site's templates | Events are bare strings with positional arguments: there is no list of them, no label, no payload. Nothing can offer them to a person (an automation rule) or to a subscriber (a webhook) |
| Record hooks | `AdminController`, `RecordWriter` | `atrium.record.data` (refuse with `Refused`), `atrium.record.saved`, `atrium.record.deleting`, `atrium.record.deleted` for every module, the panel, the API and MCP alike | Not named per module (`deal.created`), so a subscriber has to filter every save |
| Points in the panel | `atrium.module.fields`, `columns`, `filters`, `tabs`, `actions`, `bulk_actions`, `links`, `atrium.admin.menu` | A package adds fields, list columns, filters, tabs, buttons on a record and over checked records, buttons above a list, menu items to any module | Each is drawn by the panel's own components; a package brings no Vue of its own |
| HTML on a form | `DisplayField` | Server HTML (a Blade view) on the edit form, in any tab | Only on the form, only HTML |
| Dashboard gadgets | `Atrium\Admin\Dashboard\Gadget`, `atrium.dashboard.gadgets` | A tile with its data; the set comes from the config | The Vue components are a fixed map in `Dashboard.vue`: a package cannot add a gadget |
| Screens of a package | `packages/admin/resources/js/Pages` | Inertia pages | Only pages inside `packages/admin` are found (`import.meta.glob`) and the admin is a prebuilt bundle: the screens of backups, errors and monitoring live in the admin package for this reason |
| Slots of the site | `Hook::slot()`, `@atriumSlot` | HTML in nine places of the public theme | — |
| Embeds | `Atrium\Admin\Embeds`, [embeds.md](embeds.md) | A registry of things a text can embed (a form, a block) | — |
| Custom fields | `alveby/atrium-custom-fields`, [custom-fields.md](custom-fields.md) | Fields of 11 types added to any module from the admin, stored in the `custom` JSON column, filters, sorting, search, facets, own tabs ([#103](https://github.com/AlveBy/atrium/pull/103)), API and MCP | Only on modules that exist: no new entities; the types are a fixed enum |
| Ready-made sites | `alveby/atrium-solutions` | A folder with `solution.json` (modules, skin, menu, pages, palette) and its content in 15 languages, installed by `atrium:solution:install`; `Solutions::add()` registers one from a package | Sites, not CRM projects; from the console; nothing to export a project into one |
| Page templates | [#106](https://github.com/AlveBy/atrium/pull/106) | Blade templates for a record's public page, picked per record | — |
| Views of a list, kanban | [#116](https://github.com/AlveBy/atrium/pull/116) | `getAdministrationViews()` and the `atrium.module.views` filter, `Atrium\Admin\Views\View`; `KanbanView` by a select or a link field, cards moved with the mouse and saved through the form's own path; the chosen view per administrator | A calendar view |
| Calendar | next after [#116](https://github.com/AlveBy/atrium/pull/116), not in an open pull request yet | — | The third view, a `View` like the kanban |
| Activity log, versions | [#115](https://github.com/AlveBy/atrium/pull/115), [#117](https://github.com/AlveBy/atrium/pull/117) | Every save and delete of every module, from the form, the API, MCP, import and buttons, with who, from where and what changed (`admin_activity`); versions of every record, compare, restore, over the API and MCP too (`admin_revisions`) | — |
| Import and export | `Atrium\Admin\Transfer`, [import-export.md](import-export.md) | CSV and Excel for every module, a check before writing, the console, a schedule | — |
| API and tokens | `routes/api.php`, `ApiTokenController`, `/api/v1` | REST for every module (list, read, create, change, delete, actions, bulk actions, files), OpenAPI, tokens with abilities `{module}:read` and `{module}:write`, an MCP server | Tokens are made by a person for a person; no apps, no OAuth, no event list |
| Integrations | `alveby/atrium-integrations`, [integrations.md](integrations.md) | `IntegrationJob` with retries (10 s, 1 min, 5 min, 15 min), the exchange log (`integration_logs`, secrets masked), alerts and `atrium.integrations.failed`; Bitrix24 and 1C stand on it | No webhooks of its own, in or out: Bitrix24 keeps its own incoming address |
| "Market" [#100](https://github.com/AlveBy/atrium/pull/100) | `feat/market-feed` | A Yandex Market (YML) feed of the catalogue | Not an extension marketplace: the name is the only thing in common |

So the base is good for a programmer who writes a module: hooks, registries and the `atrium.module.*` filters reach almost every part of the panel from outside. Three things are missing for everybody else: a **list of events** that a person or another program can pick from, a way for a package to bring **its own interface** into the prebuilt admin, and **entities that are data** rather than code.

## The shape of the platform

```text
 4  Outside apps (any language)   OAuth with scopes · webhooks · REST · panels in a signed frame
 3  Plugins (PHP, Composer)        service provider · events · points in the panel · actions · field types
 2  Formulas and conditions        ExpressionLanguage: computed fields, conditions, defaults, visibility
 1  The builder (no code)          entities · fields · links · views · statuses · rules · roles · templates
 ─────────────────────────────────────────────────────────────────────────────────────────────────────
    Core contracts                 event catalogue · points in the panel · actions · field types · scopes
    What there is                  modules · hooks · custom fields · API · import/export · history · kanban
```

Levels 1 to 4 do not call each other: each of them talks to the core contracts, and the built-in features are registered there too.

## The core contracts

Four contracts are the public API of the platform. Their names and payloads follow semantic versioning: within 0.x a minor version may change them and says so in the changelog; from 1.0 on, fields are only added.

### The event catalogue

Every event a subscriber may hear is declared once, with a label and a payload. Modules keep firing hooks as they do now (`Hook::fire('atrium.tracker.task.status_changed', $task, $from, $to)`); the declaration says what that hook means to the outside.

```php
use Atrium\Admin\Extensions\Event;
use Atrium\Admin\Facades\Hook;

// In the service provider's boot(), while the module is installed:
Hook::register('atrium.events', 'tracker.task.status_changed', Event::make('atrium.tracker.task.status_changed')
    ->label('atrium-tracker::events.status_changed')   // text or a translation key
    ->subject(Task::class)                              // the first argument is the record
    ->payload(fn (Task $task, string $from, string $to): array => ['from' => $from, 'to' => $to]));
```

- The **key** (`tracker.task.status_changed`) is what people and programs see: in an automation rule, in a webhook subscription, in the API. The **hook** is the name the module fires.
- The **subject** is the record the event is about. It decides who may hear it: a webhook or an app gets the event only with read access to the subject's module, a rule only fires for records it can see.
- The **payload** is plain JSON data, built from the hook's arguments; it is what a webhook sends. It must not hold secrets.
- **Record events** are declared for every module by the core, so nothing has to be written for them: `{module}.created`, `{module}.updated`, `{module}.deleted` (`products.created`, `deals.updated`), from `atrium.record.saved` and `atrium.record.deleted`. An entity made in the builder gets them the moment it exists. Once the activity log ([#115](https://github.com/AlveBy/atrium/pull/115)) is in `main`, `updated` also carries the names of the fields that changed.
- When a declared hook fires, the catalogue turns it into an **occurrence** and fires the one action `atrium.event.occurred` with it. Automation rules, webhooks and apps listen to that action alone. An occurrence has an id (for idempotency), the key, the subject (module and id), the payload, the actor (a user, an app or nobody), the site and the time.

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

The catalogue is listed by `php artisan atrium:events` and, later, by `GET /api/v1/events` and on the screen of rules.

### Points in the panel

The panel already takes fields, columns, filters, tabs, record and bulk buttons, buttons above a list and menu items from any package (the `atrium.module.*` filters). Three points are added, and the built-in pieces move onto them:

| Point | Registry | Where it is drawn |
|---|---|---|
| Dashboard gadget | `atrium.ui.gadgets` | A tile on the start page; the built-in gadgets are registered here, `atrium.dashboard.gadgets` stays the layout |
| Record panel | `atrium.ui.panels` | A block beside or under a record's form, or a tab of its own, for one module or for all |
| Screen | `atrium.ui.pages` | A page of its own in the panel, with a menu item and a permission |

What such a point shows is one of three kinds of **content**:

- **HTML** from the server (a Blade view), as `DisplayField` does. Works without building anything.
- A **component**: a Vue component from the package's own prebuilt ES module. The admin hands Vue, Inertia, its components and translations to such modules (`window.Atrium`), so a package does not rebuild the admin; it publishes its file to `public/vendor/{package}` and names it in the registry `atrium.ui.scripts`.
- A **frame**: an outside app's page in an `<iframe>` with a signed context (see [level 4](#level-4-outside-apps-in-any-language)).

Each point takes a permission (`->permission('tracker.view')`), a condition (`->when(fn (Model $record, AdminUser $user): bool => …)`) and an order.

### Actions

What a rule may do is a registry, `atrium.automation.actions`, and the built-in actions are in it: change a field, move to a status, create a record, notify, send a webhook, run a record button (any `RecordAction` a module already has), wait. A plugin adds its own: a class with the settings the builder shows (fields, the same `Field` classes as a form) and `run(Occurrence $occurrence, array $settings)`.

### Field types

The custom field types are an enum today (`string`, `text`, `number`, `decimal`, `boolean`, `date`, `select`, `multiselect`, `link`, `file`, `image`). They become a registry, `atrium.fields.types`: each type says how its value is stored and normalised, which form field draws it, how it is filtered, sorted, imported, exported and described in the API. Money, a phone, a rating, a status, a formula, a link to a user are types added this way, the built-in ones included.

## Level 1. Without code: the builder

This is the main level. The bar is set by the smart processes of Bitrix24, by Airtable and by Notion. A **Builder** section in the admin, for a superadmin and for roles with `builder.manage`.

### Entities

An entity is a section of the panel made with the mouse: "Deals", "Candidates", "Objects", "Requests". It has a name in the content languages, a key, an icon, a place in the menu, and switches: sections (a tree), pages on the site, per site, translatable, SEO, files.

**Storage.** Each entity is a real table, `atrium_entity_{key}`, which the builder creates with the system columns: `id`, `site_id`, `name`, `status`, `sort`, `active`, `created_by`, `updated_by`, the dates and the `custom` JSON column. The person's fields are **custom fields**: the same storage, filters, facets, sorting, search and "hot" real columns as on a module in code. So the builder is "an empty module plus custom fields". A table per entity, not one table for all: its own indexes, its own size, an export or a drop is one table, and this is how Bitrix24 keeps smart processes.

**A class per entity.** The panel knows its modules by Eloquent class: routes, permissions, the morph map, the API and import read static methods of the class. The builder writes a two-line class per entity (`final class Deal extends Atrium\Builder\Entity {}`) into `bootstrap/cache/atrium-entities`, and an autoloader loads it; the file is made again from the database whenever it is missing (`php artisan atrium:entities:cache`). No `eval`. `Entity` implements `Administrable` and reads its fields, columns, tabs and views from the definition in the database (cached). From then on search, import and export, the API and MCP, the activity log and versions, the trash, the kanban and the permissions work for it as for any module, without a line of their own.

**Links.** A field may link to another entity or to a module in code (a product, a task, a user): one record (a column `{field}_id`) or many (one shared table `atrium_entity_links`: from, field, to). A linked record shows its counterpart's list in a tab.

**Lists, forms and views.**

- Columns: which, in which order, the default sort; saved views ("My deals", "Overdue") for a user or for a role.
- Forms: tabs and the order of fields with the mouse, required fields, hints, default values and visibility by a formula.
- Views: **table**, **kanban** by a status or a select field and **calendar** by a date field. They are the `View` classes of [#116](https://github.com/AlveBy/atrium/pull/116) (`KanbanView`, and a `CalendarView` to add), so a module in code gets the calendar too. The view is switched above the list.

### Statuses and transitions

A **status** field type: stages with a name, a colour and an icon (never colour alone), final stages marked as success or failure; which stage may go to which; which roles may move a record; which fields must be filled to enter a stage; what happens on entering one (a rule). The kanban's columns are the stages.

### Rules: when → if → do

A rule has a **trigger**, a **condition** and **steps**:

- **When**: an event from the catalogue (any module's, the builder's entities' own: `deals.created`, `deals.updated`, a status changed), on a schedule ("every Monday at 9:00"), or when a date field is reached ("3 days before `due_at`").
- **If**: a condition in the [formula language](#level-2-formulas-and-conditions): `record.amount > 10000 and record.status == "won"`.
- **Do**: steps from the actions registry, in order, with pauses ("in 2 hours").

Rules run on the queue, one at a time per record, and every run is written to a log with its outcome. A run is idempotent per occurrence; a rule does not start itself again deeper than a set number of levels; a rule can be tried on a chosen record without changing it.

**Robots that notify** are rules whose step is "notify": whom (a user, a role, the person in a field, the author), how (a notification in the panel, mail; Telegram and others are plugins) and what (a text with fields of the record).

### Roles

The permissions every module has (view, create, change, delete and its own) are there for an entity at once, in the role form. Two are added for every module, the builder's and the coded alike: **only own records** (by the author or by a "responsible" field) and **per field** (hidden or read-only for a role).

### Project templates

A template is a project in one file: entities, fields, links, views, statuses, rules, roles, the menu, the dashboard and, if wanted, sample records, with the packages it needs (`"requires": {"alveby/atrium-tracker": "^0.3"}`) and its texts in 15 languages. It is installed in one click, from the Market or from a file; a newer version shows what it will change before it does; removed, it asks whether to keep the data. A project made in the builder is exported as a template with one button, so the templates are made the same way as everything else. The first ones: sales pipeline, recruiting, service desk, a real estate agency, events.

A template is data, not code: it is installed without Composer and can be shared freely. It is published as a JSON file or as a Composer package of type `atrium-template` (the file and its translations).

### The Market

The **Modules** screen becomes the **Market**: one catalogue of modules, plugins, templates and apps, from Packagist (types `atrium-module`, `atrium-template`) and from an index on the Atrium site, which adds descriptions, screenshots and a "checked by Atrium" mark. Templates and apps are installed from the screen. A PHP package needs Composer, and running Composer from a web request is unsafe and fails on most hosting, so the screen keeps showing the commands, and a site that allows it (`atrium.market.composer`) gets a button that runs them in a queued job after a superadmin confirms with the second factor.

## Level 2. Formulas and conditions

**Decision**: [Symfony ExpressionLanguage](https://symfony.com/doc/current/components/expression_language.html). It is a ready, well-tested, sandboxed language: there are no assignments, loops or calls of PHP functions, only the variables and functions it is given, and parsed expressions are cached. Atrium does not write a language of its own.

Where formulas are used:

- **computed fields** (the `formula` field type, stored on save so that lists filter and sort by it): `record.price * record.quantity`, `days_between(record.start, record.end)`;
- **conditions** of rules, of stage transitions and of the visibility of fields;
- **default values**: `today() + days(14)`;
- **checks**: `record.end >= record.start`, with the message to show.

The variables are `record` (the fields by code), `old` (the values before the change), `user`, `site`, `now`. The functions are dates (`today`, `days`, `days_between`, `format_date`), text (`lower`, `upper`, `contains`, `concat`), numbers (`round`, `min`, `max`, `sum`), and checks (`has_role`, `in_stage`). A plugin adds functions to the registry `atrium.expressions.functions`.

Safety: an expression sees arrays and plain values, never objects (ExpressionLanguage would let it call their methods); its length is limited; an expression that fails is shown to the person who wrote it, not thrown at a visitor. The editor completes variables and fields, checks the expression on save and shows its value on a chosen record.

Scripts are not planned. If they are ever needed, a known language goes into a sandbox (Lua through LuaSandbox, or JavaScript through QuickJS), with limits on time and memory.

## Level 3. Plugins in PHP

A plugin is a Composer package of type `atrium-module`, as a module is: a module with models is a plugin, and a plugin may have no models at all. It is listed in the Market and installed as a module. Everything it adds is registered in its service provider's `boot()`, while it is installed, through the core contracts and the hooks:

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
        ->label('acme-time::panels.time')->html('acme-time::panels.task'));

    // A gadget, an action for rules and a function for formulas:
    Hook::register('atrium.ui.gadgets', 'time.week', WeekGadget::class);
    Hook::register('atrium.automation.actions', 'time.log', LogTime::class);
    Hook::register('atrium.expressions.functions', 'logged_hours', LoggedHours::class);
}
```

- The contracts (`Atrium\Admin\Extensions\*`) and the keys of the event catalogue are the public API. A plugin requires the version it was written for (`"alveby/atrium": "^0.3"`).
- Its interface is HTML from Blade, or Vue components from its own prebuilt module (see [content](#points-in-the-panel)). A Vite preset builds such a module with Vue and the admin left outside it.
- It is tested with Orchestra Testbench, as Atrium's packages are, with the same gates.
- A plugin is trusted code: it runs inside the site. This is why it is installed by a superadmin with Composer, and why the Market marks the ones Atrium has read.

## Level 4. Outside apps in any language

As Connect apps are for Jira: an app runs on its own server, in any language, and works with Atrium over HTTP only. It cannot break the site and sees only what it was allowed.

- **Registration.** In **Apps** a superadmin adds an app by the address of its manifest (`atrium-app.json`: name, icon, addresses, the scopes it asks for, the events it wants, its panels) or from the Market. The install screen lists what the app will see and do; it is installed only after a confirmation.
- **OAuth 2.0** with the authorization code and PKCE, tokens per installation. **Scopes** are the abilities tokens have now (`{module}:read`, `{module}:write`) plus `events:read`, `webhooks:write`, `ui:panels`. A token never gives more than the scopes, nor more than the user who installed it had.
- **Webhooks.** An app (or a person, in the admin) subscribes to keys of the catalogue. Each occurrence is sent as the JSON above by the queue of `alveby/atrium-integrations` (retries, the exchange log, alerts), signed: `X-Atrium-Signature: sha256=HMAC(secret, timestamp + "." + body)`, with the time and the occurrence id in headers, so that a replay and a double are seen. A subscription that fails a set number of times in a row is paused and its owner told. An event is sent only if the app may read its subject's module.
- **Panels in a frame.** An app's panel, gadget or screen is a frame on one of the [points in the panel](#points-in-the-panel). Its address gets a short-lived token signed with the app's secret (JWT, HS256, five minutes): the site, the user, the app, the place, the record (module and id), the language and the colour theme. The app checks it and calls the REST API with its own token. A small script (`atrium-app-bridge.js`) lets the frame change its height, show a message, open a page of the panel and ask the form to reload the record. The frame is sandboxed, and only the app's own origins are allowed by the panel's `frame-src`.
- **REST API.** What there is, plus `GET /events`, the subscriptions, `GET /me`; the builder's entities are in OpenAPI by themselves.

Apps that run on Atrium's servers, as Forge apps do on Atlassian's, are not planned: they would need a runtime in a sandbox and a hosting of our own.

## Security

| Level | Trust | What limits it |
|---|---|---|
| The builder | A person with `builder.manage` | Makes data only; the same permissions as any module; no code |
| Formulas | Anyone who may edit a rule or a field | ExpressionLanguage with given variables and functions only, no objects, a length limit |
| A PHP plugin | Full, like any package | Installed by a superadmin; the Market's mark; the version it requires |
| An outside app | None | OAuth scopes, the installing user's own permissions, signed webhooks, a sandboxed frame, signed context with a short life |

## Pull requests, in order

Each pull request is small and useful by itself, with its tests, its documentation and its 15 languages. The first ones are what Atrium Tracker (`packages/tracker`) needs for its plugins.

| # | Pull request | Useful at once because |
|---|---|---|
| 1 | This design (`docs/extensions.md`, `.ru.md`) | Everyone builds against the same plan |
| 2 | **Event catalogue**: `Event`, the `atrium.events` registry, record events for every module, `Occurrence`, `atrium.event.occurred`, `php artisan atrium:events` | The tracker declares its events; any package hears `deals.created` without filtering every save |
| 3 | **Gadgets from packages**: the `atrium.ui.gadgets` registry, HTML content, the built-in gadgets moved onto it | A package (the tracker's "My tasks", "Time this week") puts a tile on the dashboard |
| 4 | **Record panels**: `atrium.ui.panels`, beside or under the form or as a tab | A plugin shows its block on another module's record |
| 5 | **Components from packages**: `window.Atrium`, `atrium.ui.scripts`, the Vite preset, an example plugin | Plugins bring Vue without rebuilding the admin |
| 6 | **Screens from packages**: `atrium.ui.pages` with HTML or a component | Backups, errors, monitoring and the tracker keep their screens in their own packages |
| 7 | **Formulas**: an `Expressions` service on Symfony ExpressionLanguage, the functions registry, a check | Conditions and computed values for what follows |
| 8 | **Field types as a registry** in custom fields | Plugins add types; the status and formula types are built on it |
| 9 | **Outgoing webhooks**: subscriptions in the admin, signed sending through integrations | Any outside system hears the catalogue, before apps exist |
| 10 | **Builder core**: entity definitions, the table per entity, `Entity`, the generated classes, the menu | A person makes a section in the admin, with search, import, the API and history at once |
| 11 | **Links between entities** | Deals linked to companies and contacts |
| 12 | **Views**: saved views, kanban and calendar for entities | Pipelines and schedules without code |
| 13 | **Statuses and transitions** | Stages with rules of movement |
| 14 | **Rules**: triggers, conditions, the actions registry and its built-in steps, the queue, the log | "When → if → do" and robots that notify |
| 15 | **Own records and per-field permissions** | Sales people see their deals only |
| 16 | **Templates**: export and install of a project, update with a preview | A project in one click |
| 17 | **The Market**: one catalogue of modules, templates and apps, templates installed from the screen | Templates and apps reach a site without the console |
| 18 | **Apps**: registration by manifest, OAuth with scopes | Outside apps in any language |
| 19 | **Frames**: signed context, the bridge script | Apps put their panels into the admin |
