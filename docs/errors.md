# Errors

[English](errors.md) · [Русский](errors.ru.md)

`alveby/atrium-errors` catches the site's errors and keeps them in the site's own database: PHP exceptions, failed queue jobs, failed scheduled tasks and JavaScript errors in visitors' browsers. Errors of one kind from one place make one line in the admin, with the number of times it happened, the trace, the requests and a chart. It e-mails or messages Telegram when a new error appears or a fixed one comes back. It can also send everything to Sentry or GlitchTip, or to any service you write a driver for.

It is a small Sentry inside the site: nothing to host, nothing to pay, and the data stays on your server. When you outgrow it, switch Sentry on next to it or instead of it.

- [Install](#install)
- [What is caught](#what-is-caught)
- [How errors are grouped](#how-errors-are-grouped)
- [The admin screens](#the-admin-screens)
- [Statuses and «resolve in next release»](#statuses-and-resolve-in-next-release)
- [Alerts](#alerts)
- [Drivers: database, Sentry, log](#drivers-database-sentry-log)
- [Your own driver: Bugsnag, Rollbar, a webhook](#your-own-driver-bugsnag-rollbar-a-webhook)
- [Using sentry-laravel instead](#using-sentry-laravel-instead)
- [JavaScript errors](#javascript-errors)
- [Secrets and personal data](#secrets-and-personal-data)
- [Load: sampling, the cap, the queue](#load-sampling-the-cap-the-queue)
- [Permissions and sites](#permissions-and-sites)
- [Settings](#settings)
- [Commands](#commands)

## Install

```bash
composer require alveby/atrium-errors
php artisan atrium:module:install alveby/atrium-errors
```

The install creates two tables (`error_issues`, `error_events`), adds **Site health → Errors** to the menu, the **Errors in 24 hours** tile to the dashboard, and gives the right to see errors to every role that may change the module's settings. Old events are deleted at night by Laravel's scheduler, so cron must call `php artisan schedule:run` every minute, as for any Atrium site.

Check that it works:

```bash
php artisan atrium:errors:test
```

A test error appears in **Site health → Errors** and in every other driver that is switched on.

## What is caught

| Source | How |
|---|---|
| PHP exceptions of pages and commands | Laravel's exception handler; what Laravel does not report anyway (validation, 404, sign-in) is skipped |
| Queue jobs that failed for good | the `JobFailed` event, with the job's name, queue and connection |
| Scheduled tasks that failed | the `ScheduledTaskFailed` event, with the command and its schedule |
| JavaScript errors | a small script in the pages, [switched on in the settings](#javascript-errors) |

Each event keeps the exception chain (up to three previous exceptions) with code around every line of the trace, the request kept thin on purpose — the address with secret query parameters taken out, the method, the route, the site, the language and the signed-in user's id — the release, the environment, versions of PHP and Laravel, and the last 30 queries and log lines before the error. Headers, cookies, the request body and the IP address are never stored.

To report something yourself:

```php
use Atrium\Errors\Errors;

app(Errors::class)->capture($exception, context: ['order' => $order->id]);
```

Exceptions listed in `ignore` in `config/atrium-errors.php` are never caught.

## How errors are grouped

An error is the same one when it has the same class and happens in the same place: the first five frames of your own code, by file and function. Line numbers do not count, so an edit above the line does not make a new error; neither do numbers, ids or quoted values in the message. Compiled views and built bundles are recognised by name without their hash. When there is no code to tell the place by, the message with the changing parts blanked decides.

## The admin screens

**Site health → Errors** lists one line per error: its class and message, where it happens, the level and the source, how many times and for how many users, and when it was seen last. Buttons above the list switch between unresolved, resolved, ignored and all; the filters look for words in the class, message and place, and narrow the list by site, release, level, source and address. It sorts by last or first seen, by events or by users.

An error's page shows:

- buttons to resolve, resolve in the next release, ignore or reopen;
- events, users, first and last seen, first and last release;
- a chart by hour for the last 24 hours or by day for 30 days, with the number on every bar;
- the addresses and releases it happens in most;
- the trace of one event: your own frames with the code around them, the framework's frames behind a button;
- the request, the environment and what happened before the error;
- every event, newest first, each opening its own trace.

The **Errors in 24 hours** tile on the dashboard counts events, new errors, errors that came back and unresolved ones, draws the events hour by hour and lists the five noisiest errors.

## Statuses and «resolve in next release»

| Status | Meaning |
|---|---|
| Unresolved | New or reopened. Alerts are sent |
| Resolved | Fixed. A new event reopens it and sends a «came back» alert |
| Ignored | Known and harmless. Events are still counted, no alerts |

**Resolve in next release** is for an error fixed in code that is not deployed yet. The error is marked resolved in the release running now; events from this release do not reopen it, and the first event of any other release does. The release is the git commit of the checkout, or `ATRIUM_RELEASE` if you set it in the deploy script, or else the application's version from Composer.

## Alerts

Alerts go by e-mail, to Telegram, or both:

- a **new** error;
- an error that **came back** after it was resolved;
- a **spike**: an error with more events in an hour than the threshold (100 by default; 0 turns spikes off).

Each error alerts once per reason, a spike at most once an hour, and all of them together at most ten times in ten minutes, so an outage does not bury the inbox. Mail goes through the admin's mail settings under its own purpose, so it may use its own sender. For Telegram, create a bot with `@BotFather`, add it to a chat, and put its token and the chat id in the settings.

## Drivers: database, Sentry, log

An event goes to every driver that is switched on, each on its own: one that fails does not stop the others, and its error goes to the fallback log.

| Driver | What it does |
|---|---|
| `database` | Keeps events in the site's database for the admin screens. On by default |
| `sentry` | Sends events to Sentry, self-hosted Sentry or [GlitchTip](https://glitchtip.com), which speaks the same protocol. Needs only the DSN, no SDK |
| `log` | Writes a line to the application's log, for log collectors that already read it |

```dotenv
ATRIUM_ERRORS_DRIVERS=database,sentry
ATRIUM_ERRORS_SENTRY_DSN=https://public-key@errors.example.com/7
```

The `sentry` driver also takes `SENTRY_LARAVEL_DSN` or `SENTRY_DSN` when `ATRIUM_ERRORS_SENTRY_DSN` is empty.

## Your own driver: Bugsnag, Rollbar, a webhook

A driver is a class implementing `Atrium\Errors\Contracts\ErrorReporter`:

```php
namespace App\Errors;

use Atrium\Errors\Contracts\ErrorReporter;
use Atrium\Errors\Event;
use Illuminate\Support\Facades\Http;

final class WebhookReporter implements ErrorReporter
{
    public function report(Event $event): void
    {
        Http::timeout(3)->post('https://hooks.example.com/errors', [
            'id' => $event->id,
            'level' => $event->level,
            'title' => $event->class.': '.$event->message,
            'release' => $event->release,
            'fingerprint' => $event->fingerprint,
        ]);
    }
}
```

Register it under a name and switch it on:

```php
// config/atrium-errors.php
'reporters' => [
    'webhook' => App\Errors\WebhookReporter::class,
],
```

```dotenv
ATRIUM_ERRORS_DRIVERS=database,webhook
```

The class is made by the container, so its constructor may ask for anything bound there. The event arrives already scrubbed, sampled and within the cap. `report()` runs after the response has gone to the visitor, or in a queue job, so it must not rely on the request. It may throw: the error goes to the fallback log, and the other drivers still get the event.

`Event` holds `id`, `kind` (`php`, `job`, `schedule`, `browser`, `message`), `level`, `class`, `message`, `exceptions` (each with `class`, `message` and `frames`, the frame of the throw first), `request` (`url`, `path`, `method`, `route`, `site_id`, `locale`, `user_id`), `context`, `breadcrumbs`, `environment`, `release`, `runtime`, `fingerprint` and `timestamp`.

For Bugsnag or Rollbar, call their PHP client in `report()`: `Bugsnag::notifyError($event->class, $event->message)` or `Rollbar::log($event->level, $event->message, [...])`. Pass the `fingerprint` as their grouping hash so that both group errors the same way.

## Using sentry-laravel instead

If you prefer Sentry's own SDK (`sentry/sentry-laravel`) for its performance tracing, install it as Sentry's docs say and leave `sentry` out of `ATRIUM_ERRORS_DRIVERS`, so each error is not sent twice. Both can run together: the SDK reports to Sentry, this module keeps its own copy for the admin.

## JavaScript errors

Switch on **Catch JavaScript errors on the site** or **in the admin** in the settings (or `ATRIUM_ERRORS_BROWSER_SITE=true`, `ATRIUM_ERRORS_BROWSER_ADMIN=true`). The module then adds a small script to every full HTML page itself; no layout or theme needs editing. A page with a Content-Security-Policy gets the script with Vite's nonce.

The script sends at most five errors per page and never the same one twice, with only the message, the type and the stack. The server adds the address and scrubs it. Reports go to `POST /atrium-errors/browser`, which accepts ten per minute from one visitor and 16 KB at most each.

## Secrets and personal data

Before an event is stored or sent anywhere, it is scrubbed:

- headers, cookies, the request body and the IP address are not taken at all;
- values under keys that name a password, secret, token, API key, authorization, cookie, session, CSRF token, card, CVV, IBAN, signature, DSN, e-mail, phone, PIN or one-time code are replaced with `[Filtered]` — in the context and the job's data, including keys like `user_password` or `X-Api-Key`, and in query parameters of the address;
- in any text — messages, addresses, queries, log lines — `password=…`-like pairs are filtered; e-mails become `[email]`, card numbers (checked with Luhn, so an order number stays) become `[card]`, phone numbers become `[phone]`.

Add your own keys in `scrub_keys` in `config/atrium-errors.php`.

## Load: sampling, the cap, the queue

The tracker must never take the site down with it:

- **Sampling**: keep only part of the events, 1–100 percent (`ATRIUM_ERRORS_SAMPLE_RATE`, 100 by default).
- **Cap**: at most this many events a minute for the whole site (`ATRIUM_ERRORS_PER_MINUTE`, 60 by default); the rest is dropped before any work is done.
- **Writing later**: events are stored after the response has gone to the visitor; with `ATRIUM_ERRORS_QUEUE=redis` they go through that queue connection instead.
- **Fallback log**: when the database cannot be written to, the event goes to `storage/logs/atrium-errors.log`, one JSON line each.
- **Retention**: events older than 90 days are deleted at 03:40 every night (`ATRIUM_ERRORS_RETENTION_DAYS`); errors left without events go with them.

An error inside the tracker itself goes to the fallback log and is never thrown.

## Permissions and sites

Two rights in **Roles**:

| Right | Gives |
|---|---|
| `errors.view` — View errors | The list, the error pages, the status buttons and the dashboard tile |
| `errors.settings` — Settings | The module's settings: drivers, DSN, alerts, sampling |

On install, roles that have the settings right also get the view right. One does not include the other: a role may see errors without changing where they go, or the other way round.

An administrator limited to some sites sees only errors that happened on those sites, and only their events. Errors with no site (commands, queue jobs) are seen only by administrators of all sites.

## Settings

**Modules → Errors → Settings** in the admin; a value saved there wins over `.env`, which gives the defaults. The DSN and the Telegram token are better kept in `.env`.

| In the admin | `.env` | Default |
|---|---|---|
| Drivers | `ATRIUM_ERRORS_DRIVERS` | `database` |
| Sentry DSN | `ATRIUM_ERRORS_SENTRY_DSN` | `SENTRY_LARAVEL_DSN`, `SENTRY_DSN` |
| Events kept, % | `ATRIUM_ERRORS_SAMPLE_RATE` | 100 |
| Events a minute at most | `ATRIUM_ERRORS_PER_MINUTE` | 60 |
| Days to keep | `ATRIUM_ERRORS_RETENTION_DAYS` | 90 |
| Alert e-mail | `ATRIUM_ERRORS_ALERT_EMAIL` | none |
| Telegram bot token | `ATRIUM_ERRORS_TELEGRAM_TOKEN` | none |
| Telegram chat | `ATRIUM_ERRORS_TELEGRAM_CHAT` | none |
| Alert on new / came back | | on / on |
| Spike: events an hour | | 100 |
| JavaScript on the site / in the admin | `ATRIUM_ERRORS_BROWSER_SITE`, `ATRIUM_ERRORS_BROWSER_ADMIN` | off / off |

Only in `.env`:

| `.env` | Default | What it does |
|---|---|---|
| `ATRIUM_ERRORS_ENABLED` | `true` | `false`: nothing is caught; the admin screens stay |
| `ATRIUM_RELEASE` | git commit, else Composer version | The release events are grouped by |
| `ATRIUM_ERRORS_QUEUE` | none: after the response | A queue connection to store events through |

The rest — ignored exceptions, extra keys to scrub, breadcrumbs kept, code lines around the trace, the fallback log's path — is in `config/atrium-errors.php` after `php artisan vendor:publish --tag=atrium-errors-config`.

## Commands

```bash
php artisan atrium:errors:test           # send a test error to every driver
php artisan atrium:errors:prune          # delete old events now
php artisan atrium:errors:prune --days=30
```
