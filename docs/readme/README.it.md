# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · **Italiano** · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml)

_Questa pagina è stata tradotta automaticamente dall'inglese. Le correzioni sono benvenute: vedi [CONTRIBUTING.md](../../CONTRIBUTING.md) (in inglese)._

Un unico pannello di amministrazione per tutti i siti multilingue che vuoi, basato su Laravel 13, con un migratore che trasferisce un sito 1C-Bitrix esistente.

Descrivi un modulo una sola volta, come un modello con i suoi campi e le colonne dell'elenco. Atrium gli fornisce un elenco con ricerca e ordinamento, un form con schede, una voce di menu, un'API e, insieme al tema, pagine pubbliche.

![L'elenco dei prodotti del negozio demo](../images/admin-list.png)

> **Prima del primo rilascio.** Atrium funziona ed è coperto da test, ma la versione 0.1 non è ancora uscita e i pacchetti non sono su Packagist. Provalo da questo repository.

## Cosa fa

- **Multisito.** Un solo pannello e un solo database per molti siti, ognuno riconosciuto dal proprio dominio o da una cartella su di esso (`example.com/site2/`) e con le proprie lingue.
- **Multilingue.** Il pannello parla 15 lingue e ogni amministratore sceglie la sua. Le lingue dei contenuti si aggiungono dal pannello, qualsiasi colonna di un modulo può essere traducibile e ogni lingua ha i propri indirizzi (`/de/…`) e `hreflang`.
- **20 tipi di campo.** Testo, numeri, date, elenchi, collegamenti ad altri moduli, immagini, gallerie, file, un editor visuale, mappe, valori ripetibili. I form sono divisi in schede, come in 1C-Bitrix.
- **Ruoli e permessi.** Chi vede quale modulo e cosa può farci: visualizzare, creare, modificare, eliminare.
- **SEO senza codice.** Titoli e descrizioni per ogni record, indirizzi leggibili con reindirizzamenti da quelli vecchi, `sitemap.xml` e `robots.txt` per ogni sito. Google Tag, Yandex Metrica e i codici di verifica si impostano per sito nel pannello.
- **API.** Token limitati ai moduli e alle azioni scelti; `/api/v1` descrive sé stessa. Gli assistenti IA (Claude, ChatGPT, Cursor) si collegano a `/mcp` con lo stesso token ed esattamente i suoi permessi.
- **Codice di accesso via email.** L'accesso in due passaggi è attivo per impostazione predefinita.
- **Migratore da 1C-Bitrix.** Infoblock, proprietà, sezioni, form, layout degli elenchi, SEO e libreria multimediale diventano modelli, migrazioni e dati Laravel, e un report confronta il vecchio sito con quello nuovo.
- **Tema pubblico.** Un sito neutro che disegna ogni modulo a partire dal suo form nel pannello, chiaro e scuro, leggibile con qualsiasi tipo di visione dei colori.
- **Testato.** PHPUnit su SQLite, MySQL e PostgreSQL, e test nel browser con Laravel Dusk.

| Il form di un record | Il sito, chiaro e scuro |
|---|---|
| ![Il form di un prodotto con le schede](../images/admin-form.png) | ![La pagina di un prodotto](../images/site-light.png) ![Una notizia in tedesco, tema scuro](../images/site-dark.png) |

## Provalo

Con Docker non serve nient'altro:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
docker compose up
```

Il primo avvio richiede qualche minuto. Poi apri http://localhost:8000/admin e accedi come `admin@example.com` con la password `password` (solo per prove in locale). Il sito è su http://localhost:8000.

Senza Docker servono PHP 8.3 o superiore e Composer. Il database predefinito è SQLite e il pannello è già compilato, quindi non servono né MySQL né Node:

```bash
git clone https://github.com/AlveBy/atrium.git
cd atrium
composer install
cp .env.example .env
php artisan key:generate
php artisan atrium:install --demo
php artisan serve
```

L'installer chiede nome, email e password del primo amministratore. Il codice di accesso arriva via email; su una macchina locale senza posta si trova in fondo a `storage/logs/laravel.log`, oppure puoi disattivarlo con `ATRIUM_2FA_ENABLED=false` in `.env`.

`--demo` aggiunge un piccolo negozio: prodotti con tutti i tipi di campo, marchi, punti vendita, notizie in tre lingue e un secondo sito. Per ricominciare da capo, elimina `database/database.sqlite` e reinstalla.

## Il tuo primo modulo

```bash
php artisan atrium:make-module Article
php artisan migrate
```

Il comando crea un modello, una migrazione e una riga in `config/atrium.php`, e nel menu compare «Articles». Il modello descrive il proprio form e il proprio elenco:

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

Per dare al modulo delle pagine sul sito, aggiungilo a `public_urls` in `config/atrium.php`:

```php
'public_urls' => [
    App\Models\Article::class => ['prefix' => 'articles'],
],
```

## Passare da 1C-Bitrix

```bash
composer require alveby/atrium-bitrix --dev
php artisan atrium:bitrix:migrate
php artisan atrium:bitrix:parity
```

Il database di Bitrix viene solo letto. Le schede, l'ordine dei campi e le colonne degli elenchi restano come li avevano impostati i redattori in Bitrix. `atrium:bitrix:sync` recupera le modifiche fatte sul vecchio sito finché resta online. Passo per passo: [packages/bitrix/README.md](../../packages/bitrix/README.md) (in inglese).

## Documentazione

- [Installazione: server proprio, hosting con ISPmanager o cPanel, Docker](../installation.md)
- [Configurazione: e-mail e codice di accesso, file, token API, assistenti IA via MCP](../configuration.md)
- [Il tuo modulo: campi, colonne, schede, permessi, lingue, test, pacchetto](../modules.md)
- [Migrare da 1C-Bitrix, passo per passo](../bitrix.md)

## Pacchetti

| Pacchetto | Cos'è |
|---|---|
| [`alveby/atrium`](../../packages/admin) | Il pannello di amministrazione |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | Il tema pubblico |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | Il migratore da 1C-Bitrix |

Quando la 0.1 sarà su Packagist, un'app Laravel 13 esistente potrà aggiungere Atrium con:

```bash
composer require alveby/atrium
php artisan atrium:install
```

## Requisiti

- PHP 8.3 o superiore
- Laravel 13
- SQLite, MySQL 8 o PostgreSQL

## Lingue

Il pannello di amministrazione parla inglese, russo, ucraino, bielorusso, kazako, uzbeko, tedesco, francese, spagnolo, portoghese (Brasile), italiano, polacco, turco, cinese (semplificato) e giapponese. L'inglese e il russo sono stati scritti a mano; le altre lingue sono nate come traduzione automatica, e così anche questo README. Le correzioni dei madrelingua sono uno dei migliori primi contributi: vedi [CONTRIBUTING.md](../../CONTRIBUTING.md) (in inglese).

## Prossimi passi

La [roadmap](../../ROADMAP.md) (in inglese): moduli che si installano con un solo comando, siti pronti all'uso, importatori da WordPress e Drupal, un negozio online, 1C e Bitrix24.

## Contribuire

[CONTRIBUTING.md](../../CONTRIBUTING.md) spiega come preparare il progetto, eseguire i test e tradurre. Segnala i problemi di sicurezza come descritto in [SECURITY.md](../../SECURITY.md).

## Licenza

MIT, vedi [LICENSE](../../LICENSE).
