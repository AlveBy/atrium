# Atrium Bitrix24

Connects the [Atrium](https://github.com/AlveBy/atrium) admin to a Bitrix24 portal through an incoming webhook:

- every **form submission** becomes a **lead**, or a **contact with a deal**, with a per-form choice of which form field goes to which Bitrix24 field;
- the **chat widget** code of Bitrix24 is shown on the pages of the site;
- a **Check connection** button; the webhook is stored encrypted and shown only as a mask.

A send that fails is tried again (five attempts) and every attempt is a line of the exchange log of [`alveby/atrium-integrations`](../integrations/README.md).

## Install

```bash
composer require alveby/atrium-integrations alveby/atrium-forms alveby/atrium-bitrix24
php artisan atrium:module:install alveby/atrium-integrations
php artisan atrium:module:install alveby/atrium-forms
php artisan atrium:module:install alveby/atrium-bitrix24
```

Run a queue worker (`php artisan queue:work`), then paste the webhook in **Modules → Bitrix24** and press **Check connection**. How to make the webhook, the field mapping and what to do when something does not arrive: [docs/bitrix24.md](https://github.com/AlveBy/atrium/blob/main/docs/bitrix24.md).

Orders and deal stages need the shop module and are not part of this package yet.

## License

MIT
