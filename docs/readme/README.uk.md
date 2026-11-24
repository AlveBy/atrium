# Atrium

[English](../../README.md) · [Русский](README.ru.md) · **Українська** · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml) [![coverage 100%](https://img.shields.io/badge/coverage-100%25-brightgreen)](../testing.md)

_Цю сторінку перекладено з англійської машинно. Виправлення вітаються, див. [CONTRIBUTING.md](../../CONTRIBUTING.md)._

Одна адмінка для будь-якої кількості багатомовних сайтів на Laravel 13 і мігратор, який переносить готовий сайт з 1С-Бітрікс.

Модуль описується один раз: модель, її поля та колонки списку. Решту Atrium робить сам: список із пошуком і сортуванням, форму з вкладками, пункт меню, API, а разом із темою ще й сторінки на сайті.

![Список товарів демо-магазину](../images/admin-list.webp)

> **До першого релізу.** Atrium працює й покритий тестами, але версія 0.1 ще не вийшла, і пакетів на Packagist поки немає. Спробувати його можна з цього репозиторію.

## Що вміє

- **Багато сайтів.** Одна адмінка й одна база для багатьох сайтів. Сайт визначається за доменом або за папкою на ньому (`example.com/site2/`), мови в кожного свої.
- **Багато мов.** Адмінка розмовляє 15 мовами, кожен адміністратор обирає свою. Мови контенту додаються в адмінці, перекладною можна зробити будь-яку колонку модуля, а в кожної мови свої адреси (`/de/…`) і `hreflang`.
- **20 типів полів.** Текст, числа, дати, списки, зв'язки з іншими модулями, зображення, галереї, файли, візуальний редактор, карти, множинні значення. Форма поділена на вкладки, як в 1С-Бітрікс.
- **Ролі та права.** Хто який модуль бачить і що в ньому може робити: переглядати, створювати, редагувати, видаляти.
- **SEO без програміста.** Заголовки й описи для кожного запису, зрозумілі адреси з переадресацією зі старих, `sitemap.xml` і `robots.txt` для кожного сайту. Google Tag, Яндекс Метрика й коди підтвердження задаються в адмінці для кожного сайту.
- **API.** Токени з доступом лише до вибраних модулів і дій; `/api/v1` сам розповідає, що в ньому є. ШІ-асистенти (Claude, ChatGPT, Cursor) підключаються до `/mcp` з тим самим токеном і рівно з його правами.
- **Код входу на пошту.** Двофакторний вхід увімкнено за замовчуванням.
- **Мігратор з 1С-Бітрікс.** Інфоблоки, властивості, розділи, форми, розкладки списків, SEO та медіабібліотека перетворюються на моделі Laravel, міграції й дані, а звіт порівнює старий сайт із новим.
- **Тема для сайту.** Нейтральний сайт, який малює будь-який модуль за його формою в адмінці. Світла й темна, читається за будь-якого колірного зору.
- **Тести.** PHPUnit на SQLite, MySQL і PostgreSQL, браузерні тести на Laravel Dusk.

| Форма запису | Пошук в адмінці |
|---|---|
| ![Форма запису](../images/admin-form.webp) | ![Пошук в адмінці](../images/admin-search.webp) |

| Меню | Робочий стіл |
|---|---|
| ![Меню](../images/admin-menus.webp) | ![Робочий стіл](../images/admin-dashboard.webp) |

| Сайт, світла тема | Сайт, темна тема |
|---|---|
| ![Сайт, світла тема](../images/site-light.webp) | ![Сайт, темна тема](../images/site-dark.webp) |

## Якість

- Понад 6500 тестів PHPUnit і понад 80 браузерних тестів на Laravel Dusk запускаються на кожному pull request.
- На SQLite, MySQL і PostgreSQL, під PHP 8.3 і 8.4.
- У кожного пакета тестами покрито 100% рядків, і CI не пропустить зміну, після якої хоч один рядок лишився без тесту.
- Змінений код має пройти мутаційне тестування на 100%: Infection псує кожен змінений рядок, і на кожне псування має впасти тест.
- З цих перевірок нічого не виключено: ні позначок «не перевіряти», ні вимкнених мутаторів.
- Кожен push перевіряється на залежності з відомими вразливостями та на паролі й ключі в історії git.

[Як влаштовані тести й перевірки](../testing.md).

## Спробувати

З Docker більше нічого не потрібно:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

Перший запуск триває кілька хвилин. Потім відкрийте http://localhost:8000/admin і увійдіть як `admin@example.com` з паролем `password` (лише для спроб на власному комп'ютері). Сайт — на http://localhost:8000.

Без Docker потрібні PHP 8.3 або новіший і Composer. База за замовчуванням — SQLite, а адмінка вже зібрана, тож ні MySQL, ні Node не потрібні:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

Інсталятор запитає ім'я, пошту й пароль першого адміністратора. Код входу надходить поштою; на власному комп'ютері без пошти він лежить у кінці `storage/logs/laravel.log`, а вимкнути його можна рядком `ATRIUM_2FA_ENABLED=false` у `.env`.

`--demo` додає невеликий магазин: товари з усіма типами полів, бренди, магазини, новини трьома мовами й другий сайт. Щоб почати спочатку, видаліть `database/database.sqlite` і встановіть ще раз.

## Перший модуль

```bash
php artisan atrium:make-module Article
php artisan migrate
```

Команда створює модель, міграцію й рядок у `config/atrium.php`, і в меню з'являється «Articles». Модель описує свою форму та список:

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

Щоб у модуля з'явилися сторінки на сайті, додайте його до `public_urls` у `config/atrium.php`:

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## Переїзд з 1С-Бітрікс

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

База Бітрікса лише читається. Вкладки, порядок полів і колонки списків виходять такими, якими їх налаштували редактори в Бітріксі. `atrium:bitrix:sync` підтягує правки, які вносять на старому сайті, поки він ще працює. Покроково: [packages/bitrix/README.md](../../packages/bitrix/README.md) (англійською).

## Документація

- [Встановлення: власний сервер, хостинг з ISPmanager або cPanel, Docker](../installation.md)
- [Публічне демо: Docker, скидання щогодини, що може відвідувач, де розмістити](../demo.md)
- [Налаштування: пошта й код входу, файли, API-токени, ШІ-асистенти через MCP](../configuration.md)
- [ШІ-помічник для текстів: безкоштовні й платні провайдери, описи, SEO, alt, переклади](../ai.md)
- [SEO: заголовки, hreflang, карти сайту, schema.org, редиректи, SEO-перевірка](../seo.md)
- [Меню: шапка й підвал, дерево посилань, пункти, що розгортають модуль](../menus.md)
- [Форми: конструктор у колонках, надсилання без перезавантаження, у вікні, своя розмітка і fetch](../forms.md)
- [Пошук: сторінка результатів, рядок в адмінці, що і як шукається](../search.md)
- [Власний модуль: поля, колонки, вкладки, права, мови, тести, пакет](../modules.md)
- [Власні поля: додати поле в будь-який модуль з адмінки, фільтри й фасети на 100 000 записів](../custom-fields.md)
- [Імпорт і вивантаження: CSV і Excel для будь-якого модуля, колонки, перевірка перед записом, консоль і розклад](../import-export.md)
- [Кеш: швидкі сторінки з десятками властивостей, Redis, кеш сторінок, заголовки для CDN](../cache.md)
- [Переїзд з 1С-Бітрікс крок за кроком](../bitrix.md)
- [Переїзд з WordPress: підключити базу, перенести, перевірити, мапа 301](../wordpress.md)
- [Резервні копії: бази й файли в кілька місць, розклад, перевірки, відновлення](../backup.md)
- [Помилки: помилки PHP, черг і JavaScript у базі сайту, сповіщення, Sentry і власні драйвери](../errors.md)
- [Швидкість і доступність: заміри сервера, Web Vitals відвідувачів, перевірки доступності зі сповіщеннями](../monitoring.md)
- [CDN: зображення, зменшені копії й WebP/AVIF, очищення кешу, завантаження в S3](../cdn.md)
- [Двофакторний вхід: застосунки-автентифікатори, ключі доступу, коди, журнал входів](../two-factor.md)
- [Вхід через соцмережі: провайдери, реєстрація застосунку, адреси перенаправлення](../social-login.md)

## Пакети

| Пакет | Що це |
|---|---|
| [`alveby/atrium`](../../packages/admin) | Адмінка |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | Тема для сайту |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | Мігратор з 1С-Бітрікс |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | ШІ-помічник для текстів в адмінці |
| [`alveby/atrium-custom-fields`](../../packages/custom-fields/README.md) | Поля, які додають у будь-який модуль з адмінки, з фільтрами й фасетами |
| [`alveby/atrium-seo`](../../packages/seo/README.md) | Шаблони мета-тегів, hreflang, карти сайту, schema.org, редиректи та SEO-перевірка |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Документація з Markdown: версії та пошук |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | Резервні копії баз і файлів, відновлення з адмінки |
| [`alveby/atrium-errors`](../../packages/errors/README.md) | Облік помилок у базі сайту, сповіщення, Sentry |
| [`alveby/atrium-monitoring`](../../packages/monitoring/README.md) | Швидкість сервера й відвідувачів, доступність і сповіщення, без зовнішніх сервісів |
| [`alveby/atrium-news`](../../packages/news/README.md) | Новини |
| [`alveby/atrium-pages`](../../packages/pages/README.md) | Статичні сторінки з імпортом сторінок 1С-Бітрікс |
| [`alveby/atrium-catalog`](../../packages/catalog/README.md) | Каталог товарів із вкладеними розділами |
| [`alveby/atrium-brands`](../../packages/brands/README.md) | Бренди з логотипами |
| [`alveby/atrium-stores`](../../packages/stores/README.md) | Магазини й офіси на карті |
| [`alveby/atrium-team`](../../packages/team/README.md) | Люди, відділи та контакти |
| [`alveby/atrium-faq`](../../packages/faq/README.md) | Питання й відповіді |
| [`alveby/atrium-forms`](../../packages/forms/README.md) | Конструктор форм і заявки |
| [`alveby/atrium-reviews`](../../packages/reviews/README.md) | Відгуки та оцінки з модерацією |
| [`alveby/atrium-2fa`](../../packages/two-factor/README.md) | Двофакторний і безпарольний вхід |
| [`alveby/atrium-social-login`](../../packages/social-login/README.md) | Вхід через Google, Microsoft, Apple, GitHub, Telegram та інші |
| [`alveby/atrium-solutions`](../../packages/solutions/README.md) | Готові сайти, що встановлюються однією командою |
| [`alveby/atrium-menus`](../../packages/menus/README.md) | Меню, зібрані в адмінці |
| [`alveby/atrium-search`](../../packages/search/README.md) | Пошук по сайту в усіх модулях |
| [`alveby/atrium-wordpress`](../../packages/wordpress/README.md) | Імпортер WordPress: записи, сторінки, власні типи записів, SEO та переклади, з перевіркою і мапою 301 |

Пакети вже на Packagist, але доки 0.1 не позначено тегом — лише як версія для розробки, тож вкажіть її:

```bash
composer require alveby/atrium:^0.2
php artisan atrium:install
```

## Вимоги

- PHP 8.3 або новіший
- Laravel 13
- SQLite, MySQL 8 або PostgreSQL

## Мови

Адмінка розмовляє англійською, російською, українською, білоруською, казахською, узбецькою, німецькою, французькою, іспанською, португальською (Бразилія), італійською, польською, турецькою, китайською (спрощеною) та японською. Англійську й російську написано вручну, решта починалася з машинного перекладу, як і цей README іншими мовами. Виправлення від носіїв мови — один із найкращих перших внесків у проєкт, докладніше в [CONTRIBUTING.md](../../CONTRIBUTING.md) (англійською).

## Як усе починалося

Atrium з’явився в грудні 2025 року в [AlveSoft](https://alve.by) як наша власна адмінка на заміну 1С-Бітрікс для сайту замовника [arkaim.by](https://arkaim.by). Хотілося адмінку, у якій редактору так само звично, як у Бітріксі, але на звичайному Laravel, і мігратор, який перенесе вміст без втрат. Від літа 2026 року панель працює на цьому сайті: редактори щодня ведуть у ній вміст, а мігратор переніс туди весь каталог, сторінки та форми.

У листопаді 2026 року ми вирішили віддати її всім. Прибрали все, що стосувалося того єдиного сайту, а решту виклали під ліцензією MIT, щоб будь-хто міг безкоштовно користуватися нею, змінювати її і будувати на ній свої сайти.

## Що далі

[План розвитку](../../ROADMAP.md) (англійською): модулі, що встановлюються однією командою, готові сайти, переїзд з WordPress і Drupal, інтернет-магазин, 1С і Бітрікс24.

## Участь

У [CONTRIBUTING.md](../../CONTRIBUTING.md) описано, як розгорнути проєкт, запустити тести й перекладати. Про вразливості повідомляйте так, як описано в [SECURITY.md](../../SECURITY.md).

## Ліцензія

MIT, див. [LICENSE](../../LICENSE).

Якщо Atrium вам допоміг, будь ласка, залиште посилання «Зроблено на Atrium» у футері сайту. Це прохання, а не умова ліцензії: MIT вимагає лише зберігати повідомлення про авторське право в копіях коду.
