# Menus

[Русский](menus.ru.md)

The menus module (`alveby/atrium-menus`) lets editors build the site's menus in the admin: the header, the footer, any other one the theme asks for. Each menu belongs to a site and to one language (or to all of them), and its items form a tree edited by dragging.

## Install

```bash
composer require alveby/atrium-menus
php artisan atrium:module:install alveby/atrium-menus
```

Add `--demo` to get a header and a footer menu in the installer's language (`APP_LOCALE`, any of the panel's 15; English when unknown), plus menus of their own for English, German and Russian, filled with the modules that are installed (the catalog unfolds its sections, news, stores and the FAQ get an item each).

## In the admin

**Menus** has one record per menu:

| Field | What it is |
|---|---|
| Name | For the editors only |
| Code | What the theme asks for: `header`, `footer`. Latin letters, digits, `-` and `_` |
| Language | One content language, or "All languages". A menu in the visitor's language wins over the one for all languages |
| Items | The tree |

The code is unique within a site and a language: the same `header` can exist once for all languages and once more for German.

Each item has:

- **Title**.
- **Link**: none (a heading that only opens its children), an address (`/contacts`, `https://…`, `mailto:…`), or a record of any module with pages chosen by name. A record link follows the record: rename its code or move it and the menu points to the new address; delete it or switch it off and the item disappears.
- **Opens in**: the same window or a new one.
- **Icon**: CSS classes, for example `icon icon-phone`.
- **Active**: a switched-off item hides with its children.
- **Unfold below** and **Levels to unfold**: the records of a module listed under the item. A tree module (catalog sections) unfolds its tree, up to five levels, from the root or from the record the item links to; a flat module lists its records. Only active records of the current site, by sort.

Items are dragged by the handle: above another item, below it, or onto it to become its child. The buttons next to each item do the same from the keyboard (up, down, in, out). A menu holds up to 500 items, four levels deep.

A record with a page on the site can also go to the menu from its own list or form: **Add to header menu** puts it, linked, at the end of the site's `header` menu for all languages. The button needs the right to edit menus; a record already there is not added twice.

## On the site

With the theme (`alveby/atrium-theme`), the module draws the header menu from the menu with the code `header` and adds the `footer` menu above the copyright line. Until the site has a `header` menu, the theme keeps its own menu of the modules.

Anywhere else:

```blade
<x-atrium-menu code="header" />
<x-atrium-menu code="footer" class="footer-menu" label="Site map" />
<x-atrium-menu code="header" language="de" />
```

The component prints a `<nav>` with nested `<ul>`. The link to the current page gets `aria-current="page"`; its item and every item above it get the class `is-active`; an item with children gets `has-children`. Nothing is printed when the site has no active menu with this code.

To draw it your own way, take the tree:

```php
$menu = atrium_menu('header');   // or atrium_menu('header', 'de')

// ['code' => 'header', 'name' => 'Header', 'items' => [
//     ['title' => 'Shop', 'url' => 'https://example.com/shop', 'target' => null,
//      'icon' => null, 'current' => false, 'active' => true, 'children' => [...]],
// ]]
```

`null` means there is no such menu.

To keep the theme's own header or footer, set them in `config/atrium-theme.php`; the module only fills keys that are `null`:

```php
'header_menu' => 'atrium-theme::partials.menu',
'footer_menu' => 'partials.footer',
```

## Caching

A resolved menu is cached per site, language and code. Saving a menu, or any record with public pages, starts the cache anew, so records unfolded under an item and links to records are up to date at once. The cache needs no tags, so any cache store works.

## API and MCP

Menus are a module of the admin's API, so a token with `menus:read` and `menus:write` reads and writes them at `/api/v1/menus`, the tree included:

```bash
curl -H "Authorization: Bearer <token>" https://example.com/api/v1/menus/1
```

```json
{"data": {"id": 1, "code": "header", "name": "Header", "language": null, "tree": [
    {"id": 3, "title": "Shop", "link": {"type": "url", "url": "/shop"}, "target": null,
     "icon": null, "active": true, "source": null, "source_depth": 1, "children": []}
]}}
```

A `PUT` with `tree` replaces the items: items that keep their `id` are updated, new ones are created, missing ones are deleted. A record link is `{"type": "record", "model": "Atrium\\Catalog\\Models\\Product", "id": 5}`. The MCP tools (`list_records`, `get_record`, `update_record`) work the same way.

## Change the look

```bash
php artisan vendor:publish --tag=atrium-menus-views
php artisan vendor:publish --tag=atrium-menus-lang
```
