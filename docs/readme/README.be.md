# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · **Беларуская** · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

_Гэтая старонка перакладзена з англійскай машынным спосабам. Выпраўленні вітаюцца, гл. [CONTRIBUTING.md](../../CONTRIBUTING.md)._

Адна адмінка для любой колькасці шматмоўных сайтаў на Laravel 13 і мігратар, які пераносіць гатовы сайт з 1С-Бітрыкс.

Модуль апісваецца адзін раз: мадэль, яе палі і слупкі спіса. Астатняе Atrium робіць сам: спіс з пошукам і сартаваннем, форму з укладкамі, пункт меню, API, а разам з тэмай яшчэ і старонкі на сайце.

![Спіс тавараў дэма-крамы](../images/admin-list.png)

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

| Форма запісу | Сайт, светлы і цёмны |
|---|---|
| ![Форма тавару з укладкамі](../images/admin-form.png) | ![Старонка тавару](../images/site-light.png) ![Навіна на нямецкай, цёмная тэма](../images/site-dark.png) |

## Паспрабаваць

З Docker больш нічога не трэба:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

Першы запуск займае некалькі хвілін. Потым адкрыйце http://localhost:8000/admin і ўвайдзіце як `admin@example.com` з паролем `password` (толькі для спроб на сваім камп'ютары). Сайт — на http://localhost:8000.

Без Docker патрэбныя PHP 8.3 або навейшы і Composer. База па змаўчанні — SQLite, а адмінка ўжо сабраная, так што ні MySQL, ні Node не патрэбныя:

```bash
git clone https://github.com/AlveBy/atrium.git
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
- [Налады: пошта і код уваходу, файлы, API-токены, ШІ-асістэнты праз MCP](../configuration.md)
- [Свой модуль: палі, калонкі, укладкі, правы, мовы, тэсты, пакет](../modules.md)
- [Пераезд з 1С-Бітрыкс крок за крокам](../bitrix.md)

## Пакеты

| Пакет | Што гэта |
|---|---|
| [`alveby/atrium`](../../packages/admin) | Адмінка |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | Тэма для сайта |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | Мігратар з 1С-Бітрыкс |

Калі 0.1 з'явіцца на Packagist, у гатовую праграму на Laravel 13 Atrium будзе ўсталёўвацца так:

```bash
composer require alveby/atrium
php artisan atrium:install
```

## Патрабаванні

- PHP 8.3 або навейшы
- Laravel 13
- SQLite, MySQL 8 або PostgreSQL

## Мовы

Адмінка размаўляе на англійскай, рускай, украінскай, беларускай, казахскай, узбекскай, нямецкай, французскай, іспанскай, партугальскай (Бразілія), італьянскай, польскай, турэцкай, кітайскай (спрошчанай) і японскай. Англійская і руская напісаны ўручную, астатнія пачыналіся з машыннага перакладу, як і гэты README на іншых мовах. Выпраўленні ад носьбітаў мовы — адзін з найлепшых першых унёскаў у праект, падрабязней у [CONTRIBUTING.md](../../CONTRIBUTING.md) (на англійскай).

## Што далей

[План развіцця](../../ROADMAP.md) (на англійскай): модулі, якія ўсталёўваюцца адной камандай, гатовыя сайты, пераезд з WordPress і Drupal, інтэрнэт-крама, 1С і Бітрыкс24.

## Удзел

У [CONTRIBUTING.md](../../CONTRIBUTING.md) апісана, як разгарнуць праект, запусціць тэсты і перакладаць. Пра ўразлівасці паведамляйце так, як апісана ў [SECURITY.md](../../SECURITY.md).

## Ліцэнзія

MIT, гл. [LICENSE](../../LICENSE).
