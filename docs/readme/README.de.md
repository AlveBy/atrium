# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · **Deutsch** · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · [Italiano](README.it.md) · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

_Diese Seite wurde maschinell aus dem Englischen übersetzt. Korrekturen sind willkommen, siehe [CONTRIBUTING.md](../../CONTRIBUTING.md)._

Ein Admin-Bereich für beliebig viele mehrsprachige Websites auf Basis von Laravel 13, dazu ein Migrator, der eine bestehende 1C-Bitrix-Website übernimmt.

Ein Modul beschreiben Sie nur einmal: als Modell mit seinen Feldern und Listenspalten. Atrium macht daraus eine Liste mit Suche und Sortierung, ein Formular mit Tabs, einen Menüeintrag, eine API und, zusammen mit dem Theme, öffentliche Seiten.

![Die Produktliste des Demo-Shops](../images/admin-list.png)

> **Vor dem ersten Release.** Atrium funktioniert und ist durch Tests abgedeckt, aber Version 0.1 ist noch nicht erschienen, und die Pakete sind noch nicht auf Packagist. Ausprobieren können Sie es direkt aus diesem Repository.

## Was es kann

- **Mehrere Websites.** Ein Admin-Bereich und eine Datenbank für viele Websites; jede wird an ihrer Domain oder an einem Ordner darauf (`example.com/site2/`) erkannt und hat ihre eigenen Sprachen.
- **Mehrsprachig.** Der Admin-Bereich spricht 15 Sprachen, und jeder Administrator wählt seine eigene. Inhaltssprachen werden im Admin-Bereich hinzugefügt, jede Spalte eines Moduls kann übersetzbar sein, und jede Sprache bekommt eigene URLs (`/de/…`) und `hreflang`.
- **20 Feldtypen.** Text, Zahlen, Datumsangaben, Listen, Verknüpfungen mit anderen Modulen, Bilder, Galerien, Dateien, ein visueller Editor, Karten, wiederholbare Werte. Formulare haben Tabs wie in 1C-Bitrix.
- **Rollen und Rechte.** Wer welches Modul sieht und was er darin darf: ansehen, anlegen, bearbeiten, löschen.
- **SEO ohne Code.** Titel und Beschreibungen für jeden Eintrag, sprechende URLs mit Weiterleitungen von den alten, `sitemap.xml` und `robots.txt` für jede Website. Google Tag, Yandex Metrica und Verifizierungscodes werden pro Website im Admin-Bereich eingetragen.
- **API.** Tokens, die auf ausgewählte Module und Aktionen beschränkt sind; `/api/v1` beschreibt sich selbst. KI-Assistenten (Claude, ChatGPT, Cursor) verbinden sich mit `/mcp` über denselben Token und genau mit dessen Rechten.
- **Anmeldecode per E-Mail.** Die Zwei-Faktor-Anmeldung ist standardmäßig aktiv.
- **1C-Bitrix-Migrator.** Infoblöcke, Eigenschaften, Bereiche, Formulare, Listenlayouts, SEO und die Medienbibliothek werden zu Laravel-Modellen, Migrationen und Daten, und ein Bericht vergleicht die alte Website mit der neuen.
- **Öffentliches Theme.** Eine neutrale Website, die jedes Modul anhand seines Formulars im Admin-Bereich darstellt, hell und dunkel, lesbar bei jeder Form des Farbsehens.
- **Getestet.** PHPUnit auf SQLite, MySQL und PostgreSQL, Browsertests mit Laravel Dusk.

| Das Formular eines Eintrags | Die Website, hell und dunkel |
|---|---|
| ![Ein Produktformular mit Tabs](../images/admin-form.png) | ![Eine Produktseite](../images/site-light.png) ![Eine Neuigkeit auf Deutsch, dunkles Theme](../images/site-dark.png) |

## Ausprobieren

Mit Docker brauchen Sie nichts weiter:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

Der erste Start dauert ein paar Minuten. Öffnen Sie dann http://localhost:8000/admin und melden Sie sich als `admin@example.com` mit dem Passwort `password` an (nur für lokale Tests). Die Website läuft unter http://localhost:8000.

Ohne Docker brauchen Sie PHP 8.3 oder neuer und Composer. Als Datenbank dient standardmäßig SQLite, und der Admin-Bereich ist bereits gebaut, sodass weder MySQL noch Node nötig sind:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

Das Installationsprogramm fragt nach Name, E-Mail-Adresse und Passwort des ersten Administrators. Der Anmeldecode kommt per E-Mail; auf einem lokalen Rechner ohne Mailversand steht er am Ende von `storage/logs/laravel.log`, oder Sie schalten ihn mit `ATRIUM_2FA_ENABLED=false` in der `.env` ab.

`--demo` legt einen kleinen Shop an: Produkte mit allen Feldtypen, Marken, Filialen, Neuigkeiten in drei Sprachen und eine zweite Website. Um neu anzufangen, löschen Sie `database/database.sqlite` und installieren erneut.

## Ihr erstes Modul

```bash
php artisan atrium:make-module Article
php artisan migrate
```

Das erzeugt ein Modell, eine Migration und eine Zeile in `config/atrium.php`, und im Menü erscheint „Articles“. Das Modell beschreibt sein Formular und seine Liste:

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

Damit das Modul Seiten auf der Website bekommt, tragen Sie es unter `public_urls` in `config/atrium.php` ein:

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## Umzug von 1C-Bitrix

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

Die Bitrix-Datenbank wird nur gelesen. Tabs, Feldreihenfolge und Listenspalten kommen so heraus, wie die Redakteure sie in Bitrix eingerichtet hatten. `atrium:bitrix:sync` holt Änderungen nach, die auf der alten Website noch gemacht werden, solange sie läuft. Schritt für Schritt: [packages/bitrix/README.md](../../packages/bitrix/README.md) (auf Englisch).

## Dokumentation

- [Installation: eigener Server, Hosting mit ISPmanager oder cPanel, Docker](../installation.md)
- [Konfiguration: E-Mail und Anmeldecode, Dateien, API-Tokens, KI-Assistenten über MCP](../configuration.md)
- [KI-Schreibhilfe: kostenlose und bezahlte Anbieter, Beschreibungen, SEO, Alt-Texte, Übersetzungen](../ai.md)
- [Eigenes Modul: Felder, Spalten, Tabs, Rechte, Sprachen, Tests, als Paket](../modules.md)
- [Eigene Felder: Felder für jedes Modul im Admin-Bereich anlegen, Filter und Facetten bei 100 000 Einträgen](../custom-fields.md)
- [Cache: schnelle Seiten mit Dutzenden Eigenschaften, Redis, Seiten-Cache, CDN-Header](../cache.md)
- [Umzug von 1C-Bitrix, Schritt für Schritt](../bitrix.md)
- [Backups: Datenbanken und Dateien an mehrere Orte, Zeitpläne, Prüfungen, Wiederherstellung](../backup.md)
- [Fehler: PHP-, Warteschlangen- und JavaScript-Fehler in der Datenbank der Website, Benachrichtigungen, Sentry und eigene Treiber](../errors.md)
- [Geschwindigkeit und Erreichbarkeit: Server-Timings, Web Vitals der Besucher, Erreichbarkeitsprüfungen mit Benachrichtigungen](../monitoring.md)
- [CDN: Bilder, verkleinerte Kopien und WebP/AVIF, Cache leeren, Uploads zu S3](../cdn.md)

## Pakete

| Paket | Was es ist |
|---|---|
| [`alveby/atrium`](../../packages/admin) | Der Admin-Bereich |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | Das öffentliche Theme |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | Der 1C-Bitrix-Migrator |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | KI-Schreibhilfe im Admin-Bereich |
| [`alveby/atrium-custom-fields`](../../packages/custom-fields/README.md) | Felder für jedes Modul, im Admin-Bereich angelegt, mit Filtern und Facetten |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Dokumentation aus Markdown, mit Versionen und Suche |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | Backups der Datenbanken und Dateien, Wiederherstellung im Admin-Bereich |
| [`alveby/atrium-errors`](../../packages/errors/README.md) | Fehlererfassung in der Datenbank der Website, Benachrichtigungen, Sentry |
| [`alveby/atrium-monitoring`](../../packages/monitoring/README.md) | Geschwindigkeit von Server und Besuchern, Erreichbarkeit und Benachrichtigungen, ohne externe Dienste |

Sobald 0.1 auf Packagist ist, bekommt eine bestehende Laravel-13-Anwendung Atrium so:

```bash
composer require alveby/atrium
php artisan atrium:install
```

## Voraussetzungen

- PHP 8.3 oder neuer
- Laravel 13
- SQLite, MySQL 8 oder PostgreSQL

## Sprachen

Der Admin-Bereich spricht Englisch, Russisch, Ukrainisch, Belarussisch, Kasachisch, Usbekisch, Deutsch, Französisch, Spanisch, Portugiesisch (Brasilien), Italienisch, Polnisch, Türkisch, Chinesisch (vereinfacht) und Japanisch. Englisch und Russisch wurden von Hand geschrieben; die übrigen Sprachen begannen als maschinelle Übersetzung, und diese README ebenso. Korrekturen von Muttersprachlern gehören zu den besten ersten Beiträgen: siehe [CONTRIBUTING.md](../../CONTRIBUTING.md) (auf Englisch).

## Wie alles begann

Atrium entstand im Dezember 2025 bei [AlveSoft](https://alve.by) als unser eigener Admin-Bereich, um auf der Website eines Kunden, [arkaim.by](https://arkaim.by), 1C-Bitrix abzulösen. Wir wollten einen Admin-Bereich, der Redakteuren so vertraut ist wie Bitrix, aber auf reinem Laravel läuft, und einen Migrator, der die Inhalte ohne Verluste hinüberbringt. Seit dem Sommer 2026 läuft er auf dieser Website im Produktivbetrieb: Die Redakteure pflegen darin täglich die Inhalte, und der Migrator hat den gesamten Katalog, die Seiten und die Formulare übertragen.

Im November 2026 haben wir beschlossen, Atrium allen zu geben. Wir haben alles entfernt, was zu dieser einen Website gehörte, und den Rest unter der MIT-Lizenz veröffentlicht, damit jeder es kostenlos nutzen, verändern und eigene Websites darauf aufbauen kann.

## Wie es weitergeht

Die [Roadmap](../../ROADMAP.md) (auf Englisch): Module, die sich mit einem Befehl installieren lassen, fertige Websites, Importer für WordPress und Drupal, ein Onlineshop, 1C und Bitrix24.

## Mitwirken

[CONTRIBUTING.md](../../CONTRIBUTING.md) erklärt, wie Sie das Projekt einrichten, die Tests ausführen und übersetzen. Sicherheitslücken melden Sie wie in [SECURITY.md](../../SECURITY.md) beschrieben.

## Lizenz

MIT, siehe [LICENSE](../../LICENSE).

Wenn Atrium Ihnen geholfen hat, lassen Sie bitte den Link „Erstellt mit Atrium“ in der Fußzeile Ihrer Website stehen. Das ist eine Bitte, keine Lizenzbedingung: MIT verlangt nur, dass der Urheberrechtsvermerk in Kopien des Codes erhalten bleibt.
