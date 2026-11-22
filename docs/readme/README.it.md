# Atrium

[English](../../README.md) · [Русский](README.ru.md) · [Українська](README.uk.md) · [Беларуская](README.be.md) · [Қазақша](README.kk.md) · [Oʻzbekcha](README.uz.md) · [Deutsch](README.de.md) · [Français](README.fr.md) · [Español](README.es.md) · [Português (Brasil)](README.pt-BR.md) · **Italiano** · [Polski](README.pl.md) · [Türkçe](README.tr.md) · [简体中文](README.zh-CN.md) · [日本語](README.ja.md)

[![tests](https://github.com/AlveBy/atrium/actions/workflows/tests.yml/badge.svg)](https://github.com/AlveBy/atrium/actions/workflows/tests.yml) [![coverage 100%](https://img.shields.io/badge/coverage-100%25-brightgreen)](../testing.md)

_Questa pagina è stata tradotta automaticamente dall'inglese. Le correzioni sono benvenute: vedi [CONTRIBUTING.md](../../CONTRIBUTING.md) (in inglese)._

Un unico pannello di amministrazione per tutti i siti multilingue che vuoi, basato su Laravel 13, con un migratore che trasferisce un sito 1C-Bitrix esistente.

Descrivi un modulo una sola volta, come un modello con i suoi campi e le colonne dell'elenco. Atrium gli fornisce un elenco con ricerca e ordinamento, un form con schede, una voce di menu, un'API e, insieme al tema, pagine pubbliche.

![L'elenco dei prodotti del negozio demo](../images/admin-list.webp)

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

| Il modulo di un record | Ricerca nel pannello |
|---|---|
| ![Il modulo di un record](../images/admin-form.webp) | ![Ricerca nel pannello](../images/admin-search.webp) |

| Menu | Cruscotto |
|---|---|
| ![Menu](../images/admin-menus.webp) | ![Cruscotto](../images/admin-dashboard.webp) |

| Il sito, tema chiaro | Il sito, tema scuro |
|---|---|
| ![Il sito, tema chiaro](../images/site-light.webp) | ![Il sito, tema scuro](../images/site-dark.webp) |

## Qualità

- Più di 6.500 test PHPUnit e più di 80 test nel browser con Laravel Dusk girano a ogni pull request.
- Su SQLite, MySQL e PostgreSQL, con PHP 8.3 e 8.4.
- Ogni pacchetto ha il 100% delle righe coperte, e la CI rifiuta una modifica che lascia una riga senza test.
- Il codice modificato deve avere un mutation score del 100%: Infection altera ogni riga cambiata e per ogni alterazione deve fallire un test.
- Niente è escluso da questi controlli: nessuna annotazione per ignorare, nessun mutatore disattivato.
- Ogni push viene controllato per dipendenze con vulnerabilità note e per segreti nella cronologia git.

[Come funzionano i test e i controlli](../testing.md).

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
- [Demo pubblica: Docker, un ripristino ogni ora, cosa può fare un visitatore, dove ospitarla](../demo.md)
- [Configurazione: e-mail e codice di accesso, file, token API, assistenti IA via MCP](../configuration.md)
- [Aiuto alla scrittura con IA: fornitori gratuiti e a pagamento, descrizioni, SEO, testi alt, traduzioni](../ai.md)
- [SEO: titoli, hreflang, sitemap, schema.org, reindirizzamenti, il controllo SEO](../seo.md)
- [Menu: intestazione e piè di pagina, un albero di link, voci che espandono un modulo](../menus.md)
- [Ricerca: la pagina dei risultati, il campo nel pannello, cosa si cerca e come](../search.md)
- [Il tuo modulo: campi, colonne, schede, permessi, lingue, test, pacchetto](../modules.md)
- [Campi personalizzati: aggiungere campi a qualsiasi modulo dal pannello, filtri e faccette su 100 000 record](../custom-fields.md)
- [Importazione ed esportazione: CSV ed Excel per ogni modulo, colonne, un controllo prima di scrivere, la console e una pianificazione](../import-export.md)
- [Cache: pagine veloci con decine di proprietà, Redis, cache delle pagine, intestazioni per CDN](../cache.md)
- [Migrare da 1C-Bitrix, passo per passo](../bitrix.md)
- [Migrare da WordPress: collegare il database, migrare, verificare, la mappa dei 301](../wordpress.md)
- [Backup: database e file in più destinazioni, pianificazione, controlli, ripristino](../backup.md)
- [Errori: errori PHP, delle code e JavaScript nel database del sito, avvisi, Sentry e driver propri](../errors.md)
- [Velocità e disponibilità: tempi del server, Web Vitals dei visitatori, controlli di disponibilità con avvisi](../monitoring.md)
- [CDN: immagini, copie ridimensionate e WebP/AVIF, svuotamento della cache, caricamenti su S3](../cdn.md)
- [Accesso a due fattori: app di autenticazione, passkey, codici, il registro degli accessi](../two-factor.md)
- [Accesso social: provider, registrazione dell'app, URL di reindirizzamento](../social-login.md)

## Pacchetti

| Pacchetto | Cos'è |
|---|---|
| [`alveby/atrium`](../../packages/admin) | Il pannello di amministrazione |
| [`alveby/atrium-theme`](../../packages/theme/README.md) | Il tema pubblico |
| [`alveby/atrium-bitrix`](../../packages/bitrix/README.md) | Il migratore da 1C-Bitrix |
| [`alveby/atrium-ai`](../../packages/ai/README.md) | Aiuto alla scrittura con IA nel pannello |
| [`alveby/atrium-custom-fields`](../../packages/custom-fields/README.md) | Campi aggiunti a qualsiasi modulo dal pannello, con filtri e faccette |
| [`alveby/atrium-seo`](../../packages/seo/README.md) | Modelli meta, hreflang, sitemap, schema.org, reindirizzamenti e un controllo SEO |
| [`alveby/atrium-docs`](../../packages/docs/README.md) | Documentazione da Markdown, con versioni e ricerca |
| [`alveby/atrium-backup`](../../packages/backup/README.md) | Backup di database e file, ripristino dal pannello |
| [`alveby/atrium-errors`](../../packages/errors/README.md) | Tracciamento degli errori nel database del sito, avvisi, Sentry |
| [`alveby/atrium-monitoring`](../../packages/monitoring/README.md) | Velocità del server e dei visitatori, disponibilità e avvisi, senza servizi esterni |
| [`alveby/atrium-news`](../../packages/news/README.md) | Notizie |
| [`alveby/atrium-pages`](../../packages/pages/README.md) | Pagine statiche, con importazione delle pagine di 1C-Bitrix |
| [`alveby/atrium-catalog`](../../packages/catalog/README.md) | Catalogo prodotti con sezioni annidate |
| [`alveby/atrium-brands`](../../packages/brands/README.md) | Marchi con logo |
| [`alveby/atrium-stores`](../../packages/stores/README.md) | Negozi e uffici su una mappa |
| [`alveby/atrium-team`](../../packages/team/README.md) | Persone, reparti e contatti |
| [`alveby/atrium-faq`](../../packages/faq/README.md) | Domande e risposte |
| [`alveby/atrium-forms`](../../packages/forms/README.md) | Generatore di moduli e invii |
| [`alveby/atrium-reviews`](../../packages/reviews/README.md) | Recensioni e valutazioni con moderazione |
| [`alveby/atrium-2fa`](../../packages/two-factor/README.md) | Accesso a due fattori e senza password |
| [`alveby/atrium-social-login`](../../packages/social-login/README.md) | Accesso con Google, Microsoft, Apple, GitHub, Telegram e altri |
| [`alveby/atrium-solutions`](../../packages/solutions/README.md) | Siti pronti installati con un solo comando |
| [`alveby/atrium-menus`](../../packages/menus/README.md) | Menu creati nel pannello |
| [`alveby/atrium-search`](../../packages/search/README.md) | Ricerca in tutto il sito, in ogni modulo |
| [`alveby/atrium-wordpress`](../../packages/wordpress/README.md) | L'importatore di WordPress: articoli, pagine, tipi di contenuto personalizzati, SEO e traduzioni, con verifica e mappa dei 301 |

I pacchetti sono su Packagist, ma finché la 0.1 non ha un tag solo come versione di sviluppo, quindi va indicata:

```bash
composer require alveby/atrium:^0.1
php artisan atrium:install
```

## Requisiti

- PHP 8.3 o superiore
- Laravel 13
- SQLite, MySQL 8 o PostgreSQL

## Lingue

Il pannello di amministrazione parla inglese, russo, ucraino, bielorusso, kazako, uzbeko, tedesco, francese, spagnolo, portoghese (Brasile), italiano, polacco, turco, cinese (semplificato) e giapponese. L'inglese e il russo sono stati scritti a mano; le altre lingue sono nate come traduzione automatica, e così anche questo README. Le correzioni dei madrelingua sono uno dei migliori primi contributi: vedi [CONTRIBUTING.md](../../CONTRIBUTING.md) (in inglese).

## Com'è nato

Atrium è nato a dicembre 2025 presso [AlveSoft](https://alve.by) come il nostro pannello di amministrazione, per sostituire 1C-Bitrix sul sito di un cliente, [arkaim.by](https://arkaim.by). Volevamo un pannello familiare per i redattori quanto Bitrix, ma basato su Laravel puro, e un migratore che trasferisse i contenuti senza perdere nulla. Dall'estate 2026 il pannello gestisce quel sito in produzione: i redattori ci lavorano ogni giorno, e il migratore ha spostato l'intero catalogo, le pagine e i form.

A novembre 2026 abbiamo deciso di darlo a tutti. Abbiamo tolto tutto ciò che apparteneva a quell'unico sito e pubblicato il resto con licenza MIT, così che chiunque possa usarlo, modificarlo e costruirci sopra i propri siti gratuitamente.

## Prossimi passi

La [roadmap](../../ROADMAP.md) (in inglese): moduli che si installano con un solo comando, siti pronti all'uso, importatori da WordPress e Drupal, un negozio online, 1C e Bitrix24.

## Contribuire

[CONTRIBUTING.md](../../CONTRIBUTING.md) spiega come preparare il progetto, eseguire i test e tradurre. Segnala i problemi di sicurezza come descritto in [SECURITY.md](../../SECURITY.md).

## Licenza

MIT, vedi [LICENSE](../../LICENSE).

Se Atrium ti è stato utile, lascia per favore il link «Realizzato con Atrium» nel piè di pagina del tuo sito. È una richiesta, non una condizione della licenza: MIT chiede solo di conservare l’avviso di copyright nelle copie del codice.
