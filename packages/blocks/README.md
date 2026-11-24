# Atrium Blocks

Reusable blocks for [Atrium](https://github.com/AlveBy/atrium): a phone number, an address or a banner text written once and shown wherever it is put, in a template with `@block('phone')` or inside any text with the editor's **+** button. Per content language and per site, cached and refreshed on save.

## Install

```bash
composer require alveby/atrium-blocks
php artisan atrium:module:install alveby/atrium-blocks
```

`atrium:module:install` creates the table and turns the module on; `atrium:module:remove alveby/atrium-blocks` turns it off and asks whether to keep the blocks.

**Blocks** appears in the admin menu: name, code (what a template asks for), content (a `ContentField`, per language) and active.

## Use

```blade
<a href="tel:+100">@block('phone')</a>
{!! atrium_block('footer-note') !!}
```

In a text, **+** → **Block** → pick one. See [docs/blocks.md](../../docs/blocks.md) and [docs/embeds.md](../../docs/embeds.md).

## Tests

From the repository root:

```bash
vendor/bin/phpunit packages/blocks/tests
```

## Contributing

This package lives in `packages/blocks` of [AlveBy/atrium](https://github.com/AlveBy/atrium). Issues and pull requests go there.

## License

MIT
