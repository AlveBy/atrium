# Atrium

[English](../../README.md) · **Русский** · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

Одна админка для любого числа многоязычных сайтов на Laravel 13 и переносчик, который забирает готовый сайт с 1С-Битрикс.

Модуль описывается один раз: модель, её поля и колонки списка. Остальное Atrium делает сам: список с поиском и сортировкой, форму с вкладками, пункт меню, API, а вместе с темой ещё и страницы на сайте.

![Список товаров демо-магазина](../images/admin-list.png)

> **До первого релиза.** Atrium работает и покрыт тестами, но версия 0.1 ещё не вышла, и пакетов на Packagist пока нет. Попробовать можно из этого репозитория.

## Что умеет

- **Много сайтов.** Одна админка и одна база на много сайтов. Сайт определяется по домену или по папке на нём (`example.com/site2/`), языки у каждого свои.
- **Много языков.** Админка говорит на 15 языках, каждый администратор выбирает свой. Языки контента добавляются в админке, переводимой можно сделать любую колонку модуля, у каждого языка свои адреса (`/de/…`) и `hreflang`.
- **20 типов полей.** Текст, числа, даты, списки, привязки к другим модулям, картинки, галереи, файлы, визуальный редактор, карты, множественные значения. Форма разбита на вкладки, как в 1С-Битрикс.
- **Роли и права.** Кто какой модуль видит и что в нём может: смотреть, добавлять, менять, удалять.
- **SEO без программиста.** Заголовки и описания у каждой записи, человекопонятные адреса с переадресацией со старых, `sitemap.xml` и `robots.txt` для каждого сайта. Google Tag, Яндекс Метрика и коды подтверждения задаются в админке для каждого сайта.
- **API.** Токены с доступом только к выбранным модулям и действиям; `/api/v1` сам рассказывает, что в нём есть. ИИ-ассистенты (Claude, ChatGPT, Cursor) подключаются к `/mcp` с тем же токеном и ровно с его правами.
- **Код входа на почту.** Вход в два шага включён по умолчанию.
- **Переносчик с 1С-Битрикс.** Инфоблоки, свойства, разделы, формы, раскладки списков, SEO и медиабиблиотека превращаются в модели Laravel, миграции и данные, а отчёт сверяет старый сайт с новым.
- **Тема для сайта.** Нейтральный сайт, который рисует любой модуль по его форме в админке. Светлая и тёмная, читается при любом цветовом зрении.
- **Тесты.** PHPUnit на SQLite, MySQL и PostgreSQL, браузерные тесты на Laravel Dusk.

| Форма записи | Сайт, светлый и тёмный |
|---|---|
| ![Форма товара с вкладками](../images/admin-form.png) | ![Страница товара](../images/site-light.png) ![Новость на немецком, тёмная тема](../images/site-dark.png) |

## Попробовать

С Docker больше ничего не нужно:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

Первый запуск идёт несколько минут. Потом открываете http://localhost:8000/admin и входите как `admin@example.com` с паролем `password` (только для пробы на своём компьютере). Сайт на http://localhost:8000.

Без Docker нужны PHP 8.3 или новее и Composer. База по умолчанию SQLite, админка уже собрана, так что ни MySQL, ни Node не нужны:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

Установщик спросит имя, почту и пароль первого администратора. Код входа приходит на почту; на своём компьютере без почты он лежит в конце `storage/logs/laravel.log`, а выключить его можно строкой `ATRIUM_2FA_ENABLED=false` в `.env`.

`--demo` добавляет небольшой магазин: товары со всеми типами полей, бренды, магазины, новости на трёх языках и второй сайт. Чтобы начать заново, удалите `database/database.sqlite` и установите ещё раз.

## Первый модуль

```bash
php artisan atrium:make-module Article
php artisan migrate
```

Команда создаёт модель, миграцию и строку в `config/atrium.php`, и в меню появляется «Articles». Модель описывает свою форму и список:

```php
class Article extends Model implements Administrable
{
    use InteractsWithAdmin, Translatable;

    protected array $translatable = ['name', 'code', 'preview_text', 'detail_text'];

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label('Name')->required(),
            SlugField::make('code')->label('Code')->from('name')->unique('articles', 'code'),
            CheckboxField::make('active')->label('Active')->default(true),
            ContentField::make('detail_text')->label('Detail text'),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('name')->label('Name')->sortable()->searchable(),
            BooleanColumn::make('active')->label('Active'),
        ];
    }
}
```

Чтобы у модуля появились страницы на сайте, добавьте его в `public_urls` в `config/atrium.php`:

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## Переезд с 1С-Битрикс

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

База Битрикса только читается. Вкладки, порядок полей и колонки списков получаются такими, какими их настроили редакторы в Битриксе. `atrium:bitrix:sync` догоняет правки, которые вносят на старом сайте, пока он ещё работает. По шагам: [packages/bitrix/README.md](../../packages/bitrix/README.md) (на английском).

## Документация

- [Установка: свой сервер, хостинг с ISPmanager или cPanel, Docker](../installation.ru.md)
- [Настройка: почта и код входа, файлы, API-токены, ИИ-ассистенты через MCP](../configuration.ru.md)
- [ИИ-помощник для текстов: бесплатные и платные провайдеры, описания, SEO, alt, переводы](../ai.ru.md)
- [Свой модуль: поля, колонки, вкладки, права, языки, тесты, пакет](../modules.ru.md)
- [Переезд с 1С-Битрикс по шагам](../bitrix.ru.md)
- [Резервные копии: базы и файлы в несколько мест, расписание, проверки, восстановление](../backup.ru.md)
- [CDN: картинки, уменьшенные копии и WebP/AVIF, сброс кеша, загрузки в S3](../cdn.ru.md)

## Пакеты

| Пакет | Что это |
|---|---|
| [`alveby/atrium`](../../packages/admin) | Админка |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | Тема для сайта |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | Переносчик с 1С-Битрикс |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | ИИ-помощник для текстов в админке |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Документация из Markdown: версии и поиск |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | Резервные копии баз и файлов, восстановление из админки |

Когда 0.1 появится на Packagist, в готовое приложение на Laravel 13 Atrium будет ставиться так:

```bash
composer require alveby/atrium
php artisan atrium:install
```

## Что нужно

- PHP 8.3 или новее
- Laravel 13
- SQLite, MySQL 8 или PostgreSQL

## Языки

Админка говорит на английском, русском, украинском, белорусском, казахском, узбекском, немецком, французском, испанском, португальском (Бразилия), итальянском, польском, турецком, китайском (упрощённом) и японском. Английский и русский написаны вручную, остальные начинались с машинного перевода, как и этот README на других языках. Поправки от носителей языка — один из лучших первых вкладов в проект, подробнее в [CONTRIBUTING.md](../../CONTRIBUTING.md) (на английском).

## Как всё началось

Atrium появился в декабре 2025 года в [AlveSoft](https://alve.by) как наша собственная панель управления на замену 1С-Битрикс для сайта заказчика [arkaim.by](https://arkaim.by). Хотелось админку, в которой редактору так же привычно, как в Битриксе, но на обычном Laravel, и мигратор, который перенесёт содержимое без потерь. С лета 2026 года панель работает на этом сайте: редакторы каждый день ведут в ней содержимое, а мигратор перенёс туда весь каталог, страницы и формы.

В ноябре 2026 года мы решили отдать её всем. Убрали всё, что относилось к тому одному сайту, а остальное выложили под лицензией MIT, чтобы любой мог бесплатно пользоваться ею, менять её и строить на ней свои сайты.

## Что дальше

[План развития](../../ROADMAP.ru.md): модули, которые ставятся одной командой, готовые сайты, переезд с WordPress и Drupal, интернет-магазин, 1С и Битрикс24.

## Участие

В [CONTRIBUTING.md](../../CONTRIBUTING.md) написано, как поднять проект, запустить тесты и переводить. Об уязвимостях сообщайте так, как описано в [SECURITY.md](../../SECURITY.md).

## Лицензия

MIT, см. [LICENSE](../../LICENSE).

Если Atrium вам помог, пожалуйста, оставьте ссылку «Сделано на Atrium» в подвале сайта. Это просьба, а не условие лицензии: MIT требует только сохранять уведомление об авторском праве в копиях кода.
