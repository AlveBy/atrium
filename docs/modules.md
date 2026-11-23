# Your own module

[English](modules.md) · [Русский](modules.ru.md)

A module is an Eloquent model that describes its admin form and list. Listed in `config/atrium.php` → `resources`, it gets everything else by itself: routes, a list with search and sorting, a form, a menu entry, permissions in the role form, the API and the MCP tools.

- [Start](#start)
- [Fields](#fields)
- [List columns](#list-columns)
- [Tabs](#tabs)
- [Validation and hooks](#validation-and-hooks)
- [Permissions](#permissions)
- [Content languages and sites](#content-languages-and-sites)
- [Pages on the site](#pages-on-the-site)
- [Interface translations](#interface-translations)
- [Tests](#tests)
- [A module as a package](#a-module-as-a-package)
- [Links to other modules](#links-to-other-modules)

## Start

```bash
php artisan atrium:make-module Article
php artisan migrate
```

This writes `app/Models/Article.php`, a migration for `articles` and a line in `config/atrium.php`. "Articles" appears in the menu at `/admin/articles`. `--label="News"` names it in the menu, `--no-migration` skips the migration.

The generated model is a starting point; everything in it can be changed:

```php
class Article extends Model implements Administrable
{
    use InteractsWithAdmin, Translatable;

    protected array $translatable = ['name', 'code', 'preview_text', 'detail_text'];

    protected $fillable = ['name', 'code', 'active', 'sort', 'preview_text', 'detail_text'];

    public static function getAdminLabel(): string
    {
        return 'Articles';
    }

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            SlugField::make('code')->label('Code')->from('name')->unique('articles', 'code'),
            CheckboxField::make('active')->label('Active')->default(true),
            NumberField::make('sort')->label('Sort')->default(500),
            ContentField::make('preview_text')->label('Preview text'),
            ContentField::make('detail_text')->label('Detail text'),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('name')->label('Name')->sortable()->searchable(),
            BooleanColumn::make('active')->label('Active'),
            DateColumn::make('updated_at')->label('Updated')->sortable(),
        ];
    }
}
```

A field writes the column of the same name, so every field needs a column in the migration and a place in `$fillable`.

## Fields

Every field is made with `::make('column')` and takes `label()`, `required()`, `hint()`, `default()`, `placeholder()`, `rules([...])`, `unique('table', 'column')`, `readonly()`, `tab()` and `hideOnCreate()` / `hideOnEdit()`.

| Field | For | Typical settings |
|---|---|---|
| `TextField`, `TextareaField` | Text | `minLength()` |
| `NumberField` | Numbers | `min()`, `max()`, `step()`, `decimals()`, `money()`, `suffix('kg')` |
| `CheckboxField` | Yes / no | `checkedValue('Y')` when the column keeps a code instead of a boolean |
| `SelectField` | One value from a list | `options([...])`, `fromEnum(Status::class)`, `fromModel(Brand::class)` |
| `MultiSelectField` | Several values from a list, kept as a JSON array | `options([...])` |
| `DateField`, `DateTimeField` | Dates | `minDate()`, `maxDate()` |
| `ColorField` | A colour | |
| `SlugField` | The page address | `from('name')`, `unique('table', 'code')` |
| `ContentField` | Text with a switch: plain text, HTML, visual editor | `modes(['text', 'html'])`, `defaultMode('html')` |
| `WysiwygField` | Visual editor only | |
| `ImageField`, `FileField` | One file | `directory('articles')`, `maxSize(2048)`, `acceptedTypes([...])` |
| `GalleryField` | Several pictures | `maxFiles(20)`, `sortable()` |
| `RepeatableField` | A list of values: dates, phones, links | `itemType('date')`, `maxItems(20)`, `addLabel('Add a date')` |
| `BelongsToField` | A link to a record of another module (`brand_id`) | `model(Brand::class)` |
| `BelongsToManyField` | Links to several records; the model needs the `belongsToMany` relation of the same name | `model(Tag::class)`, `sortable()` |
| `TreeSelectField` | A section from a tree | `model(Section::class)`, `parentColumn('parent_id')`, `multiple()` |
| `TreeField` | A tree of items with fields of their own, edited by dragging (the menus module's items). The value is a nested array with `children`: a JSON column, or the model writes it in `afterAdminSave()` | `fields([...])`, `titleField('title')`, `maxDepth(4)`, `maxItems(500)` |
| `LinkField` | A link: an address, or a record of any module with pages chosen by name. The value is `{"type": "url", "url": …}` or `{"type": "record", "model": …, "id": …}` | `urlsOnly()` |
| `MapField` | A point on a map | `yandex()` or `google()`, `defaultCenter()`, `defaultZoom()` |
| `DisplayField` | Read-only HTML drawn from the record on the edit form: order lines, a summary, a link | `html(fn (Model $record): string => view('…')->render())`; not on the create form, never submitted |

The demo shop's product (`packages/catalog/src/Models/Product.php`) uses nearly all of them.

## List columns

`TextColumn`, `BooleanColumn`, `DateColumn` and `ImageColumn`, each with `label()`, `sortable()`, `searchable()`, `width()` and `alignRight()`. The search box looks in every `searchable()` column.

The default order:

```php
public static function getDefaultSortColumn(): string
{
    return 'sort';
}

public static function getDefaultSortDirection(): string
{
    return 'asc';
}
```

To narrow the list or load relations, override `scopeAdminList(Builder $query)`.

A column may show an attribute of a related record: `TextColumn::make('brand.name')` loads the `brand` relation for the page and shows the brand's name. Such a column is neither sorted nor searched by.

A column may bring its own value, and be shown only to those with a permission. That is how another module adds a column to your list without touching it:

```php
Hook::filter('atrium.module.columns', fn (array $columns, string $model) => $model === Customer::class ? [
    ...$columns,
    TextColumn::make('orders.count')->label(__('shop::orders.count'))
        ->value(fn (Customer $customer) => $customer->orders()->count())
        ->permission('orders.view'),
] : $columns);
```

`value()` gets the record and returns what the cell shows; a column with it is neither sorted nor searched by. `permission()` takes a bare action of the module (`view`) or a full name (`orders.view`); without it the column is dropped from the list, and from its export too.

A button on a record, in its list row and on its form, opens the create form filled from it. A subpage gets the page as its parent:

```php
use Atrium\Admin\Actions\CreateFromRecord;

public static function getAdministrationActions(): array
{
    return [
        CreateFromRecord::make('subpage')->label('Subpage')->fill(['parent_id' => 'id']),
    ];
}
```

`fill()` maps form fields to attributes of the record: the button opens `/admin/pages/create?parent_id=12`, and only the fields it names are taken from the address. Administrators who may not create records here do not see it.

### Buttons on a record and over selected records

The same list takes two more kinds of record buttons. A **link** leads anywhere, another site included; a **server action** runs a class on the record:

```php
use Atrium\Admin\Actions\ActionResult;
use Atrium\Admin\Actions\LinkAction;
use Atrium\Admin\Actions\RecordAction;
use Illuminate\Database\Eloquent\Model;

public static function getAdministrationActions(): array
{
    return [
        LinkAction::make('preview')->label('On the site')->url(fn (Article $article) => $article->getPublicUrl())->newTab(),
        Publish::make('publish')->label('Publish')->confirm('Publish it now?'),
    ];
}

final class Publish extends RecordAction
{
    public function handle(Model $record): ActionResult
    {
        if ($record->published_at !== null) {
            return ActionResult::error('Already published.');
        }

        $record->update(['published_at' => now()]);

        return ActionResult::success('Published.'); // or ActionResult::redirect($url)
    }
}
```

A list gets checkboxes, with one in the header for the whole page, once it has a bulk action. Checking rows shows a bar above the list with their number and the buttons. **Delete** is always there for those who may delete; a module adds its own:

```php
use Atrium\Admin\Actions\BulkAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

public static function getAdministrationBulkActions(): array
{
    return [Archive::make('archive')->label('Archive')->confirm()];
}

final class Archive extends BulkAction implements ShouldQueue
{
    public function handle(Collection $records): ActionResult
    {
        $records->each->update(['archived' => true]);

        return ActionResult::success();
    }
}
```

`handle()` gets only the checked records the administrator may see in the current site (the module's `scopeAdminList()` and site applied), at most 1000. An action that implements `ShouldQueue` goes to the queue and the administrator is told it has started; the job runs in the same site. `confirm()` asks first, with your question or "Are you sure?".

Each button needs a permission: links `view`, the others `update`, Delete `delete`. `permission('publish')` makes it `{module}.publish` (declare it in the manifest, see "Permissions"), and `permission('menus.update')` names one of another module. An administrator without it does not see the button, and the server answers 403. The panel finds the button by its name, so it runs only what the module lists.

Other modules add buttons with the `atrium.module.actions` and `atrium.module.bulk_actions` hooks (see "Hooks"). Register them in the service provider only while the module is installed: switched off, its buttons are gone. The menus module puts **Add to header menu** on every record that has a page on the site this way:

```php
Hook::filter('atrium.module.actions', fn (array $actions, string $model) => app(RecordUrls::class)->hasPages($model)
    ? [...$actions, AddToHeaderMenu::make('add-to-header-menu')->label(__('...'))->permission('menus.update')]
    : $actions);
```

The same server actions are in the API and MCP: `GET /api/v1/{module}/actions`, `POST /api/v1/{module}/{id}/actions/{name}`, `POST /api/v1/{module}/bulk/{name}` with `{"ids": [...]}`; the tools `list_actions`, `run_record_action`, `run_bulk_action`. An action whose permission is a `view` needs a reading token, any other a writing one. Links and filled create forms are the panel's own and are not there.

### Filters above the list

A module returns its filters from `getAdministrationFilters()`; each is drawn above the list, next to the search, and the list, its export, the API's `filter[name]=` and MCP's `list_records` narrow by it alike:

```php
use Atrium\Admin\Filters\BooleanFilter;
use Atrium\Admin\Filters\SelectFilter;

public static function getAdministrationFilters(): array
{
    return [
        BooleanFilter::make('active')->label('Active'),
        SelectFilter::make('brand_id')->label('Brand')
            ->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id')->all()),
    ];
}
```

The name is the filter's key in the address (`/admin/products?brand_id=4`). A value that is not one of the filter's own (`?active=maybe`, a brand that is not in the options) is no filter at all. Without `query()` a filter is a condition on the column of its name, and `atrium.query.filter` may take it over as before; with it, the filter narrows its own way:

```php
SelectFilter::make('stock')->label('Stock')
    ->options(['out' => 'Out of stock', 'low' => 'Fewer than 5'])
    ->query(fn (Builder $query, string $value) => $value === 'out' ? $query->where('stock', 0) : $query->whereBetween('stock', [1, 4])),
```

Other modules add theirs through `atrium.module.filters`. A filter with `permission()` is not drawn, applied or described to someone without it, in the API and MCP by the token owner's rights. A bare name in the list (`'module'`) is still a filter of the address that is kept but not drawn.

## Tabs

Add `HasAdminTabs`, list the tabs and put each field on one with `tab()`:

```php
use HasAdminTabs;

public static function getAdminTabs(): array
{
    return [
        ['name' => 'params', 'label' => 'Main'],
        ['name' => 'detail', 'label' => 'Detail'],
        ['name' => 'seo', 'label' => 'SEO'],
    ];
}

// in getAdministrationFields():
TextField::make('name')->label('Name')->required()->tab('params'),
ContentField::make('detail_text')->label('Detail text')->tab('detail'),
```

A field without `tab()` goes to `params`. `HasAdminSeo` adds the meta title, description and keywords to the `seo` tab by itself.

Another module puts its fields on a tab of its own: it adds the fields through `atrium.module.fields` with `tab('shop')`, and the tab through `atrium.module.tabs`. A tab with no fields is not shown, so the tab goes away with the module's fields:

```php
use Atrium\Admin\Tabs\Tab;

Hook::filter('atrium.module.tabs', fn (array $tabs, string $model) => $model === Product::class
    ? [...$tabs, Tab::make('shop')->label(__('shop::tabs.shop'))->order(50)]
    : $tabs);
```

## Validation and hooks

Rules come from the fields (`required()`, `unique()`, `rules()`). To replace them as a whole, return your own from `getCreateValidationRules()` and `getUpdateValidationRules()`.

`InteractsWithAdmin` has methods to override where the defaults do not fit:

| Method | When |
|---|---|
| `transformAdminData(array $data): array` | Before saving: change what the form sent |
| `afterAdminSave(array $data): void` | After saving, in the same transaction: related records, cache |
| `beforeAdminDelete(): bool` | Before deleting, in the same transaction; `false` cancels |
| `canEdit(): bool`, `canDelete(): bool` | Whether this record may be edited or deleted |
| `isCreatableInAdmin(): bool`, `isEditableInAdmin(): bool` (static) | `false` for records that come from elsewhere, such as form submissions: no "Add" or "Save", the routes and the API answer 403 |
| `isImportableInAdmin(): bool`, `isExportableInAdmin(): bool` (static) | `false` to take the module out of the CSV and Excel import or export; by default every module can be exported, and imported when its records can be created and edited. `getImportKeys(): array` lists the columns that find a record again. See [Import and export](import-export.md#for-module-authors) |

## Permissions

Every module gets four permissions by its key: `articles.view`, `articles.create`, `articles.update`, `articles.delete`, and `articles.import` and `articles.export` when the module can be imported and exported. They appear in the role form by themselves; the menu, the buttons, the API and the MCP tools follow them. Superadmins have them all.

A module package that checks more than that declares its own permissions in the manifest:

```json
"extra": {
    "atrium": {
        "permissions": {
            "refund": "atrium-shop::permissions.refund",
            "orders.export": {"label": "atrium-shop::permissions.export", "with": "orders.view"}
        }
    }
}
```

A name is `{section}.{action}`; a bare action belongs to the module's key (`refund` → `shop.refund`). The label is text or a translation key. While the module is installed, each permission is a checkbox in its section's row of the role form (in the "Other" column, or a row of its own), and the module checks it with `$user->hasAdminPermission('shop.refund')`. `with` names a permission it comes with: when the module is installed, every role that has `orders.view` gets `orders.export` too. `atrium:module:remove` takes them out of the roles.

## Content languages and sites

**Languages.** With `Translatable` and the columns listed in `$translatable`, the form gets a language switch and each value is kept per language. The columns must be JSON (`$table->json('name')`). Only the default language is required; the others fall back to it.

**Sites.** With `BelongsToSite` and a `site_id` column, the module becomes per-site: the list, the form, search and the public pages see only the current site's records, and a new record gets the current site. Without it the module is shared by all sites.

```php
$table->foreignId('site_id')->nullable()->index();
```

## Pages on the site

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

gives each article the address `/articles/{code}`: the slug field shows it under the code, the API returns it as `public_url`, and the public theme (`alveby/atrium-theme`) draws the page from the module's form. Records in sections and the section pages themselves are in the comment above `public_urls` in `config/atrium.php`.

To put the module's pages into `sitemap.xml`, implement `Atrium\Admin\Contracts\Sitemapable`: `sitemapQuery()` returns the records that belong there, `sitemapUrl($language)` the address of one.

With the SEO module installed, the module's pages also get titles from templates, hreflang and schema.org markup. A module adds its own markup (an `Event`, a `JobPosting`) with a `SchemaProvider`; see [SEO: markup for your own module](seo.md#markup-for-your-own-module).

## Captcha in a public form

A form on the site asks the site's captcha (`ATRIUM_CAPTCHA`, see [Configuring Atrium](configuration.md#captcha)) with a component and a rule; while the captcha is off both are empty:

```blade
<form method="post" action="...">
    @csrf
    ...
    <x-atrium::captcha />
    @error('captcha') <p>{{ $message }}</p> @enderror
</form>
```

```php
use Atrium\Admin\Captcha\CaptchaManager;

$request->validate([
    'text' => ['required', 'string'],
    ...app(CaptchaManager::class)->rules(),
]);
```

Leave the captcha's fields out of what goes back into the form: `->withInput($request->except(CaptchaManager::INPUTS))`. A captcha of another kind (Cloudflare Turnstile, hCaptcha, your own) is a class with `widget()` and `verify($request)` (`Atrium\Admin\Captcha\CaptchaDriver`), added with `app(CaptchaManager::class)->extend('turnstile', fn () => new Turnstile(...))` and chosen with `ATRIUM_CAPTCHA=turnstile`.

## Cache

Public pages draw records from the record cache. Add `implements Cacheable` and `use CachesRecords` to the model, and read lists through `->cachedIds()` / `->cachedPaginate()` / `->cachedCards()` instead of `->get()`: a list then asks the database only for IDs, and each record comes from its card, one multi-get for a whole page. Saves through Eloquent drop what they touch; code that writes with `DB::table()` calls `app(RecordCache::class)->flushClass(Article::class)`, and an import runs inside `bulk()`. Everything else, step by step: [cache.md](cache.md).

## Interface translations

Labels may be plain text or translation keys. For a module that is used in more than one language, keep them in `lang/{language}/admin.php` and write `->label(__('admin.articles.name'))`.

## The frame of the panel

Sidebar, header, flash messages and the AI helper are the layout of every admin page (`Layouts/AdminShell.vue`, set once as the default `layout` of the app). Inertia builds it once and keeps it between visits, so the menu keeps its scroll and its open groups, the helper its open chat, the header its site and language. A page is only its own content and its title:

```vue
<script setup>
import { Head } from '@inertiajs/vue3'
</script>

<template>
    <Head :title="title" />
    <h1>{{ title }}</h1>
</template>
```

The pages under `Pages/Admin/Auth/` (signing in) have no frame. Any other page can choose its own with `defineOptions({ layout: … })`, or none with `layout: false`.

**Upgrading.** A page that still wraps itself in `<AdminLayout :title="…">…</AdminLayout>` needs no change: inside the frame `AdminLayout` only sets the title, so there is one frame, not two. Nothing is rebuilt around it; only the thin wrapper itself is. Move the title into `<Head>` and drop the wrapper when you next touch the page. `AdminLayout` stays for compatibility; a page that opted out of the default layout and wraps itself still gets a whole frame from it.

## Tests

The admin's routes are named after the module key (`admin.articles.index`, `admin.articles.store`, …), so a feature test signs an administrator in and posts the form:

```php
use Atrium\Admin\Models\AdminUser;

public function test_an_editor_creates_an_article(): void
{
    $admin = AdminUser::query()->create([
        'name' => 'Editor',
        'email' => 'editor@example.com',
        'password' => 'a long password',
        'is_active' => true,
    ]);
    $admin->forceFill(['is_superadmin' => true])->save();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.articles.store'), ['name' => 'Hello', 'code' => 'hello', 'active' => true])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('articles', ['code' => 'hello']);
}
```

Atrium's own tests (`packages/admin/tests`) show more: permissions, the API, browser tests with Dusk.

## A module as a package

A module for other sites is a Composer package of type `atrium-module`. To start one, run:

```bash
php artisan atrium:make-package acme/atrium-reviews
```

This creates `packages/reviews` from the template in `vendor/alveby/atrium/stubs/module`. It holds the manifest, a translatable model with its admin form and list that already uses the [record cache](cache.md), its migration, a demo seeder, English labels, Testbench tests that install the module in a bare Atrium, a GitHub Actions workflow for PHP 8.3 and 8.4, a README and an MIT licence. The tests pass as generated. `--model=Review`, `--namespace=Acme\\Reviews`, `--title=Reviews` and `--path=` change the defaults. The command prints the three commands that try the package in the current project: a Composer path repository, `composer require acme/atrium-reviews:@dev`, and `atrium:module:install --demo`. To publish the package, give it a repository of its own and submit it to Packagist; it then shows up in every site's catalogue (see [The Modules screen](#the-modules-screen)). Your files are yours: unlike Atrium's own, they need no Atrium copyright header.

The `composer.json` says what the module brings, in `extra.atrium`:

```json
{
    "name": "vendor/atrium-reviews",
    "type": "atrium-module",
    "require": {
        "php": "^8.3",
        "alveby/atrium": "^0.1"
    },
    "suggest": {
        "alveby/atrium-catalog": "Reviews of products"
    },
    "extra": {
        "atrium": {
            "resources": ["Vendor\\Reviews\\Review"],
            "public_urls": {
                "Vendor\\Reviews\\Review": {"prefix": "reviews"}
            },
            "migrations": "database/migrations",
            "demo": "Vendor\\Reviews\\Database\\ReviewsDemoSeeder"
        },
        "laravel": {
            "providers": ["Vendor\\Reviews\\ReviewsServiceProvider"]
        }
    }
}
```

| Key | What it is |
|---|---|
| `resources` | The module's models, in menu order: the same as lines in `atrium.resources` |
| `public_urls` | Their pages on the site, as in `atrium.public_urls`; a rule the site sets for the same model wins |
| `migrations` | A folder (or a list of them) relative to the package |
| `search` | What the search module (`alveby/atrium-search`) reads in the models: `{"Vendor\\Reviews\\Review": {"title": "name", "content": ["text"]}}`, or `false` to leave a model out. Without it, a model with pages is found by `name` and the text fields of its form |
| `demo` | A seeder for `atrium:install --demo` and `atrium:module:install --demo`, best extending `Atrium\Admin\Modules\DemoSeeder` (it copies the package's pictures to the uploads disk). Its `run()` returns `false` when the module has content already and adds nothing then |
| `key`, `title` | The module's key in permissions and settings, and its name in the panel (see "Module settings") |
| `settings` | Its settings class (see "Module settings") |
| `permissions` | Its own permissions beyond view, create, update and delete (see "Permissions") |
| `submodules`, `parent` | The submodules it lists, or the module it is a submodule of (see "Submodules") |

Every key is optional. A record's page, whichever module draws it, is `app(Atrium\Admin\Support\RecordUrls::class)->url($record, 'de')` (`null` if it has none); the theme answers for `public_urls`, and a module that draws pages of its own adds an answer with `RecordUrls::extend()`. Menus and search link to records through it. `suggest` lists the modules this one links to softly (see below); their demo content is filled in first.

Two commands, after `composer require` and before `composer remove`:

```bash
composer require vendor/atrium-reviews
php artisan atrium:module:install vendor/atrium-reviews

php artisan atrium:module:remove vendor/atrium-reviews
composer remove vendor/atrium-reviews
```

`atrium:module:install` runs the module's migrations and adds it to the `modules` list in `config/atrium.php`; from then on its models are in the menu, the role form, the API and MCP, its migrations run with `php artisan migrate`, its pages are on the site. Composer is not run: the package must be required already. `--demo` fills it with its demo content.

`atrium:module:remove` asks whether to keep the data. If not, the module's migrations are rolled back and its tables dropped; either way its permissions leave the roles and it leaves the list. `--keep-data` and `--delete-data` answer the question in scripts. Installed again, a module with kept data has its records back.

A module that is required but not installed is nowhere in the panel, even if its service provider pushes its models to `atrium.resources`. So the provider needs only what the manifest does not cover:

```php
public function boot(): void
{
    $this->loadTranslationsFrom(__DIR__.'/../lang', 'reviews');
}
```

Leave the migrations to the manifest: loaded with `loadMigrationsFrom()`, they would run for a module that is not installed and bring back the tables of a removed one. Cached config and routes are rebuilt by both commands.

Test it with Orchestra Testbench, as the modules in `packages/` do: list the module in `atrium.modules` in `defineEnvironment()`, and its models, migrations and pages are there as on a site.

### Submodules

A module may have submodules: a shop with its delivery and payment methods. Each is a package of its own, with its own manifest, models, migrations, settings and permissions. The parent lists the ones it knows; a submodule from someone else names its parent:

```json
"extra": {"atrium": {"submodules": ["vendor/atrium-shop-delivery", "vendor/atrium-shop-payment"]}}
```

```json
"extra": {"atrium": {"parent": "vendor/atrium-shop"}}
```

`atrium:module:install vendor/atrium-shop` installs every submodule of it that Composer has, after it. A submodule can be installed on its own once its parent is. `atrium:module:remove vendor/atrium-shop` removes its submodules first, with the same answer about the data; a submodule removed on its own leaves its parent. The **Modules** screen lists the submodules under their parent, and their demo content is filled in after the parent's.

### The Modules screen

**Modules** in the menu lists the installed modules, with a button to the settings of each one that has any. A superadmin sees more there:

- **Switch off** and **Switch on**. A module that is switched off keeps its tables, records and settings, but everything else treats it as not installed: its models leave the menu, the role form and the API, and its pages leave the site. `Modules::isInstalled()` is false for it, so a service provider that checks it needs no change. Switching a module off switches off its submodules too, and switching it on brings them back. A submodule can't be switched on while its parent is off. Switching on runs any migrations the module got while it was off. The list is kept in `atrium.disabled_modules` in config/atrium.php. `atrium:module:install` switches a module back on, and `atrium:module:remove` also removes a module that is off.
- **Required, not installed**: the packages Composer has but `atrium.modules` doesn't list, each shown with the command that installs it.
- **Catalogue**: the Atrium modules published on Packagist, with packages of type `atrium-module` or the keyword `atrium-module`. alveby's come first, then the most downloaded. Each shows whether it is installed, off, required or available, and the commands that add it (`composer require` and `atrium:module:install`). Packagist is asked at most every six hours, after the page has loaded. When it doesn't answer, the screen says so.

Publishing a module of your own: set `"type": "atrium-module"` in its composer.json, or add `atrium-module` to `keywords`, and submit it to Packagist. It shows up in the catalogue of every site.

## Module settings

A module that needs something set by the site's owner — a currency, the number of products on a page, an address for notices — names a settings class in its manifest:

```json
"extra": {
    "atrium": {
        "key": "shop",
        "title": "atrium-shop::shop.title",
        "settings": "Vendor\\Shop\\ShopSettings"
    }
}
```

The class extends `Atrium\Admin\Modules\ModuleSettings` and lists its fields with the admin's own field types, rules and defaults included:

```php
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Modules\ModuleSettings;

final class ShopSettings extends ModuleSettings
{
    public bool $perSite = true;

    public function fields(): array
    {
        return [
            SelectField::make('currency')->label(__('atrium-shop::settings.currency'))
                ->options(['EUR' => 'EUR', 'USD' => 'USD'])->default('EUR')->required(),
            NumberField::make('free_delivery_from')->label(__('atrium-shop::settings.free_delivery_from'))
                ->rules(['nullable', 'integer', 'min:0']),
        ];
    }
}
```

Read a value anywhere with the `Setting` facade:

```php
use Atrium\Admin\Facades\Setting;

Setting::get('shop.currency');              // the current site's value, or the field's default
Setting::get('shop.free_delivery_from', 0); // 0 when nobody set it and the field has no default
Setting::get('shop.currency', null, $site); // another site (a Site or its id)
Setting::all('shop');                       // every field by name
```

A field nobody filled in gives its `default()`, then the second argument. A module that is not installed has no settings: `get()` gives the second argument. The values are kept in the `admin_module_settings` table and read once per request.

`$perSite = true` keeps a set of values for every site, set on the screen of the site chosen in the panel's header; `false` (the default) is one set for all sites. `description()` returns a line shown under the screen's title.

The panel lists the installed modules under **Modules** in the menu, with a **Settings** button for those that have them. Settings need the `{key}.settings` permission (`shop.settings`), which the role form shows in the module's row; superadmins have it. `atrium:module:remove` takes it out of the roles and, when the data is dropped, drops the settings too.

`key` in the manifest is the module's key in permissions and in `Setting::get()`; without it, the package name without `atrium-` (`vendor/atrium-shop` → `shop`). `title` is its name in the panel (a translation key or text).

The catalog has one: `per_page`, products on a page of a section, per site (`packages/catalog/src/CatalogSettings.php`); the theme reads it with `Setting::get('catalog.per_page')`.

## Hooks

Modules meet at hooks, named points where one module tells the others what happened or lets them change a value. They never call each other directly, so either one may be missing.

An **action** says that something happened. Every listener is called with the arguments, and nothing comes back:

```php
use Atrium\Admin\Facades\Hook;

// The shop, when an order is paid:
Hook::fire('atrium.order.paid', $order);

// Any module, in its service provider's boot():
Hook::on('atrium.order.paid', fn (Order $order) => $order->customer->notify(new OrderPaid($order)));
Hook::on('atrium.order.paid', SendReceipt::class); // a class with handle(), may implement ShouldQueue
```

Actions are Laravel events named by a string, so `Event::listen('atrium.order.paid', ...)` is the same as `Hook::on()`, and a queued listener runs on the queue.

A **filter** lets modules change a value. Each callback gets what the one before returned and the hook's other arguments, and returns the value:

```php
// The search module asks which sources there are:
$sources = Hook::apply('atrium.search.sources', [], $site);

// A module adds its own:
Hook::filter('atrium.search.sources', fn (array $sources) => [...$sources, ProductSource::class]);
Hook::filter('atrium.search.sources', DropDrafts::class, 20); // an invokable class, later than the default 10
```

Lower priority runs first; the default is 10. `Hook::has('atrium.order.paid')` says whether anything listens. A hook nobody listens to costs one array lookup, so fire freely.

Name a hook `atrium.{module}.{what}`: actions in the past tense (`atrium.order.paid`), filters by the value they filter (`atrium.menu.items`). Register listeners only when your module is installed (`app(Modules::class)->isInstalled('vendor/atrium-shop')`), and list the hooks your module fires in its README, with their arguments.

The core fires these:

| Hook | Kind | Arguments | When |
|---|---|---|---|
| `atrium.record.data` | filter | `array $data`, `Model $record`, `string $context` (`create` or `edit`) | A record is saved in the panel, through the API or MCP, after `transformAdminData()` and before `fill()`; throw `Refused` to keep it unsaved |
| `atrium.record.saved` | action | `Model $record`, `string $context` | After it is saved, its relations synced and `afterAdminSave()` run, once the transaction is committed |
| `atrium.record.deleting` | action | `Model $record` | Before a record is deleted in the panel, through the API, MCP or the bulk **Delete**, inside the transaction and before `beforeAdminDelete()`; throw `Refused` to keep it |
| `atrium.record.deleted` | action | `Model $record` | After a record is deleted in the panel or through the API, once the transaction is committed |
| `atrium.admin.menu` | filter | `array $items`, `?AdminUser $user` | The panel's menu is built; an item is `['label' => ..., 'route' => ..., 'icon' => ...]`, and items the user may not open are dropped afterwards |
| `atrium.record.present` | filter | `array $data`, `Model $record`, `string $context` (`form` or `list`) | A record is handed to the edit form or to a list row, the API's included |
| `atrium.module.fields` | filter | `array $fields`, `string $model` | A module's form fields are read: the form, validation, the API and its documentation, MCP |
| `atrium.module.columns` | filter | `array $columns`, `string $model` | A module's list columns are read, in the panel and for the API's `search=`; a `Column` may carry `value()` and `permission()` (see "List columns") |
| `atrium.module.filters` | filter | `array $filters`, `string $model` | A module's list filters are read: the list, its export, the API's `filter[]=` and MCP; `SelectFilter`, `BooleanFilter` or a bare name (see "Filters above the list") |
| `atrium.module.tabs` | filter | `array $tabs`, `string $model` | A module's form tabs are read; add a `Tab` for the fields you add with `tab()` (see "Tabs") |
| `atrium.module.translatable` | filter | `array $names`, `string $model` | The names of a module's fields that hold a value per content language |
| `atrium.module.links` | filter | `array $links`, `string $model` | Buttons above a module's list: `['name' => ..., 'label' => ..., 'href' => ...]` |
| `atrium.module.actions` | filter | `array $actions`, `string $model` | Buttons on a record of a module, in its list row and on its form: `CreateFromRecord`, `LinkAction`, `RecordAction` (see "List columns") |
| `atrium.module.bulk_actions` | filter | `array $actions`, `string $model` | Buttons over the records checked in a module's list, and in the API: `BulkAction`; the built-in Delete comes first |
| `atrium.query.filter` | filter | `bool $handled`, `Builder $query`, `string $name`, `mixed $value` | A list or the API filters by a name; return `true` once you have added the condition yourself |
| `atrium.query.sort` | filter | `bool $handled`, `Builder $query`, `string $name`, `string $direction` | The same for sorting |
| `atrium.query.search` | filter | `bool $handled`, `Builder $query`, `string $name`, `string $search` | The same for one searchable column, inside the search's `or` group |
| `atrium.module.installed` | action | `Module $module` | `atrium:module:install` has run its migrations and listed it |
| `atrium.module.removed` | action | `string $package`, `bool $dataDropped` | `atrium:module:remove` has taken it out |
| `atrium.module.enabled` | action | `Module $module` | a superadmin has switched it on |
| `atrium.module.disabled` | action | `Module $module` | a superadmin has switched it off |

### Saying no before a save or a delete

`atrium.record.data` and `atrium.record.deleting` run before anything is written, so a module may refuse there: throw `Atrium\Admin\Hooks\Refused` with a message for the person who pressed the button.

```php
use Atrium\Admin\Hooks\Refused;

// Nothing is saved: the form shows the message under the field, the API answers 422.
Hook::filter('atrium.record.data', function (array $data, Model $record, string $context): array {
    if ($record instanceof Product && $context === 'create' && Product::query()->where('sku', $data['sku'] ?? null)->exists()) {
        throw new Refused(__('shop::products.sku_taken'), 'sku');
    }

    return $data;
});

// The record stays: the list shows the message, the API answers 409.
Hook::on('atrium.record.deleting', function (Model $record): void {
    if ($record instanceof Customer && $record->orders()->open()->exists()) {
        throw new Refused(__('shop::customers.has_open_orders'));
    }
});
```

Without a field the panel shows the message above the form. MCP returns the message to the assistant as the API does, and the bulk **Delete** keeps the refused records and adds each reason once to its message. `atrium.record.deleting` runs inside the delete's transaction, so what its listener writes is rolled back with a refusal. Any other exception is an error, not a refusal.

The record hooks see only what the panel and the API save; a seeder or an import that writes with Eloquent does not fire them (listen to Eloquent's own events for that).

### Slots in the site's templates

A slot is a place in the public theme where modules add their HTML: a banner over the header, a chat button before `</body>`, a subscription form under every page. The theme's frame (`atrium-theme::layout`) has these, in the order of the page, with every skin:

| Slot | Where |
|---|---|
| `body.start` | right after `<body>` and the site's counters, before the skip link |
| `header.start` / `header.end` | inside the header, before the site's name / after the language and theme switches |
| `content.before` / `content.after` | inside `<main>`, around the page's content, under the breadcrumbs |
| `footer.start` / `footer.end` | inside the footer, before / after the theme's or the skin's footer |
| `body.end` | before `</body>`, after the site's code for the end of the page |

`<head>` is the `atrium.head` filter (see the table above).

```php
// In the service provider's boot(), only while the module is installed:
Hook::slot('footer.end', 'shop::partials.cart-button');            // a view
Hook::slot('body.start', fn (array $page): string => '<div class="promo">…</div>', 5); // or a closure
```

The view, or the closure, gets the page's variables: `$siteName`, `$homeUrl`, `$skin`, `$breadcrumbs`, and on a record's page `$page` with its `record`. Lower priority comes first, the default is 10. A slot is the filter `atrium.slot.{name}` of an HTML string, so `Hook::filter('atrium.slot.footer.end', ...)` may change or drop what others put there. A theme of your own marks its places with `@atriumSlot('footer.end')`: name new ones the same way, `{area}.{start|end|before|after}`.

## Links to other modules

Modules link to each other softly: products work without brands, and the "Brand" field and column appear only when the Brands module is installed. Nothing in Composer's `require` makes one module install another. The demo shop is built this way: `alveby/atrium-catalog` links to `alveby/atrium-brands` and `alveby/atrium-stores` (`packages/catalog/src/Models/Product.php`).

**Fields, columns, tabs.** `Atrium\Admin\Modules\Soft::when()` returns the items when the model is available — its class is there and its module installed — and an empty list otherwise. Spread it into the list:

```php
use Atrium\Admin\Modules\Soft;
use Atrium\Brands\Models\Brand;

public static function getAdministrationFields(): array
{
    return [
        TextField::make('name')->label('Name')->required(),
        ...Soft::when(Brand::class, fn () => [
            BelongsToField::make('brand_id')->label('Brand')->model(Brand::class),
        ]),
    ];
}

public static function getAdministrationListColumns(): array
{
    return [
        TextColumn::make('name')->label('Name')->sortable()->searchable(),
        ...Soft::when(Brand::class, fn () => [
            TextColumn::make('brand.name')->label('Brand'),
        ]),
    ];
}
```

`Brand::class` is only a string: the class is not loaded, so the package may be missing altogether. `Soft::when([A::class, B::class], …)` wants both, `Soft::available(Brand::class)` answers the question itself.

**Relations.** `Atrium\Admin\Traits\HasSoftRelations` gives `softBelongsTo()`, `softBelongsToMany()` and `softHasMany()`, with the arguments of Laravel's own (the pivot table of `softBelongsToMany()` must be named). With the module installed they are Laravel's relations. Without it they find nothing: `$product->brand` is null, `$product->stores` is empty, `with()` and `whereHas()` work and no table of the missing module is queried.

```php
use HasSoftRelations;

/** @return BelongsTo<covariant Model,$this> */
public function brand(): BelongsTo
{
    return $this->softBelongsTo(Brand::class, 'brand_id');
}

/** @return BelongsToMany<covariant Model,$this> */
public function stores(): BelongsToMany
{
    return $this->softBelongsToMany(Store::class, 'catalog_product_store', 'product_id', 'store_id');
}
```

The link column (`brand_id`) and the pivot table belong to the module that declares the relation and are there with or without the other one. Make no foreign keys to the other module's tables: they may be dropped.

**Attached to a record of any module.** Reviews, comments and the like keep `{name}_type` (the model class) and `{name}_id` and use `softMorphTo()`:

```php
/** @return MorphTo<covariant Model,$this> */
public function reviewable(): MorphTo
{
    return $this->softMorphTo();
}
```

A review of a product reads as the product; once the catalog is removed, it reads as null instead of failing, also with `with('reviewable')`. `Soft::targets()` lists what a record can be attached to — model class => label of every content module in the panel — for a select of the type column; `TextColumn::make('reviewable.name')` shows the record in the list.
