# Поиск

[English](search.md)

Модуль поиска (`alveby/atrium-search`) даёт сайту один поиск по всем установленным модулям: посетитель пишет пару слов в шапке и получает страницы текущего сайта на текущем языке, сгруппированные по модулям, с выделенными словами. Редакторы получают строку поиска в шапке админки, которая находит записи всех модулей.

## Установка

```bash
composer require alveby/atrium-search
php artisan atrium:module:install alveby/atrium-search
php artisan atrium:search:rebuild
```

Первая команда создаёт таблицу индекса, вторая заполняет её уже существующими записями. Дальше индекс следует за каждым сохранением и удалением.

## Что ищется

Каждая модель с публичными страницами, если не сказано иначе. Запись находится по `name` и по текстовым полям своей формы в админке (`ContentField`, `TextareaField`, `WysiwygField`) — простым текстом, без тегов. Переведённая запись находится на каждом языке по тексту этого языка, непереведённая — на всех языках.

Запись находится только там, где посетитель мог бы её открыть: если она активна, между `active_from` и `active_to`, на своём сайте (или на всех сайтах — у модулей, общих для всех сайтов) и если у неё есть страница на этом языке.

Модуль может сказать больше в своём `composer.json`:

```json
"extra": {
    "atrium": {
        "search": {
            "Acme\\Blog\\Models\\Post": {"title": "name", "content": ["preview_text", "detail_text"]},
            "Acme\\Blog\\Models\\Draft": false
        }
    }
}
```

Последнее слово за приложением, в `config/atrium.php`:

```php
'search' => [
    App\Models\Article::class => ['title' => 'name', 'content' => ['preview_text', 'detail_text']],
    App\Models\Vacancy::class => false,
],
```

Модель, которой нужно больше (цена, название бренда), пишет текст сама:

```php
public function toSearchDocument(string $language): ?array
{
    return ['title' => $this->name, 'content' => $this->brand?->name.' '.$this->description];
}
```

`null` убирает запись из поиска на этом языке. После смены правил запустите `php artisan atrium:search:rebuild`.

Пакет может поменять список в коде, после манифестов и конфига, фильтром `atrium.search.sources`: он получает список (модель => заголовок и текст) и возвращает его.

```php
use Atrium\Admin\Facades\Hook;

Hook::filter('atrium.search.sources', fn (array $models) => [
    ...$models,
    Acme\Events\Models\Event::class => ['title' => 'name', 'content' => ['description']],
]);
```

## Как сравниваются слова

Каждое слово запроса должно найтись, и слово находит более длинные (`lamp` находит `lamps`). На MySQL и MariaDB индекс — `FULLTEXT`, на PostgreSQL — индекс `to_tsvector('simple', …)`; на SQLite, а также для слов из одной-двух букв, китайского, японского и стоп-слов MySQL поиск читает текст через `LIKE`. Если целые слова и их начала ничего не нашли, поиск ищет слова и внутри более длинных, так что `schlauch` находит `Gartenschlauch`. Результаты модуля идут по месту слова: сначала найденные в заголовке, потом самые новые.

## На сайте

С темой (`alveby/atrium-theme`) в шапке появляется строка поиска, а у сайта — страница `/search` (и `/de/search` для других языков, имя маршрута `atrium.search`):

- без выбранного модуля — первые результаты каждого модуля и ссылка на все;
- `?in=news` — результаты одного модуля постранично;
- у каждого результата заголовок и кусок текста вокруг первого найденного слова, слова в `<mark>`;
- страница закрыта от индексации (`noindex`).

Настройки — в `config/atrium-search.php` (`vendor:publish --tag=atrium-search-config`):

```php
'per_page' => 10,     // результатов на странице одного модуля
'group_limit' => 5,   // результатов каждого модуля на первой странице
'min_length' => 2,    // более короткие запросы не ищутся
```

Чтобы поставить строку поиска в другое место, подключите её шаблон:

```blade
@include('atrium-search::box')
```

## В админке

Строка в шапке находит записи всех модулей, которые редактору можно видеть, так же, как их находит список самого модуля (по колонкам с поиском, на всех языках, число находит id), в том числе неактивные. Клик открывает форму записи. На экране уже 1280px вместо строки — кнопка с лупой, она открывает поле под шапкой, чтобы переключателям сайта и языка хватило места; на телефоне строки нет.

## API и MCP

`GET /api/v1/site-search?q=…` находит то же, что нашёл бы посетитель, в модулях, которые токену можно читать (`{module}:read`):

```bash
curl -H "Authorization: Bearer <token>" "https://example.com/api/v1/site-search?q=lamp&module=products"
```

```json
{"data": [{"module": "products", "id": 5, "title": "Desk lamp", "url": "https://example.com/catalog/desk-lamp", "snippet": "A warm light for the desk…"}],
 "counts": {"products": 1}, "meta": {"page": 1, "per_page": 10, "total": 1, "last_page": 1}}
```

Параметры: `page`, `per_page` (до 50), `module`, `locale` (язык контента; без него — язык по умолчанию) и `site` (код другого сайта). ИИ-ассистенты получают то же как MCP-инструмент `search_site`.

## Индекс

Таблица `search_index` хранит одну строку на запись и язык: заголовок, простой текст, адрес страницы, сайт, язык и даты. Она заполняется при сохранении; записи, изменённые в обход моделей (импорт простыми запросами), догоняет команда:

```bash
php artisan atrium:search:rebuild
php artisan atrium:search:rebuild --model="Atrium\News\Models\News"
```

## Свой вид

```bash
php artisan vendor:publish --tag=atrium-search-views
php artisan vendor:publish --tag=atrium-search-lang
```
