# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · **Беларуская** · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml) [![coverage 100%](https://img.shields.io/badge/coverage-100%25-brightgreen)](../testing.md)

_Гэтая старонка перакладзена з англійскай машынным спосабам. Выпраўленні вітаюцца, гл. [CONTRIBUTING.md](../../CONTRIBUTING.md)._

Адна адмінка для любой колькасці шматмоўных сайтаў на Laravel 13 і мігратар, які пераносіць гатовы сайт з 1С-Бітрыкс.

Модуль апісваецца адзін раз: мадэль, яе палі і слупкі спіса. Астатняе Atrium робіць сам: спіс з пошукам і сартаваннем, форму з укладкамі, пункт меню, API, а разам з тэмай яшчэ і старонкі на сайце.

![Спіс тавараў дэма-крамы](../images/admin-list.webp)

> **Да першага рэлізу.** Atrium працуе і пакрыты тэстамі, але версія 0.1 яшчэ не выйшла, і пакетаў на Packagist пакуль няма. Паспрабаваць яго можна з гэтага рэпазіторыя.

## Што ўмее

- **Шмат сайтаў.** Адна адмінка і адна база на шмат сайтаў. Сайт вызначаецца па дамене або па папцы на ім (`example.com/site2/`), мовы ў кожнага свае.
- **Шмат моў.** Адмінка размаўляе на 15 мовах, кожны адміністратар выбірае сваю. Мовы кантэнту дадаюцца ў адмінцы, перакладальным можна зрабіць любы слупок модуля, а ў кожнай мовы свае адрасы (`/de/…`) і `hreflang`.
- **20 тыпаў палёў.** Тэкст, лікі, даты, спісы, прывязкі да іншых модуляў, выявы, галерэі, файлы, візуальны рэдактар, карты, множныя значэнні. Форма падзелена на ўкладкі, як у 1С-Бітрыкс.
- **Ролі і правы.** Хто які модуль бачыць і што ў ім можа рабіць: праглядаць, ствараць, правіць, выдаляць.
- **SEO без праграміста.** Загалоўкі і апісанні для кожнага запісу, зразумелыя адрасы з пераадрасацыяй са старых, `sitemap.xml` і `robots.txt` для кожнага сайта. Google Tag, Яндэкс Метрыка і коды пацвярджэння задаюцца ў адмінцы для кожнага сайта.
- **API.** Токены з доступам толькі да выбраных модуляў і дзеянняў; `/api/v1` сам расказвае, што ў ім ёсць. ШІ-асістэнты (Claude, ChatGPT, Cursor) падключаюцца да `/mcp` з тым жа токенам і роўна з яго правамі.
- **Код уваходу на пошту.** Двухфактарны ўваход уключаны па змаўчанні.
- **Мігратар з 1С-Бітрыкс.** Інфаблокі, уласцівасці, раздзелы, формы, раскладкі спісаў, SEO і медыябібліятэка ператвараюцца ў мадэлі Laravel, міграцыі і даныя, а справаздача зверае стары сайт з новым.
- **Тэма для сайта.** Нейтральны сайт, які малюе любы модуль па яго форме ў адмінцы. Светлая і цёмная, чытаецца пры любым каляровым зроку.
- **Тэсты.** PHPUnit на SQLite, MySQL і PostgreSQL, браўзерныя тэсты на Laravel Dusk.

| Форма запісу | Пошук у адмінцы |
|---|---|
| ![Форма запісу](../images/admin-form.webp) | ![Пошук у адмінцы](../images/admin-search.webp) |

| Меню | Працоўны стол |
|---|---|
| ![Меню](../images/admin-menus.webp) | ![Працоўны стол](../images/admin-dashboard.webp) |

| Сайт, светлая тэма | Сайт, цёмная тэма |
|---|---|
| ![Сайт, светлая тэма](../images/site-light.webp) | ![Сайт, цёмная тэма](../images/site-dark.webp) |

## Якасць

- Больш за 6500 тэстаў PHPUnit і больш за 80 браўзерных тэстаў на Laravel Dusk запускаюцца на кожным pull request.
- На SQLite, MySQL і PostgreSQL, пад PHP 8.3 і 8.4.
- У кожнага пакета тэстамі пакрыта 100% радкоў, і CI не прапусціць змену, пасля якой хоць адзін радок застаўся без тэста.
- Зменены код павінен прайсці мутацыйнае тэставанне на 100%: Infection псуе кожны зменены радок, і на кожнае псаванне павінен упасці тэст.
- З гэтых праверак нічога не выключана: ні пазнак «не правяраць», ні адключаных мутатараў.
- Кожны push правяраецца на залежнасці з вядомымі ўразлівасцямі і на паролі і ключы ў гісторыі git.

[Як уладкаваныя тэсты і праверкі](../testing.md).

## Паспрабаваць

З Docker больш нічога не трэба:

```bash
git clone -b stable https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

Першы запуск займае некалькі хвілін. Потым адкрыйце http://localhost:8000/admin і ўвайдзіце як `admin@example.com` з паролем `password` (толькі для спроб на сваім камп'ютары). Сайт — на http://localhost:8000.

Без Docker патрэбныя PHP 8.3 або навейшы і Composer. База па змаўчанні — SQLite, а адмінка ўжо сабраная, так што ні MySQL, ні Node не патрэбныя:

```bash
git clone -b stable https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

Усталёўшчык спытае імя, пошту і пароль першага адміністратара. Код уваходу прыходзіць на пошту; на сваім камп'ютары без пошты ён ляжыць у канцы `storage/logs/laravel.log`, а выключыць яго можна радком `ATRIUM_2FA_ENABLED=false` у `.env`.

`--demo` дадае невялікую краму: тавары з усімі тыпамі палёў, брэнды, крамы, навіны на трох мовах і другі сайт. Каб пачаць нанова, выдаліце `database/database.sqlite` і ўсталюйце яшчэ раз.

## Першы модуль

```bash
php artisan atrium:make-module Article
php artisan migrate
```

Каманда стварае мадэль, міграцыю і радок у `config/atrium.php`, і ў меню з'яўляецца «Articles». Мадэль апісвае сваю форму і спіс:

```php
class Article extends Model implements Administrable
{
    use InteractsWithAdmin, Translatable;

    protected array $translatable = ['name', 'code', 'preview_text', 'detail_text'];

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label(__('atrium::fields.standard.name'))->required(),
            SlugField::make('code')->label(__('atrium::fields.standard.code'))->from('name')->unique('articles', 'code'),
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            ContentField::make('detail_text')->label(__('atrium::fields.standard.detail_text')),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('name')->label(__('atrium::fields.standard.name'))->sortable()->searchable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }
}
```

Каб у модуля з'явіліся старонкі на сайце, дадайце яго ў `public_urls` у `config/atrium.php`:

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## Пераезд з 1С-Бітрыкс

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

База Бітрыкса толькі чытаецца. Укладкі, парадак палёў і слупкі спісаў атрымліваюцца такімі, якімі іх наладзілі рэдактары ў Бітрыксе. `atrium:bitrix:sync` даганяе праўкі, якія ўносяць на старым сайце, пакуль ён яшчэ працуе. Па кроках: [packages/bitrix/README.md](../../packages/bitrix/README.md) (на англійскай).

## Дакументацыя

- [Усталёўка: свой сервер, хостынг з ISPmanager або cPanel, Docker](../installation.md)
- [Публічнае дэма: Docker, скід кожную гадзіну, што можа наведвальнік, дзе размясціць](../demo.md)
- [Налады: пошта і код уваходу, файлы, API-токены, ШІ-асістэнты праз MCP](../configuration.md)
- [ШІ-памочнік для тэкстаў: бясплатныя і платныя правайдары, апісанні, SEO, alt, пераклады](../ai.md)
- [SEO: загалоўкі, hreflang, карты сайта, schema.org, рэдырэкты, SEO-праверка](../seo.md)
- [Меню: шапка і падвал, дрэва спасылак, пункты, якія разгортваюць модуль](../menus.md)
- [Формы: канструктар у калонках, адпраўка без перазагрузкі, у акне, свая разметка і fetch](../forms.md)
- [Канструктар сайта: старонкі з блокаў, фармат даных і свой тып блока](../site-builder.md)
- [Устаўкі: форма або блок унутры тэксту і як модуль рэгіструе свой тып](../embeds.md)
- [Блокі: тэлефон ці плашка, напісаныя адзін раз і паказаныя ў шаблонах і тэкстах](../blocks.md)
- [Пошук: старонка вынікаў, радок у адмінцы, што і як шукаецца](../search.md)
- [Свой модуль: палі, калонкі, укладкі, правы, мовы, тэсты, пакет](../modules.md)
- [Пашырэнні: праект платформы — канструктар без кода, формулы, плагіны, знешнія праграмы, план](../extensions.md)
- [Формулы: пішуцца як у табліцы, назвы на англійскай і рускай, ліміты, памылкі з радком і пазіцыяй](../language.md)
- [Свае палі: дадаць поле ў любы модуль з адмінкі, фільтры і фасеты на 100 000 запісаў](../custom-fields.md)
- [Імпарт і выгрузка: CSV і Excel для любога модуля, слупкі, праверка перад запісам, кансоль і расклад](../import-export.md)
- [Гісторыя запісаў: журнал змен усіх модуляў, хто, што і калі змяніў, без пароляў](../history.md)
- [Кэш: хуткія старонкі з дзясяткамі ўласцівасцей, Redis, кэш старонак, загалоўкі для CDN](../cache.md)
- [Пераезд з 1С-Бітрыкс крок за крокам](../bitrix.md)
- [Пераезд з WordPress: падключыць базу, перанесці, праверыць, мапа 301](../wordpress.md)
- [Рэзервовыя копіі: базы і файлы ў некалькі месцаў, расклад, праверкі, аднаўленне](../backup.md)
- [Памылкі: памылкі PHP, чэргаў і JavaScript у базе сайта, абвесткі, Sentry і свае драйверы](../errors.md)
- [Хуткасць і даступнасць: таймінгі сервера, Web Vitals наведвальнікаў, праверкі даступнасці з абвесткамі](../monitoring.md)
- [Інтэграцыі: аснова канектараў 1C і Bitrix24, чарга з паўторамі, журнал абмену, абвесткі адміністратарам](../integrations.md)
- [Bitrix24: заяўкі з форм становяцца лідамі або здзелкамі, віджэт чата на сайце, адзін устаўлены вэбхук](../bitrix24.md)
- [1С: каталог з 1С:Прадпрыемства па стандартным абмене CommerceML, экран абмену](../1c.md)
- [CDN: выявы, паменшаныя копіі і WebP/AVIF, ачыстка кэша, загрузкі ў S3](../cdn.md)
- [Двухфактарны ўваход: праграмы-аўтэнтыфікатары, ключы доступу, коды, журнал уваходаў](../two-factor.md)
- [Уваход праз сацсеткі: правайдары, рэгістрацыя праграмы, адрасы перанакіравання](../social-login.md)

## Пакеты

| Пакет | Што гэта |
|---|---|
| [`alveby/atrium`](../../packages/admin) | Адмінка |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | Тэма для сайта |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | Мігратар з 1С-Бітрыкс |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | ШІ-памочнік для тэкстаў у адмінцы |
| [`alveby/atrium-custom-fields`](../../packages/custom-fields/README.md) | Палі, якія дадаюць у любы модуль з адмінкі, з фільтрамі і фасетамі |
| [`alveby/atrium-language`](../../packages/language/README.md) | Формулы ў кодзе: разбор, праверка і выкананне з лімітамі, без ператварэння ў PHP |
| [`alveby/atrium-seo`](../../packages/seo/README.md) | Шаблоны мета-тэгаў, hreflang, карты сайта, schema.org, рэдырэкты і SEO-праверка |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Дакументацыя з Markdown: версіі і пошук |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | Рэзервовыя копіі баз і файлаў, аднаўленне з адмінкі |
| [`alveby/atrium-errors`](../../packages/errors/README.md) | Улік памылак у базе сайта, абвесткі, Sentry |
| [`alveby/atrium-monitoring`](../../packages/monitoring/README.md) | Хуткасць сервера і наведвальнікаў, даступнасць і абвесткі, без вонкавых сэрвісаў |
| [`alveby/atrium-integrations`](../../packages/integrations/README.md) | Аснова канектараў: чарга з паўторамі, журнал абмену, абвестка пра збой |
| [`alveby/atrium-bitrix24`](../../packages/bitrix24/README.md) | Заяўкі з форм у Bitrix24 лідамі або здзелкамі, віджэт чата |
| [`alveby/atrium-1c`](../../packages/onec/README.md) | Тавары, цэны і астаткі з 1С па CommerceML |
| [`alveby/atrium-news`](../../packages/news/README.md) | Навіны |
| [`alveby/atrium-pages`](../../packages/pages/README.md) | Статычныя старонкі з імпартам старонак 1С-Бітрыкс |
| [`alveby/atrium-catalog`](../../packages/catalog/README.md) | Каталог тавараў з укладзенымі раздзеламі |
| [`alveby/atrium-brands`](../../packages/brands/README.md) | Брэнды з лагатыпамі |
| [`alveby/atrium-stores`](../../packages/stores/README.md) | Крамы і офісы на карце |
| [`alveby/atrium-team`](../../packages/team/README.md) | Людзі, аддзелы і кантакты |
| [`alveby/atrium-faq`](../../packages/faq/README.md) | Пытанні і адказы |
| [`alveby/atrium-tracker`](../../packages/tracker/README.md) | Задачы і праекты для тых, хто працуе ў адмінцы |
| [`alveby/atrium-forms`](../../packages/forms/README.md) | Канструктар формаў і заяўкі |
| [`alveby/atrium-blocks`](../../packages/blocks/README.md) | Блокі: тэлефон ці плашка, напісаныя адзін раз і паказаныя дзе заўгодна |
| [`alveby/atrium-reviews`](../../packages/reviews/README.md) | Водгукі і ацэнкі з мадэрацыяй |
| [`alveby/atrium-2fa`](../../packages/two-factor/README.md) | Двухфактарны і беспарольны ўваход |
| [`alveby/atrium-social-login`](../../packages/social-login/README.md) | Уваход праз Google, Microsoft, Apple, GitHub, Telegram і іншых |
| [`alveby/atrium-solutions`](../../packages/solutions/README.md) | Гатовыя сайты, якія ўсталёўваюцца адной камандай |
| [`alveby/atrium-menus`](../../packages/menus/README.md) | Меню, сабраныя ў адмінцы |
| [`alveby/atrium-search`](../../packages/search/README.md) | Пошук па сайце ва ўсіх модулях |
| [`alveby/atrium-wordpress`](../../packages/wordpress/README.md) | Імпартар WordPress: запісы, старонкі, уласныя тыпы запісаў, SEO і пераклады, з праверкай і мапай 301 |

Пакеты ўжо на Packagist, але пакуль 0.1 не пазначана тэгам — толькі як версія для распрацоўкі, таму ўкажыце яе:

```bash
composer require alveby/atrium:^0.2
php artisan atrium:install
```

## Патрабаванні

- PHP 8.3 або навейшы
- Laravel 13
- SQLite, MySQL 8 або PostgreSQL

## Мовы

Адмінка размаўляе на англійскай, рускай, украінскай, беларускай, казахскай, узбекскай, нямецкай, французскай, іспанскай, партугальскай (Бразілія), італьянскай, польскай, турэцкай, кітайскай (спрошчанай) і японскай. Англійская і руская напісаны ўручную, астатнія пачыналіся з машыннага перакладу, як і гэты README на іншых мовах. Выпраўленні ад носьбітаў мовы — адзін з найлепшых першых унёскаў у праект, падрабязней у [CONTRIBUTING.md](../../CONTRIBUTING.md) (на англійскай).

## Як усё пачыналася

Atrium з’явіўся ў снежні 2025 года ў [AlveSoft](https://alve.by) як наша ўласная адмінка замест 1С-Бітрыкс для сайта заказчыка [arkaim.by](https://arkaim.by). Хацелася адмінку, у якой рэдактару гэтак жа звыкла, як у Бітрыксе, але на звычайным Laravel, і мігратар, які перанясе змест без страт. З лета 2026 года панэль працуе на гэтым сайце: рэдактары штодня вядуць у ёй змест, а мігратар перанёс туды ўвесь каталог, старонкі і формы.

У лістападзе 2026 года мы вырашылі аддаць яе ўсім. Прыбралі ўсё, што адносілася да таго аднаго сайта, а астатняе выклалі пад ліцэнзіяй MIT, каб любы мог бясплатна карыстацца ёю, змяняць яе і будаваць на ёй свае сайты.

## Што далей

[План развіцця](../../ROADMAP.md) (на англійскай): модулі, якія ўсталёўваюцца адной камандай, гатовыя сайты, пераезд з WordPress і Drupal, інтэрнэт-крама, 1С і Бітрыкс24.

## Удзел

У [CONTRIBUTING.md](../../CONTRIBUTING.md) апісана, як разгарнуць праект, запусціць тэсты і перакладаць. Пра ўразлівасці паведамляйце так, як апісана ў [SECURITY.md](../../SECURITY.md).

## Ліцэнзія

MIT, гл. [LICENSE](../../LICENSE).

Калі Atrium вам дапамог, калі ласка, пакіньце спасылку «Зроблена на Atrium» у падвале сайта. Гэта просьба, а не ўмова ліцэнзіі: MIT патрабуе толькі захоўваць паведамленне пра аўтарскае права ў копіях кода.
