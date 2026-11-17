# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · **Polski** · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

_Ta strona została przetłumaczona maszynowo z angielskiego. Poprawki są mile widziane, zobacz [CONTRIBUTING.md](../../CONTRIBUTING.md)._

Jeden panel administracyjny dla dowolnej liczby wielojęzycznych witryn, zbudowany na Laravel 13, z migratorem, który przenosi istniejącą witrynę z 1C-Bitrix.

Moduł opisujesz raz: jako model z jego polami i kolumnami listy. Atrium daje mu listę z wyszukiwaniem i sortowaniem, formularz z kartami, pozycję w menu, API, a razem z motywem także strony publiczne.

![Lista produktów sklepu demonstracyjnego](../images/admin-list.png)

> **Przed pierwszym wydaniem.** Atrium działa i jest pokryty testami, ale wersja 0.1 jeszcze się nie ukazała, a pakietów nie ma na Packagist. Wypróbuj go z tego repozytorium.

## Co potrafi

- **Wiele witryn.** Jeden panel i jedna baza danych dla wielu witryn; każda jest rozpoznawana po domenie lub folderze w niej (`example.com/site2/`) i ma własne języki.
- **Wielojęzyczność.** Panel mówi w 15 językach, a każdy administrator wybiera swój. Języki treści dodaje się w panelu, każdą kolumnę modułu można oznaczyć jako tłumaczalną, a każdy język dostaje własne adresy (`/de/…`) i `hreflang`.
- **20 typów pól.** Tekst, liczby, daty, listy, powiązania z innymi modułami, obrazy, galerie, pliki, edytor wizualny, mapy, wartości wielokrotne. Formularze mają karty, jak w 1C-Bitrix.
- **Role i uprawnienia.** Kto widzi który moduł i co może w nim robić: podgląd, tworzenie, edycja, usuwanie.
- **SEO bez programisty.** Tytuły i opisy dla każdego rekordu, czytelne adresy z przekierowaniami ze starych, `sitemap.xml` i `robots.txt` dla każdej witryny. Google Tag, Yandex Metrica i kody weryfikacyjne ustawia się w panelu osobno dla każdej witryny.
- **API.** Tokeny ograniczone do wybranych modułów i akcji; `/api/v1` sam opisuje, co zawiera. Asystenci AI (Claude, ChatGPT, Cursor) łączą się z `/mcp` tym samym tokenem i dokładnie z jego uprawnieniami.
- **Kod logowania e-mailem.** Logowanie dwuetapowe jest domyślnie włączone.
- **Migrator z 1C-Bitrix.** Infobloki, właściwości, sekcje, formularze, układy list, SEO i biblioteka mediów stają się modelami Laravel, migracjami i danymi, a raport porównuje starą witrynę z nową.
- **Motyw publiczny.** Neutralna witryna, która rysuje każdy moduł na podstawie jego formularza w panelu, w wersji jasnej i ciemnej, czytelna przy każdym rodzaju widzenia barw.
- **Przetestowany.** PHPUnit na SQLite, MySQL i PostgreSQL, testy w przeglądarce z Laravel Dusk.

| Formularz rekordu | Witryna, jasna i ciemna |
|---|---|
| ![Formularz produktu z kartami](../images/admin-form.png) | ![Strona produktu](../images/site-light.png) ![Aktualność po niemiecku, ciemny motyw](../images/site-dark.png) |

## Wypróbuj

Z Dockerem nic więcej nie jest potrzebne:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

Pierwsze uruchomienie trwa kilka minut. Potem otwórz http://localhost:8000/admin i zaloguj się jako `admin@example.com` hasłem `password` (tylko do prób na własnym komputerze). Witryna jest pod adresem http://localhost:8000.

Bez Dockera potrzebujesz PHP 8.3 lub nowszego oraz Composera. Domyślną bazą danych jest SQLite, a panel jest już zbudowany, więc nie potrzeba ani MySQL, ani Node:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

Instalator zapyta o imię, adres e-mail i hasło pierwszego administratora. Kod logowania przychodzi e-mailem; na lokalnym komputerze bez poczty znajdziesz go na końcu `storage/logs/laravel.log`, możesz też go wyłączyć, ustawiając `ATRIUM_2FA_ENABLED=false` w `.env`.

`--demo` dodaje mały sklep internetowy: produkty ze wszystkimi typami pól, marki, sklepy, aktualności w trzech językach i drugą witrynę. Aby zacząć od nowa, usuń `database/database.sqlite` i zainstaluj ponownie.

## Twój pierwszy moduł

```bash
php artisan atrium:make-module Article
php artisan migrate
```

To polecenie tworzy model, migrację i wiersz w `config/atrium.php`, a w menu pojawia się „Articles”. Model opisuje swój formularz i listę:

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

Aby moduł miał strony w witrynie, dodaj go do `public_urls` w `config/atrium.php`:

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## Przeprowadzka z 1C-Bitrix

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

Baza danych Bitrixa jest tylko odczytywana. Karty, kolejność pól i kolumny list wychodzą takie, jakie ustawili sobie redaktorzy w Bitrixie. `atrium:bitrix:sync` nadrabia zmiany wprowadzane na starej witrynie, dopóki ta jeszcze działa. Krok po kroku: [packages/bitrix/README.md](../../packages/bitrix/README.md) (po angielsku).

## Dokumentacja

- [Instalacja: własny serwer, hosting z ISPmanager lub cPanel, Docker](../installation.md)
- [Konfiguracja: poczta i kod logowania, pliki, tokeny API, asystenci AI przez MCP](../configuration.md)
- [Pomoc AI w pisaniu: darmowi i płatni dostawcy, opisy, SEO, teksty alt, tłumaczenia](../ai.md)
- [Własny moduł: pola, kolumny, zakładki, uprawnienia, języki, testy, pakiet](../modules.md)
- [Przeprowadzka z 1C-Bitrix krok po kroku](../bitrix.md)
- [Oferty pracy i aplikacje: strona kariery, CV, etapy rekrutacji, dane osobowe](../jobs.md)

## Pakiety

| Pakiet | Co to jest |
|---|---|
| [`alveby/atrium`](../../packages/admin) | Panel administracyjny |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | Motyw publiczny |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | Migrator z 1C-Bitrix |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | Pomoc AI w pisaniu tekstów w panelu |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Dokumentacja z Markdown, z wersjami i wyszukiwaniem |
| [`alveby/atrium-jobs`](../../packages/jobs/README.md) | Oferty pracy i aplikacje z etapami rekrutacji |

Gdy 0.1 trafi na Packagist, do istniejącej aplikacji na Laravel 13 dodasz Atrium tak:

```bash
composer require alveby/atrium
php artisan atrium:install
```

## Wymagania

- PHP 8.3 lub nowszy
- Laravel 13
- SQLite, MySQL 8 lub PostgreSQL

## Języki

Panel administracyjny mówi po angielsku, rosyjsku, ukraińsku, białorusku, kazachsku, uzbecku, niemiecku, francusku, hiszpańsku, portugalsku (Brazylia), włosku, polsku, turecku, chińsku (w wersji uproszczonej) i japońsku. Angielski i rosyjski zostały napisane ręcznie; pozostałe zaczynały jako tłumaczenie maszynowe, podobnie jak ten README. Poprawki od rodzimych użytkowników języka to jeden z najlepszych pierwszych wkładów w projekt: zobacz [CONTRIBUTING.md](../../CONTRIBUTING.md) (po angielsku).

## Co dalej

[Plan rozwoju](../../ROADMAP.md) (po angielsku): moduły instalowane jednym poleceniem, gotowe witryny, importery z WordPressa i Drupala, sklep internetowy, 1C i Bitrix24.

## Współpraca

[CONTRIBUTING.md](../../CONTRIBUTING.md) wyjaśnia, jak uruchomić projekt, przeprowadzić testy i tłumaczyć. Problemy z bezpieczeństwem zgłaszaj zgodnie z opisem w [SECURITY.md](../../SECURITY.md).

## Licencja

MIT, zobacz [LICENSE](../../LICENSE).

Jeśli Atrium Ci pomógł, zostaw proszę link „Zbudowane na Atrium” w stopce swojej strony. To prośba, a nie warunek licencji: MIT wymaga jedynie zachowania informacji o prawach autorskich w kopiach kodu.
