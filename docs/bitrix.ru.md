# Переезд с 1С-Битрикс

[English](bitrix.md) · [Русский](bitrix.ru.md)

`alveby/atrium-bitrix` переносит содержимое сайта на Битриксе в Atrium: инфоблоки становятся модулями с теми же вкладками, порядком полей и колонками списка, что были у редакторов, а следом переезжают данные, файлы, SEO и медиабиблиотека. Потом он доказывает, что ничего не потерялось.

База Битрикса только читается. Старый сайт работает, пока вы не переключите домен.

- [Что нужно](#что-нужно)
- [1. Подключить базу Битрикса](#1-подключить-базу-битрикса)
- [2. Принести файлы](#2-принести-файлы)
- [3. Перенести](#3-перенести)
- [4. Проверить](#4-проверить)
- [5. Догнать и переключить](#5-догнать-и-переключить)
- [Что пока не переносится](#что-пока-не-переносится)

## Что нужно

- Установленный Atrium ([Установка](installation.ru.md)), лучше сначала у себя на компьютере: перенос можно повторять, а боевой сайт — потом.
- Копия базы Битрикса (дамп из панели хостинга или `mysqldump`), залитая в MySQL, до которого дотягивается ваш Atrium. Запускать сам Битрикс не нужно.
- Папка `/upload` Битрикса или доступ к ней по FTP.

```bash
composer require alveby/atrium-bitrix --dev
php artisan vendor:publish --tag=atrium-bitrix-config
```

## 1. Подключить базу Битрикса

Добавьте в `config/database.php`, рядом с остальными, подключение `bitrix`:

```php
'bitrix' => [
    'driver' => 'mysql',
    'host' => env('BITRIX_DB_HOST', '127.0.0.1'),
    'port' => env('BITRIX_DB_PORT', '3306'),
    'database' => env('BITRIX_DB_DATABASE', 'bitrix'),
    'username' => env('BITRIX_DB_USERNAME', 'root'),
    'password' => env('BITRIX_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

а значения — в `.env`. Посмотрите, что там лежит:

```bash
php artisan atrium:bitrix:analyze
```

Команда покажет типы инфоблоков, сами инфоблоки, число свойств, разделов и элементов. Если коды инфоблоков начинаются с префикса темы (`mytheme_news`), впишите его в `strip_prefixes` в `config/atrium-bitrix.php` — тогда таблица будет `news`, а модель `News`.

## 2. Принести файлы

Скопируйте папку `upload` Битрикса в `public/upload` нового сайта или скачайте её по FTP (сначала задайте `BITRIX_FTP_HOST`, `BITRIX_FTP_USER`, `BITRIX_FTP_PASSWORD` и `BITRIX_FTP_ROOT`):

```bash
php artisan atrium:bitrix:download:upload
```

## 3. Перенести

```bash
php artisan atrium:bitrix:migrate
```

выполняет всё по порядку: сайты, миграции по инфоблокам, `migrate`, модели с формами админки, данные, SEO и медиабиблиотеку. Каждый шаг можно запускать и отдельно, в таком порядке:

```bash
php artisan atrium:bitrix:migrate:sites
php artisan atrium:bitrix:generate:migrations
php artisan migrate
php artisan atrium:bitrix:generate:models
php artisan atrium:bitrix:migrate:data
php artisan atrium:bitrix:migrate:seo
php artisan atrium:bitrix:migrate:medialibrary
```

Каждая команда спрашивает, какие инфоблоки брать; с `--no-interaction` берёт все.

`migrate:sites` переносит сайты (`b_lang`) с их доменами. `DIR` сайта становится его папкой: `/site2/` открывает сайт по адресу `example.com/site2/`, как раньше. Сайт по умолчанию в Atrium живёт в корне; если в Битриксе он лежит в папке (`/ru/`), команда предупредит, и для старых адресов нужен редирект.

Модели появятся в `app/Models`. Те, что нужны редакторам, перечислите в `config/atrium.php` → `resources`, и они окажутся в меню админки:

```php
'resources' => [
    App\Models\News::class,
    App\Models\Catalog::class,
    App\Models\CatalogSection::class,
],
```

Сгенерированные модели перегенерируют, а не правят руками: то, что вы меняете сознательно, пишется в `config/atrium-bitrix.php` (`extra_fields`, `hidden_list_columns`, `extra_list_columns`), а после перегенерации смотрите `git diff app/Models`.

Статические страницы — `.php`-файлы вне инфоблоков («О компании», «Контакты») — переезжают с модулем «Страницы» из копии файлов сайта:

```bash
composer require alveby/atrium-pages
php artisan migrate
php artisan atrium:pages:import-bitrix /path/to/bitrix/site
```

`/about/index.php` становится страницей `/about`, `/about/team.php` — страницей `/about/team`; заголовки, тексты и свойства `title`, `description` и `keywords` переносятся, а старые адреса с `.php` перенаправляют на страницы. Что не переносится и почему — в [README модуля](../packages/pages/README.md#moving-from-1c-bitrix).

## 4. Проверить

```bash
php artisan atrium:bitrix:parity
php artisan atrium:bitrix:verify
```

`parity` сверяет каждый инфоблок и свойство с моделями и перенесёнными строками: количество, колонки, типы полей, обязательность, варианты списков, вкладки и порядок полей формы, колонки списка, а у каждого сайта — папку, домены и активность. Пустой отчёт значит, что ничего не потерялось; `--format=markdown` пишет его в файл для заказчика. `verify` сравнивает количество и выборочно проверяет несколько записей каждого инфоблока (`--sample=5`).

Это проверка данных. Открывается ли на новом сайте каждая страница старого, проверяют по живому старому сайту: чек-лист — [MIGRATION-QA.md](../packages/bitrix/MIGRATION-QA.md).

## 5. Догнать и переключить

Пока вы собираете новый сайт, редакторы продолжают работать в Битриксе. Перед переключением перенесите их правки со свежего дампа:

```bash
php artisan atrium:bitrix:sync --audit
php artisan atrium:bitrix:sync
```

`--audit` показывает расхождения и ничего не пишет; без него копируются только новые и изменённые элементы, а `--prune` удаляет то, что удалили в Битриксе. Колонки из `sync_protected` сохраняют значение нового сайта.

Мигратор — зависимость для разработки, поэтому последний `sync` выполняется там же, где делали перенос. Потом перенесите эту базу на боевой сайт ([Установка](installation.ru.md)) и направьте на него домен.

## Что пока не переносится

- **Главная и страницы из компонентов**: `/index.php` и страницы, собранные из компонентов, — не статические страницы; новый сайт рисует их по модулям.
- **Шаблоны и компоненты**: новый сайт рисует тема (`alveby/atrium-theme`) по формам модулей, или ваши собственные шаблоны Blade.
- **Пользователи, заказы и магазин**: мигратор переносит контент. Магазин и обмен с 1С — в [плане развития](../ROADMAP.ru.md).
