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

The demo product (`packages/admin/src/Demo/Models/Product.php`) uses nearly all of them.

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

A module for other sites is a Composer package with the model, its migration and translations. Its service provider adds the model to the panel:

```php
public function register(): void
{
    config()->push('atrium.resources', \Vendor\Reviews\Review::class);
}

public function boot(): void
{
    $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    $this->loadTranslationsFrom(__DIR__.'/../lang', 'reviews');
}
```

`composer require vendor/reviews` and `php artisan migrate`, and the module is in the menu. Test it with Orchestra Testbench, as `packages/admin` does. Version 0.2 adds `atrium:module:install`, a "Modules" screen and a template repository: see the [roadmap](../ROADMAP.md).
