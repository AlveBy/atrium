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

Команда пишет `app/Models/Article.php`, миграцию таблицы `articles` и строку в `config/atrium.php`. В меню появляются «Articles» по адресу `/admin/articles`. `--label="Новости"` задаёт подпись в меню, `--no-migration` — без миграции. Подпись пишется в `lang/en/articles.php` (язык по умолчанию), а модель читает её через `__('articles.module')`: добавьте `lang/ru/articles.php` с `'module' => 'Статьи'`, и администратор с русским интерфейсом увидит меню по-русски. Подписи стандартных полей берутся из языковых файлов самой панели, на всех 15 языках.

Сгенерированная модель — отправная точка, в ней можно менять всё:

```php
class Article extends Model implements Administrable
{
    use InteractsWithAdmin, Translatable;

    protected array $translatable = ['name', 'code', 'preview_text', 'detail_text'];

    protected $fillable = ['name', 'code', 'active', 'sort', 'preview_text', 'detail_text'];

    public static function getAdminLabel(): string
    {
        return __('articles.module');
    }

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label(__('atrium::fields.standard.name'))->required(),
            SlugField::make('code')->label(__('atrium::fields.standard.code'))->from('name')->unique('articles', 'code'),
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
            ContentField::make('preview_text')->label(__('atrium::fields.standard.preview_text')),
            ContentField::make('detail_text')->label(__('atrium::fields.standard.detail_text')),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium::fields.standard.name'))->sortable()->searchable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
            DateColumn::make('updated_at')->label(__('atrium::fields.standard.updated_at'))->sortable(),
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
| `TreeField` | Дерево пунктов со своими полями, правится перетаскиванием (пункты модуля меню). Значение — вложенный массив с `children`: JSON-колонка, или модель пишет его сама в `afterAdminSave()` | `fields([...])`, `titleField('title')`, `maxDepth(4)`, `maxItems(500)` |
| `FormBuilderField` | Поля формы для посетителей, собранные прямо на странице записи (модуль форм): добавляются из ряда типов, перетаскиваются, у каждого подпись, код, тип, обязательность, варианты, текст в пустом поле, подсказка, минимум и максимум и ширина от 1 до 12 колонок; рядом предпросмотр для компьютера и телефона. Значение — список полей; модель отдаёт его атрибутом и пишет сама в `afterAdminSave()` | `types([...])`, `choiceTypes(['select', 'radio'])`, `maxItems(100)` |
| `LinkField` | Ссылка: адрес или запись любого модуля со страницами, выбранная по названию. Значение — `{"type": "url", "url": …}` или `{"type": "record", "model": …, "id": …}` | `urlsOnly()` |
| `MapField` | Точка на карте | `yandex()` или `google()`, `defaultCenter()`, `defaultZoom()` |
| `DisplayField` | HTML только для чтения, собранный из записи на форме редактирования: строки заказа, сводка, ссылка | `html(fn (Model $record): string => view('…')->render())`; на форме создания нет, не отправляется |

Товар демо-магазина (`packages/catalog/src/Models/Product.php`) использует почти все.

### Поля связей, когда записей много

`BelongsToField` и `BelongsToManyField` кладут в форму все записи связанной модели: выпадающим списком или списком флажков (с полем фильтра, если записей больше десяти). Когда записей больше 50, форма их ищет: выбранные записи — плашки, новые находятся по части названия или по id в поле поиска, по двадцать, следующие — при прокрутке; работают стрелки, Enter и Escape. Порог задаётся у поля:

```php
BelongsToManyField::make('related')->model(Product::class)->searchThreshold(100),
```

Записи приходят из `GET /admin/{module}/fields/{field}/options?q=…&page=…`, ответ — `{"data": [{"value": 7, "label": "Quiet One"}], "more": true}`. Поиск идёт по `displayColumn()` (переводимое поле — на всех языках контента) и по id, с учётом `query()` поля и `scopeAdminList()` связанного модуля, текущего сайта и того, какие записи этому сайту видны. Открыт тому, кто может открыть модуль и видеть связанный. Сохранение, проверка, `maxItems()` / `minItems()`, API, MCP и импорт читают и пишут такие поля как раньше. `searchRoute('имя или адрес')` отправляет поиск в другое место при любом числе записей, теперь и у `BelongsToManyField`; такой маршрут должен принимать `q` и `page` и отвечать в том же виде.

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

Кнопка у записи, в строке списка и на её форме, открывает форму создания, заполненную из этой записи. Подстраница получает страницу родителем:

```php
use Atrium\Admin\Actions\CreateFromRecord;

public static function getAdministrationActions(): array
{
    return [
        CreateFromRecord::make('subpage')->label('Подстраница')->fill(['parent_id' => 'id']),
    ];
}
```

`fill()` сопоставляет поля формы полям записи: кнопка открывает `/admin/pages/create?parent_id=12`, и из адреса берутся только названные в ней поля. Администратор без права создавать записи здесь кнопку не видит.

### Кнопки у записи и над выбранными записями

В тот же список кладутся ещё два вида кнопок. **Ссылка** ведёт куда угодно, в том числе на другой сайт; **действие на сервере** выполняет класс над записью:

```php
use Atrium\Admin\Actions\ActionResult;
use Atrium\Admin\Actions\LinkAction;
use Atrium\Admin\Actions\RecordAction;
use Illuminate\Database\Eloquent\Model;

public static function getAdministrationActions(): array
{
    return [
        LinkAction::make('preview')->label('На сайте')->url(fn (Article $article) => $article->getPublicUrl())->newTab(),
        Publish::make('publish')->label('Опубликовать')->confirm('Опубликовать сейчас?'),
    ];
}

final class Publish extends RecordAction
{
    public function handle(Model $record): ActionResult
    {
        if ($record->published_at !== null) {
            return ActionResult::error('Уже опубликовано.');
        }

        $record->update(['published_at' => now()]);

        return ActionResult::success('Опубликовано.'); // или ActionResult::redirect($url)
    }
}
```

Как только у списка есть массовое действие, в строках появляются флажки, а в заголовке — флажок для всей страницы. Отмеченные строки открывают над списком панель с их числом и кнопками. **Удалить** есть всегда, у кого есть право удалять; модуль добавляет свои:

```php
use Atrium\Admin\Actions\BulkAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

public static function getAdministrationBulkActions(): array
{
    return [Archive::make('archive')->label('В архив')->confirm()];
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

`handle()` получает только те отмеченные записи, которые администратор видит в текущем сайте (с `scopeAdminList()` модуля и сайтом), не больше 1000. Действие с `ShouldQueue` уходит в очередь, администратору говорят, что оно запущено; задача выполняется в том же сайте. `confirm()` сначала спрашивает — вашим вопросом или «Вы уверены?».

Каждой кнопке нужно право: ссылке `view`, остальным `update`, «Удалить» — `delete`. `permission('publish')` делает его `{модуль}.publish` (объявите его в манифесте, см. «Права»), а `permission('menus.update')` называет право другого модуля. Без права администратор кнопку не видит, а сервер отвечает 403. Панель находит кнопку по имени, поэтому выполняет только то, что перечислил модуль.

Другие модули добавляют кнопки хуками `atrium.module.actions` и `atrium.module.bulk_actions` (см. «Хуки»). Регистрируйте их в сервис-провайдере, только пока модуль установлен: выключили модуль — его кнопок нет. Так модуль меню ставит **Добавить в верхнее меню** на каждую запись, у которой есть страница на сайте:

```php
Hook::filter('atrium.module.actions', fn (array $actions, string $model) => app(RecordUrls::class)->hasPages($model)
    ? [...$actions, AddToHeaderMenu::make('add-to-header-menu')->label(__('...'))->permission('menus.update')]
    : $actions);
```

Те же действия на сервере есть в API и MCP: `GET /api/v1/{модуль}/actions`, `POST /api/v1/{модуль}/{id}/actions/{имя}`, `POST /api/v1/{модуль}/bulk/{имя}` с `{"ids": [...]}`; инструменты `list_actions`, `run_record_action`, `run_bulk_action`. Действию, чьё право — `view`, нужен токен на чтение, остальным — на запись. Ссылок и заполненных форм создания там нет: это кнопки самой панели.

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

Другой модуль ставит свои поля на отдельную вкладку: поля он добавляет через `atrium.module.fields` с `tab('shop')`, а вкладку — через `atrium.module.tabs`. Вкладка без полей не показывается, так что она уходит вместе с полями модуля:

```php
use Atrium\Admin\Tabs\Tab;

Hook::filter('atrium.module.tabs', fn (array $tabs, string $model) => $model === Product::class
    ? [...$tabs, Tab::make('shop')->label(__('shop::tabs.shop'))->order(50)]
    : $tabs);
```

Форма модуля без вкладок (нет `HasAdminTabs` или `usesTabs()` возвращает `false`) рисуется плоской. Как только другой модуль добавил для неё вкладку через `atrium.module.tabs` и на этой вкладке стоит поле, у формы появляются вкладки: собственные поля модуля и каждое поле на вкладке, которой у формы нет, остаются вместе на первой, «Основное», а добавленные идут следом. Именно так делает пакет «Свои поля» с вкладками, которые добавляет администратор ([Свои поля](custom-fields.ru.md#вкладки)).

## Панели на записи

Другой модуль может показать на странице записи свой блок: время по задаче, заказы клиента, предпросмотр. Панель рисуется Blade-шаблоном и регистрируется в `atrium.ui.panels` — для одного модуля (`for()`) или для всех:

```php
use Atrium\Admin\Extensions\Panel;

Hook::register('atrium.ui.panels', 'time.task', Panel::make('time.task')
    ->for(Task::class)
    ->label('acme-time::panels.time')
    ->html('acme-time::panels.task')
    ->side()
    ->permission('time.view'));
```

- **Где**: `below()` — под формой (по умолчанию), `side()` — в колонке сбоку на широком экране, `tab()` — вкладкой после вкладок формы (под формой, если вкладок нет).
- **Кому**: `permission()` показывает панель только администраторам с этим правом; `when(fn (Model $record, ?AdminUser $user): bool => …)` решает по записи. Показывает только `true`.
- **Что**: шаблон получает запись как `$record` и администратора как `$user`. Страница показывает HTML как есть, поэтому значения выводите через `{{ }}` — он их экранирует.

Панели идут в порядке регистрации (меньший приоритет раньше), а реестр получает запись, так что `Hook::filter('atrium.ui.panels', fn (array $panels, Model $record) => …)` может убрать панель для части записей. Панель, чей шаблон упал, попадает в отчёт об ошибках и пропускается: запись остаётся доступной для правки. Панели показываются на странице правки, а не на форме создания — там записи ещё нет.

## Проверка и обработчики

Правила берутся из полей (`required()`, `unique()`, `rules()`). Чтобы заменить их целиком, верните свои из `getCreateValidationRules()` и `getUpdateValidationRules()`.

В `InteractsWithAdmin` есть методы, которые можно переопределить, если поведение по умолчанию не подходит:

| Метод | Когда |
|---|---|
| `transformAdminData(array $data): array` | Перед сохранением: поправить то, что прислала форма |
| `afterAdminSave(array $data): void` | После сохранения, в той же транзакции: связанные записи, кеш |
| `beforeAdminDelete(): bool` | Перед удалением, в той же транзакции; `false` отменяет |
| `canEdit(): bool`, `canDelete(): bool` | Можно ли править или удалять эту запись |
| `isCreatableInAdmin(): bool`, `isEditableInAdmin(): bool` (static) | `false` для записей, которые приходят извне, например заявок с форм: нет «Добавить» и «Сохранить», маршруты и API отвечают 403 |
| `isContentModule(): bool` (static) | `false` для служебного модуля: журналы, результаты проверок, заявки с форм, языки. Ему не добавляются пользовательские поля, его не предлагают целью ссылки в меню и отзыва; пункт меню, поиск, API и выгрузка остаются. По умолчанию `true` |
| `isImportableInAdmin(): bool`, `isExportableInAdmin(): bool` (static) | `false` — убрать модуль из импорта и выгрузки CSV и Excel; по умолчанию любой модуль выгружается, а импортируется, если его записи можно и создавать, и править. `getImportKeys(): array` — колонки, по которым запись находится снова. См. [Импорт и выгрузка](import-export.ru.md#для-авторов-модулей) |

## Права

У каждого модуля четыре права по его ключу: `articles.view`, `articles.create`, `articles.update`, `articles.delete`, а если модуль можно импортировать и выгружать, то и `articles.import` и `articles.export`. Они сами появляются в форме роли; по ним строятся меню, кнопки, API и инструменты MCP. У суперадмина есть все.

Модуль-пакет, которому этого мало, объявляет свои права в манифесте:

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

Имя — `{раздел}.{действие}`; голое действие относится к ключу модуля (`refund` → `shop.refund`). Подпись — текст или ключ перевода. Пока модуль установлен, каждое право — галочка в строке своего раздела в форме роли (в столбце «Другое» или в отдельной строке), а модуль проверяет его через `$user->hasAdminPermission('shop.refund')`. `with` называет право, вместе с которым оно выдаётся: при установке модуля каждая роль с `orders.view` получает и `orders.export`. `atrium:module:remove` убирает их из ролей.

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

С установленным модулем SEO у страниц модуля появляются заголовки по шаблонам, hreflang и разметка schema.org. Свою разметку (`Event`, `JobPosting`) модуль добавляет через `SchemaProvider`, см. [SEO: разметка своего модуля](seo.ru.md#разметка-своего-модуля).

## Шаблоны страниц

Модуль со страницей на сайте может дать редактору выбор, как выглядит запись, как шаблоны страниц в WordPress. Положите Blade-файл в views сайта:

```
resources/views/templates/articles/wide-photo.blade.php   только для модуля с ключом `articles`
resources/views/templates/all/full-width.blade.php        для всех модулей
```

Его первая строка — название в форме:

```blade
{{-- Template: Фото на всю ширину --}}
@extends($defaultView)

@section('main-class', 'wrap-full')
```

Больше ничего не нужно: в форме записи модуля появится список **Шаблон**, первым **По умолчанию**, дальше найденные файлы, и запись рисуется выбранным. Пока у модуля нет ни одного шаблона, кроме обычного, списка нет, и он бывает только у модулей из `public_urls`.

- **Название** — комментарий `{{-- Template: … --}}` в начале файла. Другой комментарий перед ним (например, строка про авторские права) не мешает. Название может быть ключом перевода (`{{-- Template: shop::templates.wide --}}`) или текстом из JSON-переводов сайта. Без комментария название берётся из имени файла (`wide-photo` → «Wide Photo»).
- **Папка** — `templates/{модуль}/` по ключу модуля (для пакета модуля `acme/atrium-labels` это `labels`; для модели самого приложения — имя таблицы, `articles`) и `templates/all/` для всех. Файл из папки модуля сильнее одноимённого из `all`. В имени файла только буквы, цифры, `-` и `_`; `default` занято: это собственный вид страницы.
- **Где ещё ищется:** у темы или скина такая же папка в их views. Публичная тема смотрит сначала views сайта, потом скина (`skins/{скин}/templates/…`), потом свои: оттуда берётся **На всю ширину** (`templates/all/full-width`), который рисует любую запись и любой раздел без боковых полей. Настройка `atrium.templates` добавляет, заменяет или прячет:

```php
// config/atrium.php
'templates' => [
    'articles' => [
        'wide-photo' => 'shop::articles.wide',   // ключ => Blade-вид
        'full-width' => null,                    // для статей этот не предлагать
    ],
],
```

- **Что получает:** шаблон рисуется вместо обычного вида с теми же переменными (`$page`, `$title`, `$canonical`, `$breadcrumbs` …), а `$defaultView` — имя вида, которым запись рисовалась бы без шаблона. `@extends($defaultView)` оставляет всю его разметку и меняет только названные места; можно и `@include($defaultView)`. Шаблон может быть и отдельной страницей с `@extends('atrium-theme::layout')`.
- **Пропавший файл:** запись, чей шаблон удалили или выключили, рисуется обычным видом без ошибки. Выбор не теряется и снова работает, когда файл вернётся.
- **Где хранится:** в `admin_record_templates` (модель и id, как хранятся SEO-данные), так что модулю не нужны ни колонка, ни миграция. Модель, у которой для этого своя колонка (у страниц это `template`), говорит об этом, реализуя `Atrium\Admin\Templates\OwnsTemplateColumn` (`public static function templateColumn(): string`), и получает тот же список и то же рисование без второго поля.
- **Пакеты:** `app(Atrium\Admin\Templates\Templates::class)->source(Article::class, fn () => ['wide' => 'shop::articles.wide'])` добавляет шаблоны кодом, а `->roots(fn () => ['shop::'])` называет ещё одно место с папкой `templates/`.

Новые файлы находятся при следующем запросе; при закешированных конфиге и видах выполните `php artisan view:clear`. Страница записи не делает лишних запросов, когда кеш модуля прогрет.

## Капча в публичной форме

Форма на сайте спрашивает капчу сайта (`ATRIUM_CAPTCHA`, см. [Настройка Atrium](configuration.ru.md#капча)) компонентом и правилом; пока капча выключена, оба пустые:

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

Поля капчи не возвращайте в форму: `->withInput($request->except(CaptchaManager::INPUTS))`. Капча другого вида (Cloudflare Turnstile, hCaptcha, своя) — класс с `widget()` и `verify($request)` (`Atrium\Admin\Captcha\CaptchaDriver`), который добавляют через `app(CaptchaManager::class)->extend('turnstile', fn () => new Turnstile(...))` и выбирают `ATRIUM_CAPTCHA=turnstile`.

## Кеш

Публичные страницы берут записи из кеша записей. Добавьте модели `implements Cacheable` и `use CachesRecords`, а списки читайте через `->cachedIds()` / `->cachedPaginate()` / `->cachedCards()` вместо `->get()`: тогда список спрашивает у базы только ID, а каждая запись приходит из своей карточки, одним multi-get на всю страницу. Сохранения через Eloquent сбрасывают то, чего касаются; код, который пишет через `DB::table()`, вызывает `app(RecordCache::class)->flushClass(Article::class)`, а импорт идёт внутри `bulk()`. Всё остальное по шагам: [cache.ru.md](cache.ru.md).

## Переводы интерфейса

Подписи могут быть обычным текстом или ключами перевода. Для модуля, которым пользуются на нескольких языках, держите их в `lang/{язык}/admin.php` и пишите `->label(__('admin.articles.name'))`.

## Рамка панели

Боковое меню, шапка, сообщения и помощник на ИИ — это layout каждой страницы админки (`Layouts/AdminShell.vue`, один раз заданный как `layout` приложения по умолчанию). Inertia создаёт его один раз и хранит между переходами: у меню остаются прокрутка и раскрытые группы, у помощника — открытый чат, у шапки — сайт и язык. Страница — это только её содержимое и заголовок:

```vue
<script setup>
import { Head } from '@inertiajs/vue3'
</script>

<template>
    <Head :title="title" />
    <h1>{{ title }}</h1>
</template>
```

У страниц в `Pages/Admin/Auth/` (вход) рамки нет. Любая другая страница может выбрать свой layout через `defineOptions({ layout: … })` или обойтись без него — `layout: false`.

**Обновление.** Страницу, которая сама оборачивается в `<AdminLayout :title="…">…</AdminLayout>`, менять не обязательно: внутри рамки `AdminLayout` только ставит заголовок, так что рамка одна, а не две. Перестраиваться вокруг такой страницы ничего не будет, пересоздаётся только сама обёртка. Перенесите заголовок в `<Head>` и уберите обёртку, когда в следующий раз будете править страницу. `AdminLayout` остаётся для совместимости; страница, отказавшаяся от layout по умолчанию и оборачивающая себя сама, получает от него целую рамку.

## Плитки рабочего стола

Стартовая страница панели — набор плиток. Каждая — класс-наследник `Atrium\Admin\Dashboard\Gadget`, зарегистрированный по ключу в `atrium.ui.gadgets`: так регистрирует свои плитки сама панель (`site-speed`, `performance`, `recent`), модули ошибок и мониторинга (`errors`, `monitoring`). Модуль добавляет свою плитку так же, в `boot()`:

```php
Hook::register('atrium.ui.gadgets', 'time.week', WeekGadget::class);
```

Плитка, которую пакет рисует сам, наследует `Atrium\Admin\Dashboard\HtmlGadget`: это Blade-шаблон на сервере, собирать Vue не нужно. Панель даёт ей рамку и заголовок и показывает HTML как есть, поэтому значения выводите через `{{ }}` — он их экранирует. Шаблон получает то, что вернул `viewData()`, а настройки плитки — как `$options`:

```php
final class WeekGadget extends HtmlGadget
{
    public function title(): string
    {
        return __('acme-time::gadget.week');
    }

    protected function view(): string
    {
        return 'acme-time::gadget';
    }

    protected function viewData(): array
    {
        return ['hours' => Hours::thisWeek()];
    }
}
```

`atrium.dashboard.gadgets` в `config/atrium.php` — раскладка: порядок, настройки и выключенные плитки. Элемент — ключ, имя класса или массив с `gadget` и настройками (`title`, `width` — `half` или `full`, `enabled` и всё, что плитка читает сама). Зарегистрированная плитка, которой нет в раскладке, встаёт после названных, так что плитка нового модуля появляется без правки конфига; чтобы скрыть её, назовите её выключенной:

```php
'gadgets' => [
    'site-speed',
    ['gadget' => 'recent', 'model' => App\Models\Article::class, 'count' => 5],
    ['gadget' => 'performance', 'width' => 'half'],
    ['gadget' => 'errors', 'enabled' => false],
],
```

Плитка сама решает, показываться ли (`isVisible()`: плитку ошибок видят только те, кому можно смотреть ошибки), а упавшая при сборке данных попадает в отчёт об ошибках и пропускается, а не роняет страницу.

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

Модуль для других сайтов — это Composer-пакет с типом `atrium-module`. Чтобы начать такой пакет, выполните:

```bash
php artisan atrium:make-package acme/atrium-reviews
```

Команда создаёт `packages/reviews` по шаблону из `vendor/alveby/atrium/stubs/module`. В нём манифест, переводимая модель с формой и списком для админки, которая уже работает через [кеш записей](cache.ru.md), её миграция, демо-сидер, английские подписи, тесты на Testbench, которые ставят модуль в чистый Atrium, workflow GitHub Actions для PHP 8.3 и 8.4, README и лицензия MIT. Тесты проходят сразу после генерации. `--model=Review`, `--namespace=Acme\\Reviews`, `--title=Reviews` и `--path=` меняют значения по умолчанию. В конце команда печатает три команды, чтобы попробовать пакет в текущем проекте: path-репозиторий Composer, `composer require acme/atrium-reviews:@dev` и `atrium:module:install --demo`. Чтобы опубликовать пакет, вынесите его в отдельный репозиторий и отправьте на Packagist: после этого он появится в каталоге на каждом сайте (см. [Экран модулей](#экран-модулей)). Ваши файлы принадлежат вам: в отличие от файлов самого Atrium, заголовок с копирайтом Atrium им не нужен.

`composer.json` описывает, что модуль приносит, в `extra.atrium`:

```json
{
    "name": "vendor/atrium-reviews",
    "type": "atrium-module",
    "require": {
        "php": "^8.3",
        "alveby/atrium": "^0.2"
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
| `search` | Что модуль поиска (`alveby/atrium-search`) читает в моделях: `{"Vendor\\Reviews\\Review": {"title": "name", "content": ["text"]}}` или `false`, чтобы модель не искалась. Без него модель со страницами находится по `name` и текстовым полям своей формы |
| `demo` | Сидер для `atrium:install --demo` и `atrium:module:install --demo`; удобнее всего наследовать `Atrium\Admin\Modules\DemoSeeder` (он копирует картинки пакета на диск загрузок). Его `run()` возвращает `false`, если в модуле уже есть данные, и тогда ничего не добавляет |
| `key`, `title` | Ключ модуля в правах и настройках и его название в панели (см. «Настройки модуля») |
| `settings` | Класс его настроек (см. «Настройки модуля») |
| `permissions` | Его собственные права сверх просмотра, создания, изменения и удаления (см. «Права») |
| `submodules`, `parent` | Его подмодули или модуль, подмодулем которого он является (см. «Подмодули») |

Все ключи необязательны. Страница записи, какой бы модуль её ни рисовал, — `app(Atrium\Admin\Support\RecordUrls::class)->url($record, 'de')` (`null`, если страницы нет); за `public_urls` отвечает тема, а модуль со своими страницами добавляет ответ через `RecordUrls::extend()`. Через него на записи ссылаются меню и поиск. В `suggest` — модули, с которыми этот связан мягко (см. ниже); их демо-данные заполняются раньше.

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

### Подмодули

У модуля могут быть подмодули: у магазина — способы доставки и оплаты. Каждый — отдельный пакет со своим манифестом, моделями, миграциями, настройками и правами. Родитель перечисляет те, что знает; подмодуль от другого автора сам называет родителя:

```json
"extra": {"atrium": {"submodules": ["vendor/atrium-shop-delivery", "vendor/atrium-shop-payment"]}}
```

```json
"extra": {"atrium": {"parent": "vendor/atrium-shop"}}
```

`atrium:module:install vendor/atrium-shop` после самого модуля устанавливает все его подмодули, которые есть в Composer. Подмодуль можно установить и отдельно, когда родитель уже установлен. `atrium:module:remove vendor/atrium-shop` сначала удаляет подмодули с тем же ответом про данные; подмодуль, удалённый отдельно, родителя не трогает. Экран **Модули** показывает подмодули под родителем, а их демо-данные заполняются после родительских.

### Экран модулей

Пункт **Модули** в меню показывает установленные модули и кнопку к настройкам у тех, у кого они есть. Суперадмин видит там больше:

- **Выключить** и **Включить**. У выключенного модуля остаются таблицы, записи и настройки, но во всём остальном он считается неустановленным: его модели уходят из меню, формы ролей и API, а его страницы — с сайта. `Modules::isInstalled()` для него возвращает false, поэтому сервис-провайдер, который это проверяет, менять не нужно. Модуль выключается вместе со своими подмодулями и включается тоже вместе с ними. Подмодуль нельзя включить, пока выключен его родитель. При включении выполняются миграции, которые появились у модуля, пока он был выключен. Список выключенных хранится в `atrium.disabled_modules` в config/atrium.php. `atrium:module:install` снова включает модуль, а `atrium:module:remove` удаляет и выключенный модуль.
- **Подключены, но не установлены**: пакеты, которые есть в Composer, но которых нет в `atrium.modules`. У каждого показана команда установки.
- **Каталог**: модули Atrium, опубликованные на Packagist, то есть пакеты с типом `atrium-module` или ключевым словом `atrium-module`. Сначала идут модули alveby, потом самые скачиваемые. У каждого видно, установлен ли он, выключен, подключён или только доступен, и команды, которыми его добавить (`composer require` и `atrium:module:install`). Packagist опрашивается не чаще раза в шесть часов и уже после загрузки страницы. Если он не отвечает, экран так и пишет.

Чтобы опубликовать свой модуль, укажите в его composer.json `"type": "atrium-module"` или добавьте `atrium-module` в `keywords` и отправьте пакет на Packagist. Он появится в каталоге на каждом сайте.

## Настройки модуля

Модуль, которому нужно что-то от владельца сайта — валюта, число товаров на странице, адрес для уведомлений, — называет класс настроек в манифесте:

```json
"extra": {
    "atrium": {
        "key": "shop",
        "title": "atrium-shop::shop.title",
        "settings": "Vendor\\Shop\\ShopSettings"
    }
}
```

Класс наследует `Atrium\Admin\Modules\ModuleSettings` и перечисляет поля теми же типами полей, что и в админке, с правилами и значениями по умолчанию:

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

Значение читается где угодно фасадом `Setting`:

```php
use Atrium\Admin\Facades\Setting;

Setting::get('shop.currency');              // the current site's value, or the field's default
Setting::get('shop.free_delivery_from', 0); // 0 when nobody set it and the field has no default
Setting::get('shop.currency', null, $site); // another site (a Site or its id)
Setting::all('shop');                       // every field by name
```

Незаполненное поле даёт свой `default()`, затем второй аргумент. У неустановленного модуля настроек нет: `get()` вернёт второй аргумент. Значения хранятся в таблице `admin_module_settings` и читаются один раз за запрос.

`$perSite = true` хранит свой набор значений для каждого сайта — его задают на экране сайта, выбранного в шапке панели; `false` (по умолчанию) — один набор на все сайты. `description()` возвращает строку под заголовком экрана.

Установленные модули перечислены в меню панели в разделе **Модули**, у тех, что с настройками, — кнопка **Настройки**. Для настроек нужно право `{key}.settings` (`shop.settings`), оно есть в строке модуля в форме роли; у суперадминов оно есть. `atrium:module:remove` убирает его из ролей, а при удалении данных удаляет и настройки.

`key` в манифесте — ключ модуля в правах и в `Setting::get()`; без него это имя пакета без `atrium-` (`vendor/atrium-shop` → `shop`). `title` — его название в панели (ключ перевода или текст).

Пример — каталог: `per_page`, товаров на странице раздела, для каждого сайта (`packages/catalog/src/CatalogSettings.php`); тема читает его через `Setting::get('catalog.per_page')`.

## Хуки

Модули встречаются в хуках — именованных точках, где один модуль сообщает остальным, что произошло, или даёт им изменить значение. Напрямую друг друга они не вызывают, поэтому любого из них может не быть.

**Действие** (action) сообщает, что что-то произошло. Каждый слушатель получает аргументы, обратно ничего не возвращается:

```php
use Atrium\Admin\Facades\Hook;

// The shop, when an order is paid:
Hook::fire('atrium.order.paid', $order);

// Any module, in its service provider's boot():
Hook::on('atrium.order.paid', fn (Order $order) => $order->customer->notify(new OrderPaid($order)));
Hook::on('atrium.order.paid', SendReceipt::class); // a class with handle(), may implement ShouldQueue
```

Действия — это события Laravel с именем-строкой, поэтому `Event::listen('atrium.order.paid', ...)` равносилен `Hook::on()`, а слушатель с очередью выполняется в очереди.

**Фильтр** (filter) даёт модулям изменить значение. Каждый обработчик получает то, что вернул предыдущий, и остальные аргументы хука и возвращает значение:

```php
// The search module asks which sources there are:
$sources = Hook::apply('atrium.search.sources', [], $site);

// A module adds its own:
Hook::filter('atrium.search.sources', fn (array $sources) => [...$sources, ProductSource::class]);
Hook::filter('atrium.search.sources', DropDrafts::class, 20); // an invokable class, later than the default 10
```

Меньший приоритет выполняется раньше; по умолчанию 10. `Hook::has('atrium.order.paid')` отвечает, слушает ли кто-нибудь. Хук без слушателей стоит одного поиска в массиве, так что вызывайте смело.

Называйте хук `atrium.{модуль}.{что}`: действия — в прошедшем времени (`atrium.order.paid`), фильтры — по значению, которое фильтруют (`atrium.menu.items`). Регистрируйте слушателей, только когда модуль установлен (`app(Modules::class)->isInstalled('vendor/atrium-shop')`), и перечислите хуки модуля с аргументами в его README.

Ядро вызывает такие:

| Хук | Вид | Аргументы | Когда |
|---|---|---|---|
| `atrium.record.data` | фильтр | `array $data`, `Model $record`, `string $context` (`create` или `edit`) | Запись сохраняется в панели или через API, после `transformAdminData()` и до `fill()` |
| `atrium.record.saved` | действие | `Model $record`, `string $context` | После сохранения, синхронизации связей и `afterAdminSave()`, когда транзакция зафиксирована |
| `atrium.record.deleted` | действие | `Model $record` | После удаления записи в панели или через API, когда транзакция зафиксирована |
| `atrium.admin.menu` | фильтр | `array $items`, `?AdminUser $user` | Строится меню панели; пункт — `['label' => ..., 'route' => ..., 'icon' => ...]`, недоступные пользователю пункты отбрасываются после |
| `atrium.record.present` | фильтр | `array $data`, `Model $record`, `string $context` (`form` или `list`) | Запись отдаётся в форму редактирования или в строку списка, в том числе в API |
| `atrium.module.fields` | фильтр | `array $fields`, `string $model` | Читаются поля формы модуля: форма, проверка, API и его документация, MCP |
| `atrium.module.columns` | фильтр | `array $columns`, `string $model` | Читаются колонки списка модуля, в панели и для `search=` в API |
| `atrium.module.translatable` | фильтр | `array $names`, `string $model` | Имена полей модуля, у которых своё значение на каждом языке контента |
| `atrium.module.links` | фильтр | `array $links`, `string $model` | Кнопки над списком модуля: `['name' => ..., 'label' => ..., 'href' => ...]` |
| `atrium.module.actions` | фильтр | `array $actions`, `string $model` | Кнопки у записи модуля, в строке списка и на форме: `CreateFromRecord`, `LinkAction`, `RecordAction` (см. «Колонки списка») |
| `atrium.module.bulk_actions` | фильтр | `array $actions`, `string $model` | Кнопки над отмеченными в списке модуля записями, и в API: `BulkAction`; встроенное «Удалить» идёт первым |
| `atrium.query.filter` | фильтр | `bool $handled`, `Builder $query`, `string $name`, `mixed $value` | Список или API фильтрует по имени; верните `true`, если условие вы добавили сами |
| `atrium.query.sort` | фильтр | `bool $handled`, `Builder $query`, `string $name`, `string $direction` | То же для сортировки |
| `atrium.query.search` | фильтр | `bool $handled`, `Builder $query`, `string $name`, `string $search` | То же для одной колонки поиска, внутри группы `or` поиска |
| `atrium.module.installed` | действие | `Module $module` | `atrium:module:install` выполнил миграции и внёс модуль в список |
| `atrium.module.removed` | действие | `string $package`, `bool $dataDropped` | `atrium:module:remove` убрал модуль |
| `atrium.module.enabled` | действие | `Module $module` | суперадмин включил модуль |
| `atrium.module.disabled` | действие | `Module $module` | суперадмин выключил модуль |

Хуки записей видят только то, что сохраняют панель и API; сидер или импорт, пишущий через Eloquent, их не вызывает (для этого слушайте события самого Eloquent).

### Каталог событий

Действие — это имя, которое знает только его модуль. Чтобы его услышали правила автоматизации, вебхуки и внешние приложения, объявите его в каталоге: ключ, который видят люди и программы, подпись, запись, о которой событие, и данные, которые оно сообщает. Хук запускайте как и раньше.

```php
use Atrium\Admin\Extensions\Event;

// В boot() сервис-провайдера, пока модуль установлен:
Hook::register('atrium.events', 'shop.order.paid', Event::make('atrium.order.paid')
    ->label('shop::events.order_paid')                      // текст или ключ перевода
    ->subject(Order::class)                                  // первый аргумент хука — запись
    ->payload(fn (Order $order): array => ['total' => $order->total, 'currency' => $order->currency]));
```

- **Ключ** (`shop.order.paid`) публичный: называйте его `{module}.{what}.{happened}` и меняйте так же осторожно, как API.
- **Предмет** — первый аргумент хука, модель этого класса; запуск с чем-то другим — не это событие. Кто может услышать событие, будет решать модуль предмета.
- **Данные** получают аргументы хука и возвращают простые значения, которые уходят JSON-ом: без секретов и без моделей. Всё, что не массив, считается отсутствием данных.
- `->when(fn (...$args): bool => …)` делает из одного хука несколько событий; считается только `true`.

У каждого модуля панели есть три события без единой строки кода: `{module}.created`, `{module}.updated` и `{module}.deleted` (`posts.created`); они слышны из `atrium.record.saved` и `atrium.record.deleted`, то есть из панели, API и MCP.

Когда срабатывает объявленный хук, каталог запускает одно действие `atrium.event.occurred` с `Atrium\Admin\Extensions\Occurrence` на каждое событие, которое означает этот запуск; пока его никто не слушает, ничего не собирается:

```php
Hook::on('atrium.event.occurred', function (Occurrence $occurrence): void {
    $occurrence->event;    // 'shop.order.paid'
    $occurrence->subject;  // Order
    $occurrence->module;   // 'orders' или null, если предмет — не модуль панели
    $occurrence->data;     // ['total' => 120, 'currency' => 'EUR']
    $occurrence->actor;    // администратор, который это сделал; null в консоли и в очереди
    $occurrence->toArray(); // id, event, occurred_at, site, actor, subject, data — то, что отправит вебхук
});
```

Каталог начинает слушать, когда загрузились все модули, поэтому объявляйте события в `boot()`. Объявленное позже (в тесте) слышно после `app(Events::class)->listen()`. `php artisan atrium:events` выводит каталог: ключ, подпись, хук и модуль предмета. Это первая часть [платформы расширений](extensions.ru.md).

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
