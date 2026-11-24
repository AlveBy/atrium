# Atrium Integrations

The base of the connectors of the [Atrium](https://github.com/AlveBy/atrium) admin (1C, Bitrix24 and whatever comes next), so each of them does not write the same three things again:

- **a queue job with retries**: five attempts, pauses of 10 s, 1 min, 5 min and 15 min;
- **an exchange log** in the admin: when, which connector, what, about what, how it ended and why not;
- **an alert to the administrators** when a connector gives up: one letter per integration per hour at most.

Secrets (a webhook address, a password) are hidden in everything the log stores.

## Install

```bash
composer require alveby/atrium-integrations
php artisan atrium:module:install alveby/atrium-integrations
```

Run a queue worker (`php artisan queue:work`) and cron `php artisan schedule:run` every minute. Alert addresses and how long the log is kept are set in **Modules → Integrations**.

Your own connector extends `Atrium\Integrations\Jobs\IntegrationJob` or calls `app(Atrium\Integrations\Exchange::class)->record(...)`; both are described, with the hook for other alert channels, in [docs/integrations.md](https://github.com/AlveBy/atrium/blob/main/docs/integrations.md).

## License

MIT
