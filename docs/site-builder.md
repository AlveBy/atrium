# Site builder: pages made of blocks

[Русский](site-builder.ru.md)

A page of the [pages module](../packages/pages/README.md) can be put together from blocks instead of written as one text: **sections** one under another, **columns** in a section, **blocks** in a column. The page is stored as data and drawn by Blade on the server. This guide describes what an editor does, the data format, and how a module adds a block type of its own.

An existing site gets the new column with `php artisan migrate`. Pages without blocks look exactly as before.

## What an editor does

The form of a page has **Sections and blocks** under the text.

1. **Add section**. It comes with one column over the whole width.
2. **Add column** if you want two or three side by side: they share the row equally. The width of a column is a number of twelfths (6 is a half) and can be changed.
3. Choose a block in a column and press **Add block**, then fill in its settings.
4. The arrow buttons move a section, a column or a block; the cross deletes it. Deleting a column deletes its blocks.
5. Save.

A page with at least one section is shown from its blocks; the **Text** field is kept but not shown. Delete every section and the text is shown again. On a screen narrower than 40rem the columns stand one under another.

With several content languages the language switch of the form also switches the texts of the blocks. A text that is not written in a language is shown in the default one.

## Built-in blocks

| Block | Key | Settings (`*` required, `⇄` per language) | Comes with |
|---|---|---|---|
| Text | `text` | `html` *⇄: visual editor or HTML. [Embeds](embeds.md) in it are drawn | theme |
| Picture | `picture` | `image` *, `alt` ⇄, `caption` ⇄, `link` | theme |
| Gallery | `gallery` | `images` * | theme |
| Button | `button` | `label` *⇄, `link` *, `style` (`primary`, `secondary`), `new_tab` | theme |
| Map | `map` | `point` * (`"48.8584, 2.2945"`), `zoom` (0–19, 15 by default), `address` ⇄ | theme |
| Reusable block | `block` | `block` *: the id of a [block](blocks.md) | `alveby/atrium-blocks` |
| Form | `form` | `form` *: the id of a [form](forms.md); `modal`: a button that opens it in a window | `alveby/atrium-forms` |
| Menu | `menu` | `menu` *: the code of a [menu](menus.md), so each language shows its own menu of that code | `alveby/atrium-menus` |

Columns and sections are not blocks: they are the structure of the tree.

- A block that has nothing to show (a form that is switched off, an empty menu, a link to a hidden page on a button) is left out of the page.
- Pictures are resized by the [CDN or imgproxy](cdn.md) when an image driver is set; without one the file is shown as it was uploaded.
- The map is a frame from openstreetmap.org: the visitor's browser asks that site for it when the map comes into view. Mention it in your privacy policy if that matters to you.

## The data format

The tree is the `layout` column of the page (JSON). It lives on the record, so a page and its blocks are saved in one transaction, and the [history](history.md) of a page, restoring a version, access rights and the API work for blocks as for any field.

```json
{
    "version": 1,
    "sections": [
        {
            "id": "s1",
            "settings": { "width": "default", "tone": "plain" },
            "columns": [
                {
                    "id": "c1",
                    "settings": { "span": 6 },
                    "blocks": [
                        {
                            "id": "b1",
                            "type": "text",
                            "settings": { "html": { "en": "<p>Hello</p>", "de": "<p>Hallo</p>" } }
                        },
                        {
                            "id": "b2",
                            "type": "button",
                            "settings": {
                                "label": { "en": "Contact us", "de": null },
                                "link": { "type": "url", "url": "/contacts" },
                                "style": "primary",
                                "new_tab": false
                            }
                        }
                    ]
                }
            ]
        }
    ]
}
```

| Part | Rule |
|---|---|
| `version` | `1` (`Atrium\Admin\Builder\Layout::VERSION`). A tree of another version is not drawn |
| `sections` | A list, at most 100 |
| `columns` | A list in a section, at most 12 |
| `blocks` | A list in a column, at most 100 |
| `id` | 1 to 40 Latin letters, digits, `-` and `_`; unique in the whole tree. It stays with the node when it moves, so an editor can follow it |
| `type` | The key of a block type |
| section `settings` | `width`: `narrow`, `default`, `full`; `tone`: `plain`, `tinted` |
| column `settings` | `span`: 1 to 12 of the twelve columns of the row; 12 when absent |
| block `settings` | The values of the type's fields, by field name, in the shape the field keeps on a record (a link is `{"type": "url", "url": …}` or `{"type": "record", "model": …, "id": …}`, a gallery is a list of paths) |
| translatable setting | `{"en": …, "de": …}` by content language; `null` or absent: not written |

No other keys are allowed in a node.

### What is checked on save

Saving the form, the API and restoring a version check the whole tree: its shape, and the settings of every block against the validation rules of the type's fields. A setting the type does not have is refused. For a translatable setting the default language must satisfy the rules; the others may be empty. The first problem is reported with its place: `Section 1, column 2, block 1 (Button): The Link field is required.`

A block that arrives exactly as it is stored (the same id, type and settings) is not checked again. So a page stays editable after a module was removed: its blocks can be moved, kept or deleted. Restoring an old version that holds such a block is refused if the page does not have it any more.

The [API](configuration.md) reads and writes `layout` as an object. [Import and export](import-export.md) leave it out.

## How a page is drawn

`Atrium\Theme\Builder\LayoutRenderer` turns the sections into HTML:

```html
<div class="atrium-layout">
    <style>/* the grid, and the styles of the block types of this page */</style>
    <section class="b-section b-width-default b-tone-plain" data-section-id="s1">
        <div class="b-inner">
            <div class="b-column b-span-6" data-column-id="c1">
                <div class="b-block b-type-text" data-block-id="b1">…</div>
            </div>
        </div>
    </section>
    <script>/* the scripts of the block types of this page */</script>
</div>
```

- Styles and scripts are put into the page once per type that is drawn. A page loads nothing for types it does not use.
- A block of an unknown type (its module is gone) is left out, and the log says so, once per type on a page. A block that throws an error is left out and logged too. The rest of the page is drawn.
- A theme of your own changes a block's look with a view of the same name in `resources/views/vendor/atrium-theme/builder/blocks/`.
- The templates of a page (**Page**, **Full width, no heading**, your [own ones](modules.md#page-templates)) get the drawn blocks as `$content`, and `$built` says the page is made of blocks.

To draw a tree of your own, call the renderer in a controller, before the view:

```php
use Atrium\Admin\Builder\Layout;
use Atrium\Theme\Builder\LayoutRenderer;

$content = app(LayoutRenderer::class)->render(Layout::sections($record->layout));
```

and give a record that field with `LayoutField::make('layout')` and an `array` cast.

Two things the blocks do not take part in yet: the site search does not look into them, and the automatic meta description is made from the **Text** field, so write one on the **SEO** tab.

## Registering your own block type

A type is a class that extends `Atrium\Admin\Builder\BlockType`:

```php
use Atrium\Admin\Builder\BlockType;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextField;

final class PriceBlock extends BlockType
{
    public function label(): string
    {
        return __('acme-price::builder.label');
    }

    public function fields(): array
    {
        return [
            TextField::make('title')->label(__('acme-price::builder.title'))->required()->maxLength(255),
            NumberField::make('amount')->label(__('acme-price::builder.amount'))->required()->min(0),
        ];
    }

    public function translatable(): array
    {
        return ['title'];
    }

    public function view(): string
    {
        return 'acme-price::builder';
    }

    public function data(array $settings): ?array
    {
        $title = is_string($settings['title'] ?? null) ? $settings['title'] : '';
        $amount = $settings['amount'] ?? null;

        return $title === '' || ! is_numeric($amount) ? null : ['title' => $title, 'amount' => $amount];
    }

    public function styles(): array
    {
        return [__DIR__.'/../resources/css/price.css'];
    }
}
```

```blade
{{-- resources/views/builder.blade.php --}}
<p class="price"><span>{{ $title }}</span> <strong>{{ $amount }}</strong></p>
```

and is registered when the module boots:

```php
use Atrium\Admin\Builder\BlockTypes;
use Atrium\Admin\Hooks\Hooks;

$this->app->make(Hooks::class)->register(BlockTypes::REGISTRY, 'price', new PriceBlock, 90);
```

The editor, the check on save and the site know the type from this: a module needs no routes and no JavaScript of its own for the editor.

| Method | What it does |
|---|---|
| `label()` | The name in the editor's list, in the admin's language |
| `view()` | The Blade view of one block |
| `fields()` | The settings, as [form fields](modules.md). The field name is the key in `settings` |
| `translatable()` | The settings written per content language |
| `data($settings)` | What the view gets. Translatable settings arrive already in the visitor's language, or `null`. Return `null` to leave the block out. The stored tree may be older than your class: expect any setting to be missing or of another shape |
| `styles()`, `scripts()` | Paths of CSS and JavaScript files whose text goes into the page |
| `available()` | `false` while the module behind the type is not installed: the type is not offered and its blocks are not drawn |

Good to know:

- **The key** (`price`) is stored in pages: keep it stable.
- **The priority** (the last argument of `register`) orders the editor's list: the built-in types take 10 to 80, lower first. Registering a key that exists, with a higher priority, replaces that type and keeps its place.
- **Fields** that keep a plain value work in a block: text, number, checkbox, select, content, image, gallery, link, map and the like. Fields that need the record itself (links to many records, files of a record) do not. A select lists all of its options, so use it for lists of a reasonable size. A checkbox is stored as the editor sends it.
- **The view** is drawn on its own, before the page: `@push` and `@section` in it do not reach the page. Put styles and scripts into `styles()` and `scripts()`. `@once` works across the blocks of a page.
- **Escape** what you print with `{{ }}`: settings are typed by people.
- The files of `scripts()` must not contain the text `</script>`, nor those of `styles()` `</style>`: they are placed inside the tags.

## For editors of the tree

The admin field describes itself to the screen that edits it. `LayoutField` gives these props:

| Prop | What it holds |
|---|---|
| `types` | `[{type, label, fields}]`: the block types that work now, with their fields in the same shape as on a record form; `translatable: true` marks the per-language ones |
| `sectionFields`, `columnFields` | The settings of sections and columns, as fields |
| `maxSections`, `maxColumns`, `maxBlocks`, `version` | The limits and the format version |

The screen takes a tree and sends back the whole tree. Everything it may rely on is the format above; the current screen (`LayoutField.vue`) can be replaced without touching the stored pages or the block types.
