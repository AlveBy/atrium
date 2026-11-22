# Monitoring: speed and uptime

[English](monitoring.md) · [Русский](monitoring.ru.md)

`alveby/atrium-monitoring` measures how fast the site answers and whether it answers at all, on your own server, with no outside service. It does three things:

- **server timings** of every request: time to the first byte, database queries and their time, cache hits and misses, peak memory, slow queries with their SQL;
- **visitors' Web Vitals** (LCP, INP, CLS, TTFB, FCP) from real browsers, through a script under 2 KB, without cookies or personal data;
- **uptime**: the front page of every site and the addresses you add are opened every minute; after a few failures in a row an incident is opened and an e-mail and a Telegram message go out.

The numbers are on the **Site health → Speed and uptime** screen, by day, week or month, as p50, p75 and p95, and in a tile on the dashboard.

- [Install](#install)
- [What is measured](#what-is-measured)
- [The screen](#the-screen)
- [Uptime and alerts](#uptime-and-alerts)
- [Privacy](#privacy)
- [How it stays cheap](#how-it-stays-cheap)
- [Percentiles](#percentiles)
- [Retention](#retention)
- [Configuration](#configuration)
- [Commands](#commands)
- [For other packages](#for-other-packages)
- [Contrast and charts without colour](#contrast-and-charts-without-colour)

## Install

```bash
composer require alveby/atrium-monitoring
php artisan atrium:module:install alveby/atrium-monitoring
```

The command creates the tables and turns the module on. Until then the package does nothing: no middleware, no script, no checks. The flush, the uptime checks and the cleanup run from Laravel's scheduler, so cron must call `php artisan schedule:run` every minute.

Defaults are in `config/atrium-monitoring.php` (`php artisan vendor:publish --tag=atrium-monitoring-config`). The alert settings are in the panel, in **Modules → Monitoring**.

The screen is open to every role with the **View speed and uptime reports** permission (`monitoring.view`). On install it is given to every role that may see the uptime checks; superadmins always have it.

## What is measured

**On the server**, a middleware in the `web` group records one line per request:

| Value | What it is |
|---|---|
| Time to the first byte | from the start of the request to the response being ready |
| Database | number of queries and their total time |
| Cache | hits and misses (from the cache module when it is there, see [below](#for-other-packages)) |
| Memory | peak memory of the request |
| Errors | the share of answers with a 5xx status |

Requests are grouped by **route** (its name, or its pattern such as `/catalog/{slug}` when it has none), by **site** and by **language**. A query slower than 100 ms (`slow_query_ms`) is kept with its SQL, with every value replaced by `?`, so no e-mail, password or id ends up in the log; the same query from different places is one row. At most 20 slow queries are kept per request.

**In the browser**, the public theme gets a script that waits for the page to be hidden and then sends, once, with `navigator.sendBeacon`:

| Metric | Good up to | Poor above |
|---|---|---|
| LCP, Largest Contentful Paint | 2.5 s | 4 s |
| INP, Interaction to Next Paint | 200 ms | 500 ms |
| CLS, Cumulative Layout Shift | 0.1 | 0.25 |
| TTFB, Time to First Byte | 800 ms | 1.8 s |
| FCP, First Contentful Paint | 1.8 s | 3 s |

These are Google's thresholds, applied to the 75th percentile, as Google does. With the page, it sends the kind of device (phone, tablet, computer, from the screen width) and the connection type when the browser knows it (`4g`, `3g`…).

Another theme adds the script with one tag in its layout:

```blade
<x-atrium-monitoring::rum />
```

## The screen

**Site health → Speed and uptime** (`/admin/monitoring`) has three parts.

- **Server**: requests measured, time to the first byte (p50, p75, p95), the share of server errors, cache hits, queries per request and memory at p95; charts of the time to the first byte and of the database time; the slowest routes and the slowest queries. A route opens the same screen filtered to that route; a query opens its own page, with its time by day and the routes it runs on.
- **Visitors**: the five Web Vitals at p75 with their rating written out (Good, Needs improvement, Poor), a chart of each, and the slowest pages by LCP. A page opens the screen filtered to it.
- **Uptime**: every check with its status, uptime and last response, and the recent incidents.

Filters: the period (30 days by day, 26 weeks by week, 13 months by month), the site, the language and the device. The table of pages counts every device and language, so it is hidden while one of those filters is on.

With the error log (`alveby/atrium-errors`) installed, a page in the slowest pages table, and a page on a query's own screen, also link to the errors of that address (for an administrator who may read the log). The sidebar group "Site health" holds the report, the uptime checks and the incidents, next to the error log.

Numbers are written the way the panel's language writes them: `2.91 s` in English, `2,91 с` in Russian.

The dashboard tile **Speed and uptime** shows the Web Vitals at p75 over 7 days, uptime over 30 days, what is down right now and the five slowest routes. It is shown only to those who may open the screen.

## Ask the helper

With the AI module (`alveby/atrium-ai`) installed, the helper in the corner of the admin has one more tool, `site_speed`. Ask "how fast is the site?" or "which pages are slowest this week?": it answers from the same numbers as the screen, for the last 7 days or the number you name (up to 90). The tool gives the server response time (average, median and p95), the database time, the share of failed requests, the five slowest routes and a link to the screen. It is offered only for an administrator who may open the screen (`monitoring.view`); to anyone else it says so and shows no number.

## Uptime and alerts

Every minute (`atrium:monitoring:uptime`):

1. Each active site gets a check of its front page, following its domain and folder (`https://shop.example.com/`, `https://example.com/blog/`). A switched-off site's check is switched off too. Other addresses are added under **Site health → Uptime checks**.
2. Every active address is opened, ten at a time, with a 10-second timeout. An answer below 400 is up; 4xx, 5xx, a timeout or a refused connection is a failure.
3. A check moves through four states:

| Now | Result | Next | What happens |
|---|---|---|---|
| any | up | Up | an open incident is closed, «back up» alert |
| Up / Not checked | failure, fewer than N in a row | Failing | nothing yet |
| Failing | the N-th failure in a row | Down | an incident is opened, «down» alert |
| Down | failure | Down | the incident counts the failures, no new alert |

N is **Failed checks before an incident** in the settings (3 by default: three minutes without an answer).

Alerts go:

- **by e-mail** to the addresses in **Alert e-mails** (commas or new lines). They go through Laravel's mailer; with the mail module installed it takes them over, with its accounts and history.
- **by Telegram**: create a bot with @BotFather, add it to a chat or channel, and fill in **Telegram bot token** and **Telegram chat** (`-1001234567890` or `@site_alerts`). The token is kept in the settings table, never in the code; `ATRIUM_MONITORING_TELEGRAM_TOKEN` and `ATRIUM_MONITORING_TELEGRAM_CHAT_ID` in `.env` win over it. Never commit a token.

The alert is written in the application's language. A failed alert is logged, without the token, and does not stop the checks.

## Privacy

- No cookies, no local storage, no fingerprint.
- The IP address is not stored anywhere: it is only used, in memory, to limit beacons to 30 a minute from one address.
- The page address is kept without its query string and its `#fragment`, so `?email=` or a campaign id never reach the database.
- Visitors with **Do Not Track** or **Global Privacy Control** on are not measured at all: the script is not even printed.
- `rum.sample_rate` measures only a share of page views (0.1 = one in ten).
- Slow queries are kept with every value replaced by `?`.

Nothing of this needs a cookie banner.

## How it stays cheap

A measured request does not touch the database. The middleware appends one short JSON line to a file under `storage/app/atrium-monitoring`, after the response has been sent. Once a minute `atrium:monitoring:flush` takes the file by renaming it, writes the raw rows in chunks and adds every request to its day's histogram, in one transaction under a lock. When the file passes 256 KB before the minute is up, the request that notices it flushes, or hands the flush to a queue when `buffer.queue` names a queue connection. `server.sample_rate` measures only a share of requests.

`php artisan atrium:monitoring:benchmark` measures what the middleware adds. It runs the same simulated request (20 queries, two cache reads, through the real event dispatcher) without and with the middleware, in rounds, writing to a real file, and reports the median round. It fails when the middleware adds 1 ms or more. On PHP 8.4 in Docker (`php:8.4-apache`, Linux, no Xdebug):

| Queries per request | Added per request |
|---|---|
| 20 | 0.14–0.20 ms |
| 0 | 0.04 ms |

Xdebug, in any mode, slows PHP down several times; measure without it.

## Percentiles

p50 is the value half of the requests (or page views) were faster than; p75, three in four; p95, all but one in twenty. p75 is what Google rates a site by; p95 shows the slow tail that an average hides.

Each day is kept as a histogram per value with buckets 5% apart (a log scale from 1 µs), so a percentile read from it is within about 2.5% of the exact one. Histograms add up, so a week or a month is the sum of its days, and the percentiles of a month are those of all its requests, not an average of daily percentiles.

## Retention

| Data | Kept | Table |
|---|---|---|
| Raw requests, slow queries, page views, check results | 7 days | `monitoring_requests`, `monitoring_queries`, `monitoring_vitals`, `monitoring_check_results` |
| Daily histograms | 13 months | `monitoring_daily` |
| SQL of slow queries | as long as their daily rows | `monitoring_query_texts` |
| Closed incidents | 13 months | `monitoring_incidents` |

`atrium:monitoring:prune` deletes the rest every night at 03:17, in slices of 5,000 rows so a big table is never locked for long. Both periods are in `retention`.

## Configuration

| Key | `.env` | Default | |
|---|---|---|---|
| `server.enabled` | `ATRIUM_MONITORING_SERVER` | `true` | server timings |
| `server.sample_rate` | `ATRIUM_MONITORING_SAMPLE_RATE` | `1.0` | share of requests measured |
| `server.slow_query_ms` | `ATRIUM_MONITORING_SLOW_QUERY_MS` | `100` | a slower query is kept |
| `server.except` | | `up`, `atrium/rum`, `vendor/*`, `build/*`, `storage/*` | paths never measured |
| `buffer.path` | `ATRIUM_MONITORING_BUFFER` | `storage/app/atrium-monitoring` | the buffer file |
| `buffer.max_bytes` | | `262144` | flush early above this |
| `buffer.queue` | `ATRIUM_MONITORING_QUEUE` | — | queue connection for an early flush |
| `buffer.lock_store` | `ATRIUM_MONITORING_LOCK_STORE` | default cache | the flush lock; use a shared store with several servers |
| `rum.enabled` | `ATRIUM_MONITORING_RUM` | `true` | the visitors' script |
| `rum.sample_rate` | `ATRIUM_MONITORING_RUM_SAMPLE_RATE` | `1.0` | share of page views measured |
| `rum.rate_limit` | | `30` | beacons a minute from one address |
| `rum.theme` | | `true` | the public theme prints the script by itself |
| `uptime.enabled` | `ATRIUM_MONITORING_UPTIME` | `true` | uptime checks |
| `uptime.scheme` | `ATRIUM_MONITORING_UPTIME_SCHEME` | `https` | scheme of the front-page checks |
| `uptime.timeout` | | `10` | seconds per check |
| `uptime.failures` | | `3` | default for the setting |
| `retention.raw_days` | | `7` | |
| `retention.aggregate_months` | | `13` | |
| `schedule` | | `true` | add the tasks to the scheduler |

## Commands

| Command | |
|---|---|
| `atrium:monitoring:flush` | write the buffer to the database (every minute) |
| `atrium:monitoring:uptime` | check every address (every minute) |
| `atrium:monitoring:prune` | delete old data (daily, 03:17) |
| `atrium:monitoring:benchmark --requests=2000 --queries=20 --rounds=5` | measure the middleware's cost |

With `schedule` set to `false`, schedule them yourself.

## For other packages

**Cache counters.** When a filter `atrium.cache.stats` is registered, the module takes the request's cache hits and misses from it instead of counting Laravel's cache events. The filter gets `['hits' => 0, 'misses' => 0]` and returns the numbers of the current request:

```php
Hook::filter('atrium.cache.stats', fn (array $stats): array => [
    'hits' => $this->hits,
    'misses' => $this->misses,
]);
```

The module reads it when the request terminates. Anything else than an array falls back to the events.

**Incidents.** `atrium.monitoring.incident.opened` and `atrium.monitoring.incident.resolved` fire with the `Incident` model (its `check` loaded), for a third channel such as Slack or an SMS:

```php
Hook::on('atrium.monitoring.incident.opened', function (Incident $incident): void {
    // $incident->check->url, $incident->started_at, $incident->last_error
});
```

## Contrast and charts without colour

Each chart draws p50, p75 and p95, told apart without colour: p50 has circles and a solid line, p75 squares and a dashed line, p95 triangles and a dotted line, and each line has its name written at its end. Under every chart the same numbers are in a table, and the SVG carries them in its `<title>` and `<desc>` for screen readers. A rating is written out in words; an uptime status has its name and a sign (● up, ◐ failing, ○ down).

The line colours, against the panel's surface (WCAG 2 contrast ratio; 4.5:1 is the minimum for text, 3:1 for lines):

| Line | Light themes | on white | on the darkest light page (#f7f3ec) | Night | on #161d27 |
|---|---|---|---|---|---|
| p50 | #0B5FA5 | 6.57:1 | 5.94:1 | #6CB4F0 | 7.61:1 |
| p75 | #B34700 | 5.50:1 | 4.97:1 | #F5A05B | 8.12:1 |
| p95 | #374151 | 10.31:1 | 9.32:1 | #E5E7EB | 13.69:1 |

The High contrast theme draws all three in black (21:1) and leaves them to the markers and dashes. Axis text uses the theme's muted colour (7.56:1 on white in Graphite, 7.13:1 on #161d27 in Night).
