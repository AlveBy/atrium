# Свой модуль

[English](modules.md) · [Русский](modules.ru.md)

Модуль — модель Eloquent, которая описывает свою форму и список в админке. Стоит указать её в `config/atrium.php` → `resources`, и всё остальное появится само: маршруты, список с поиском и сортировкой, форма, пункт меню, права в форме роли, API и инструменты MCP.

- [Начало](#начало)
- [Поля](#поля)
- [Колонки списка](#колонки-списка)
- [Вкладки](#вкладки)
- [Проверка и обработчики](#проверка-и-обработчики)
- [Права](#права)
- [Языки контента и сайты](#языки-контента-и-сайты)
- [Страницы на сайте](#страницы-на-сайте)
- [Переводы интерфейса](#переводы-интерфейса)
- [Тесты](#тесты)
- [Модуль пакетом](#модуль-пакетом)
- [Связи с другими модулями](#связи-с-другими-модулями)

## Начало

```bash
php artisan atrium:make-module Article
php artisan migrate
```

Команда пишет `app/Models/Article.php`, миграцию таблицы `articles` и строку в `config/atrium.php`. В меню появляются «Articles» по адресу `/admin/articles`. `--label="Новости"` задаёт подпись в меню, `--no-migration` — без миграции.

Сгенерированная модель — отправная точка, в ней можно менять всё:

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

Поле пишет в одноимённую колонку, поэтому у каждого поля должна быть колонка в миграции и место в `$fillable`.

## Поля

Каждое поле создаётся через `::make('колонка')` и умеет `label()`, `required()`, `hint()`, `default()`, `placeholder()`, `rules([...])`, `unique('таблица', 'колонка')`, `readonly()`, `tab()` и `hideOnCreate()` / `hideOnEdit()`.

| Поле | Для чего | Частые настройки |
|---|---|---|
| `TextField`, `TextareaField` | Текст | `minLength()` |
| `NumberField` | Числа | `min()`, `max()`, `step()`, `decimals()`, `money()`, `suffix('кг')` |
| `CheckboxField` | Да / нет | `checkedValue('Y')`, если в колонке код, а не boolean |
| `SelectField` | Одно значение из списка | `options([...])`, `fromEnum(Status::class)`, `fromModel(Brand::class)` |
| `MultiSelectField` | Несколько значений из списка, хранятся JSON-массивом | `options([...])` |
| `DateField`, `DateTimeField` | Даты | `minDate()`, `maxDate()` |
| `ColorField` | Цвет | |
| `SlugField` | Адрес страницы | `from('name')`, `unique('таблица', 'code')` |
| `ContentField` | Текст с переключателем: текст, HTML, визуальный редактор | `modes(['text', 'html'])`, `defaultMode('html')` |
| `WysiwygField` | Только визуальный редактор | |
| `ImageField`, `FileField` | Один файл | `directory('articles')`, `maxSize(2048)`, `acceptedTypes([...])` |
| `GalleryField` | Несколько картинок | `maxFiles(20)`, `sortable()` |
| `RepeatableField` | Список значений: даты, телефоны, ссылки | `itemType('date')`, `maxItems(20)`, `addLabel('Добавить дату')` |
| `BelongsToField` | Привязка к записи другого модуля (`brand_id`) | `model(Brand::class)` |
| `BelongsToManyField` | Привязка к нескольким записям; у модели нужна одноимённая связь `belongsToMany` | `model(Tag::class)`, `sortable()` |
| `TreeSelectField` | Раздел из дерева | `model(Section::class)`, `parentColumn('parent_id')`, `multiple()` |
| `MapField` | Точка на карте | `yandex()` или `google()`, `defaultCenter()`, `defaultZoom()` |

Товар демо-магазина (`packages/catalog/src/Models/Product.php`) использует почти все.

## Колонки списка

`TextColumn`, `BooleanColumn`, `DateColumn` и `ImageColumn`, у каждой `label()`, `sortable()`, `searchable()`, `width()` и `alignRight()`. Поиск ищет по всем колонкам с `searchable()`.

Сортировка по умолчанию:

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

Чтобы сузить список или подгрузить связи, переопределите `scopeAdminList(Builder $query)`.

Колонка может показывать поле связанной записи: `TextColumn::make('brand.name')` подгружает связь `brand` для страницы и выводит название бренда. По такой колонке не сортируют и не ищут.

## Вкладки

Подключите `HasAdminTabs`, перечислите вкладки и разложите поля через `tab()`:

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

Поле без `tab()` попадает в `params`. `HasAdminSeo` сам добавляет на вкладку `seo` мета-заголовок, описание и ключевые слова.

## Проверка и обработчики

Правила берутся из полей (`required()`, `unique()`, `rules()`). Чтобы заменить их целиком, верните свои из `getCreateValidationRules()` и `getUpdateValidationRules()`.

В `InteractsWithAdmin` есть методы, которые можно переопределить, если поведение по умолчанию не подходит:

| Метод | Когда |
|---|---|
| `transformAdminData(array $data): array` | Перед сохранением: поправить то, что прислала форма |
| `afterAdminSave(array $data): void` | После сохранения: связанные записи, кеш |
| `beforeAdminDelete(): bool` | Перед удалением; `false` отменяет |
| `canEdit(): bool`, `canDelete(): bool` | Можно ли править или удалять эту запись |
| `isCreatableInAdmin(): bool`, `isEditableInAdmin(): bool` (static) | `false` для записей, которые приходят извне, например заявок с форм: нет «Добавить» и «Сохранить», маршруты и API отвечают 403 |

## Права

У каждого модуля четыре права по его ключу: `articles.view`, `articles.create`, `articles.update`, `articles.delete`. Они сами появляются в форме роли; по ним строятся меню, кнопки, API и инструменты MCP. У суперадмина есть все.

## Языки контента и сайты

**Языки.** С `Translatable` и колонками в `$translatable` в форме появляется переключатель языка, и каждое значение хранится по языкам. Колонки должны быть JSON (`$table->json('name')`). Обязателен только язык по умолчанию, остальные берут значение из него.

**Сайты.** С `BelongsToSite` и колонкой `site_id` модуль становится «по сайтам»: список, форма, поиск и страницы на сайте видят только записи текущего сайта, новая запись получает текущий сайт. Без трейта модуль общий для всех сайтов.

```php
$table->foreignId('site_id')->nullable()->index();
```

## Страницы на сайте

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

даёт каждой статье адрес `/articles/{code}`: поле символьного кода показывает его под кодом, API отдаёт в `public_url`, а тема сайта (`alveby/atrium-theme`) рисует страницу по форме модуля. Записи в разделах и страницы самих разделов описаны в комментарии над `public_urls` в `config/atrium.php`.

Чтобы страницы модуля попали в `sitemap.xml`, реализуйте `Atrium\Admin\Contracts\Sitemapable`: `sitemapQuery()` возвращает записи, которым там место, `sitemapUrl($language)` — адрес одной из них.

## Переводы интерфейса

Подписи могут быть обычным текстом или ключами перевода. Для модуля, которым пользуются на нескольких языках, держите их в `lang/{язык}/admin.php` и пишите `->label(__('admin.articles.name'))`.

## Тесты

Маршруты админки названы по ключу модуля (`admin.articles.index`, `admin.articles.store`, …), поэтому feature-тест входит администратором и отправляет форму:

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

Больше примеров — в тестах самого Atrium (`packages/admin/tests`): права, API, браузерные тесты на Dusk.

## Модуль пакетом

Модуль для чужих сайтов — пакет Composer с типом `atrium-module`. Что он приносит, написано в его `composer.json`, в `extra.atrium`:

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

| Ключ | Что это |
|---|---|
| `resources` | Модели модуля в порядке меню: то же, что строки в `atrium.resources` |
| `public_urls` | Их страницы на сайте, как в `atrium.public_urls`; правило сайта для той же модели важнее |
| `migrations` | Папка (или список папок) относительно пакета |
| `demo` | Сидер для `atrium:install --demo` и `atrium:module:install --demo`; удобнее всего наследовать `Atrium\Admin\Modules\DemoSeeder` (он копирует картинки пакета на диск загрузок). Его `run()` возвращает `false`, если в модуле уже есть данные, и тогда ничего не добавляет |

Все ключи необязательны. В `suggest` — модули, с которыми этот связан мягко (см. ниже); их демо-данные заполняются раньше.

Две команды — после `composer require` и перед `composer remove`:

```bash
composer require vendor/atrium-reviews
php artisan atrium:module:install vendor/atrium-reviews

php artisan atrium:module:remove vendor/atrium-reviews
composer remove vendor/atrium-reviews
```

`atrium:module:install` запускает миграции модуля и добавляет его в список `modules` в `config/atrium.php`; с этого момента его модели есть в меню, в форме роли, в API и MCP, его миграции идут вместе с `php artisan migrate`, его страницы — на сайте. Composer команда не запускает: пакет должен быть уже подключён. `--demo` заполняет модуль демо-данными.

`atrium:module:remove` спрашивает, оставить ли данные. Если нет, миграции модуля откатываются и его таблицы удаляются; в любом случае его права уходят из ролей, а сам он — из списка. В скриптах на вопрос отвечают `--keep-data` и `--delete-data`. Если снова установить модуль, у которого данные оставили, записи вернутся.

Подключённый, но не установленный модуль в админке не виден нигде, даже если его сервис-провайдер добавляет модели в `atrium.resources`. Поэтому провайдеру нужно только то, чего нет в манифесте:

```php
public function boot(): void
{
    $this->loadTranslationsFrom(__DIR__.'/../lang', 'reviews');
}
```

Миграции оставьте манифесту: через `loadMigrationsFrom()` они запустились бы и для неустановленного модуля и вернули бы таблицы удалённого. Закешированные конфиг и маршруты обе команды собирают заново.

Тестируйте модуль через Orchestra Testbench, как модули в `packages/`: укажите его в `atrium.modules` в `defineEnvironment()` — и модели, миграции и страницы будут на месте, как на сайте.

## Связи с другими модулями

Модули связаны мягко: товары работают без брендов, а поле и колонка «Бренд» появляются, только когда установлен модуль брендов. Ничто в `require` Composer не заставляет один модуль ставить другой. Так устроен демо-магазин: `alveby/atrium-catalog` ссылается на `alveby/atrium-brands` и `alveby/atrium-stores` (`packages/catalog/src/Models/Product.php`).

**Поля, колонки, вкладки.** `Atrium\Admin\Modules\Soft::when()` возвращает элементы, если модель доступна — класс есть и его модуль установлен, — и пустой список иначе. Разверните его в списке:

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

`Brand::class` — просто строка: класс не загружается, так что пакета может не быть вовсе. `Soft::when([A::class, B::class], …)` требует оба, `Soft::available(Brand::class)` отвечает на сам вопрос.

**Связи Eloquent.** `Atrium\Admin\Traits\HasSoftRelations` даёт `softBelongsTo()`, `softBelongsToMany()` и `softHasMany()` с аргументами как у Laravel (у `softBelongsToMany()` промежуточная таблица указывается всегда). С установленным модулем это обычные связи Laravel. Без него они ничего не находят: `$product->brand` — null, `$product->stores` пуст, `with()` и `whereHas()` работают, и ни одна таблица отсутствующего модуля не запрашивается.

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

Колонка связи (`brand_id`) и промежуточная таблица принадлежат модулю, который объявляет связь, и есть с другим модулем и без него. Не делайте внешних ключей на таблицы другого модуля: их могут удалить.

**Привязка к записи любого модуля.** Отзывы, комментарии и подобное хранят `{name}_type` (класс модели) и `{name}_id` и используют `softMorphTo()`:

```php
/** @return MorphTo<covariant Model,$this> */
public function reviewable(): MorphTo
{
    return $this->softMorphTo();
}
```

Отзыв о товаре возвращает товар; когда каталог удалят, он вернёт null вместо ошибки, в том числе через `with('reviewable')`. `Soft::targets()` перечисляет, к чему можно привязать запись, — класс модели => название каждого контентного модуля админки — для выпадающего списка колонки типа; `TextColumn::make('reviewable.name')` показывает запись в списке.
