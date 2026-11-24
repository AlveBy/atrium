# Atrium 1C

Exchange between the [Atrium](https://github.com/AlveBy/atrium) catalogue and **1C:Enterprise** over the standard **CommerceML 2** protocol, the one 1C-Bitrix speaks. A stock 1C configuration connects without changes on the 1C side: only the site address, the login and the password are set in the exchange settings.

- **From 1C:** sections, products, properties, pictures, prices and stock go into [`alveby/atrium-catalog`](../catalog/README.md), matched by the 1C identifier, so a repeated exchange updates instead of duplicating. Zip archives and files sent in chunks are accepted; a big file is imported in steps.
- **An exchange screen** in the admin: when the exchange ran, what came, what was skipped, the errors. Every exchange is also a line of the exchange log of [`alveby/atrium-integrations`](../integrations/README.md).

Orders to 1C (`type=sale`) need the shop module and are not part of this package yet: 1C gets an empty list of orders and nothing breaks.

## Install

```bash
composer require alveby/atrium-catalog alveby/atrium-integrations alveby/atrium-1c
php artisan atrium:module:install alveby/atrium-catalog
php artisan atrium:module:install alveby/atrium-integrations
php artisan atrium:module:install alveby/atrium-1c
```

Set the login and the password in **Modules → 1C**, then give 1C the address the screen shows. The step-by-step setup in 1C, the limits and what to do when something does not arrive: [docs/1c.md](https://github.com/AlveBy/atrium/blob/main/docs/1c.md).

## License

MIT
