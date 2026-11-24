# Integrations

[English](integrations.md) · [Русский](integrations.ru.md)

`alveby/atrium-integrations` is the small base that Atrium's connectors stand on: 1C, Bitrix24 and, later, amoCRM, MoySklad and the rest. It gives every connector three things, so none of them writes its own:

- a **queue job with retries**: a call to another system that fails is tried again after 10 seconds, a minute, five minutes and a quarter of an hour, five attempts in all;
- an **exchange log** in the admin: when, which connector, what, about what, how it ended and, if it did not, why;
- an **alert to the administrators** when a connector has given up.

A connector (Bitrix24, 1C) works without it, but it then has no screen with its history: the lines go to the application log. Install it first.

- [Install](#install)
- [The exchange log](#the-exchange-log)
- [Retries](#retries)
- [Alerts](#alerts)
- [Settings](#settings)
- [Writing a connector on this base](#writing-a-connector-on-this-base)
- [Hooks](#hooks)
- [Commands](#commands)

## Install

```bash
composer require alveby/atrium-integrations
php artisan atrium:module:install alveby/atrium-integrations
```

The queue must run (`php artisan queue:work`, or a supervisor/cron setup of your host) and cron must call `php artisan schedule:run` every minute. With `QUEUE_CONNECTION=sync` a call is made during the request and is never retried.

## The exchange log

**Exchange log** in the menu lists every line, newest first. A line says:

| Column | Meaning |
|---|---|
| When | Date and time of the attempt |
| Integration | `bitrix24`, `1c`, … |
| Action | What was done: `lead.add`, `catalog.import`, … |
| About | What it was done to, in words: «Form submission #12» |
| Result | **Done**, **Failed, will retry** (more attempts are coming) or **Failed** (it gave up) |
| Error | Why, for a failure |

Open a line to read the whole text, the attempt number and how many milliseconds the call took. Narrow the list by result or by integration; delete lines you do not need. Lines older than 90 days are deleted every night (set the number in the module's settings).

Texts are cut at 2000 characters. A connector names its secrets (a webhook address, a password), and they are replaced by `***` in everything written to the log, so the screen is safe to show to a colleague or to paste in a bug report.

## Retries

A job of a connector is tried five times. After a failure it waits 10 seconds, then 60, 300 and 900, and every attempt leaves a line: **Failed, will retry** for the first four, **Failed** for the last one. If an attempt works, the job is done and the line says so. A job that is stuck for over a minute is stopped and counts as a failed attempt.

The pauses are in `config/atrium-integrations.php` (`backoff`); publish it with `php artisan vendor:publish --tag=atrium-integrations-config`.

## Alerts

When the last attempt fails, the administrators get a letter: which integration, what it could not do, about what, the error, how many attempts, and a link to the log. It goes to the addresses in **Modules → Integrations → Alert e-mails**, or to every active superadministrator when none are given.

So that a connector that is down for a night does not send a letter per record, one integration speaks at most **once an hour** (`alert_interval`, minutes, in the config or `ATRIUM_INTEGRATIONS_ALERT_INTERVAL`). The failures are all in the log anyway.

The letter has the purpose `integrations`, so the mail module, when installed, sends it from the account chosen for it. A letter that cannot be sent is logged by the kind of error only and changes nothing else.

## Settings

**Modules → Integrations**:

- **Alert e-mails**: one or more addresses, separated by commas or new lines. Empty means every active superadministrator.
- **Keep the log for, days**: 90 by default (`ATRIUM_INTEGRATIONS_RETENTION_DAYS`).

## Writing a connector on this base

Extend `Atrium\Integrations\Jobs\IntegrationJob`:

```php
use Atrium\Integrations\Jobs\IntegrationJob;

final class SendOrder extends IntegrationJob
{
    public function __construct(private readonly int $orderId) {}

    protected function integration(): string { return 'moysklad'; }
    protected function action(): string { return 'order.add'; }
    protected function subject(): ?string { return "Order #{$this->orderId}"; }
    protected function secrets(): array { return [Setting::get('moysklad.token', '')]; }

    protected function perform(): ?string
    {
        // call the API; throw on failure
        return 'Order created: '.$id;   // goes to "what was done"
    }
}

SendOrder::dispatch($order->id)->afterCommit();
```

Anything thrown from `perform()` is a failed attempt. What it returns is written as what was done.

Work that does not run in the queue (1C calls the site and the site answers at once) writes its lines itself:

```php
use Atrium\Integrations\Enums\{Direction, Status};
use Atrium\Integrations\Exchange;

app(Exchange::class)->record('1c', 'catalog.import', Status::Failed,
    subject: 'import.xml', error: 'Cannot read the file', direction: Direction::In);
```

A `Status::Failed` line also tells the administrators, with the same hourly limit. When the module is not installed, `record()` writes to the application log and the connector keeps working.

## Hooks

| Hook | Kind | Arguments |
|---|---|---|
| `atrium.integrations.failed` | action | `ExchangeLog` of the failure; fires for every final failure, even when the letter is held back by the hourly limit, so another channel (a messenger) can listen |

## Commands

```bash
php artisan atrium:integrations:prune
```

Deletes the log lines older than the retention period; the scheduler runs it every night at 03:41.
