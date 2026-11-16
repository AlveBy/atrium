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

## Validation and hooks

Rules come from the fields (`required()`, `unique()`, `rules()`). To replace them as a whole, return your own from `getCreateValidationRules()` and `getUpdateValidationRules()`.

`InteractsWithAdmin` has methods to override where the defaults do not fit:

| Method | When |
|---|---|
| `transformAdminData(array $data): array` | Before saving: change what the form sent |
| `afterAdminSave(array $data): void` | After saving: related records, cache |
| `beforeAdminDelete(): bool` | Before deleting; `false` cancels |
| `canEdit(): bool`, `canDelete(): bool` | Whether this record may be edited or deleted |
| `isCreatableInAdmin(): bool`, `isEditableInAdmin(): bool` (static) | `false` for records that come from elsewhere, such as form submissions: no "Add" or "Save", the routes and the API answer 403 |

## Permissions

Every module gets four permissions by its key: `articles.view`, `articles.create`, `articles.update`, `articles.delete`. They appear in the role form by themselves; the menu, the buttons, the API and the MCP tools follow them. Superadmins have them all.

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

## Interface translations

Labels may be plain text or translation keys. For a module that is used in more than one language, keep them in `lang/{language}/admin.php` and write `->label(__('admin.articles.name'))`.

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

A module for other sites is a Composer package of type `atrium-module`. Its `composer.json` says what it brings, in `extra.atrium`:

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
| `demo` | A seeder for `atrium:install --demo` and `atrium:module:install --demo`, best extending `Atrium\Admin\Modules\DemoSeeder` (it copies the package's pictures to the uploads disk). Its `run()` returns `false` when the module has content already and adds nothing then |

Every key is optional. `suggest` lists the modules this one links to softly (see below); their demo content is filled in first.

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
