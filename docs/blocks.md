# Blocks: write once, show anywhere

[Русский](blocks.ru.md)

The blocks module (`alveby/atrium-blocks`) keeps small pieces of text or HTML in one place: the phone number that is in the header, the footer and the contacts page, a line about delivery, a banner. Change it in **Blocks** and it changes everywhere.

## Install

```bash
composer require alveby/atrium-blocks
php artisan atrium:module:install alveby/atrium-blocks
```

## In the admin

**Blocks** has one record per block:

| Field | What it is |
|---|---|
| Active | A block that is off shows nothing, wherever it is put. |
| Name | For the editors: what the list and the editor's picker show. |
| Code | Short, in Latin letters, unique within a site (`phone`). A template asks for the block by it. |
| Content | The text, with the same three modes as any text: plain, HTML, visual editor. Written per content language; a language without a text shows the default one. |

A block belongs to a site: each site has its own `phone`.

## In a text

In any visual editor, click **+**, choose **Block**, pick the block by name. The editor shows a chip `Block: Phone number` and the site draws the block in its place ([how embeds work](embeds.md)). The placeholder holds the block's id, so changing its code later breaks nothing. A block may hold a form or another block.

## In a template

```blade
<a href="tel:+100">@block('phone')</a>
```

or, in PHP and in a template that prints raw HTML:

```blade
{!! atrium_block('phone') !!}
```

The block is the text as it was written: HTML goes out as HTML, so give editors this module only if they may write HTML. When the site has no such block, it is off or the module is not installed, the place stays empty. The site's language is used; a block of another site is never shown.

## Speed

A block is read once per site, language and code and kept in the cache for an hour; saving or deleting any block makes the next page read afresh, on every cache store. A change made behind the model's back (a raw SQL update) waits for the hour to pass or for `php artisan cache:clear`. A form or block inside a block is put in its place on every request, after the cache.

## A block that holds itself

A block that holds itself, directly or through other blocks, is cut where it would repeat: `A` holding `B` holding `A` shows `A`'s text, `B`'s text and then nothing.

## Files

The English and Russian labels are written by hand, the other 13 languages are machine translations; override `atrium-blocks::blocks` in your own `lang/vendor/atrium-blocks` to change a word (`php artisan vendor:publish --tag=atrium-blocks-lang`).
