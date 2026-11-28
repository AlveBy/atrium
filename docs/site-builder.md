# Site builder: pages made of blocks

[Русский](site-builder.ru.md)

A page of the [pages module](../packages/pages/README.md) can be put together from blocks instead of written as one text: **sections** one under another, **columns** in a section, **blocks** in a column. The page is stored as data and drawn by Blade on the server. This guide describes what an editor does, the data format, and how a module adds a block type of its own.

An existing site gets the new column with `php artisan migrate`. Pages without blocks look exactly as before.

## What an editor does

A saved page has **Sections and blocks** under the text, with a line saying what the page holds and the button **Open the page editor**. The editor needs the right to change the page (`pages.update`). While a page is being created, the same blocks are put together right on the form, without a preview.

The editor has three parts: the blocks there are and the **page structure** on the left, the **preview** in the middle, the **settings** of what is selected on the right (on a narrower screen, under the structure).

1. **Add section**. It comes with one column over the whole width.
2. **Add column** if you want two or three side by side: they share the row equally. The width of a column is a number of twelfths (6 is a half) and can be changed.
3. Drag a block from the list into a column, or press it: it goes after the selected block, into the selected column, or to the end of the page, in a new section when the page has no column.
4. Select a block, a column or a section, in the structure or by clicking it in the preview, and fill in its settings.
5. Drag blocks between columns and sections. The arrow buttons move a section, a column or a block one step; **Move to column** under the settings of a block puts it at the end of any column; the cross deletes. Deleting a column deletes its blocks.
6. **Publish**.

Everything the mouse does has a button, so the editor works from the keyboard alone: **Tab** reaches every control, **Enter** or **Space** presses it. After a move the keyboard stays on what moved, after a deletion it goes to the neighbour, and screen readers are told what was added, moved or removed and where it is now. The selected node, the place a dragged block will land and a node with an error are marked with a frame, a sign or words, never with a colour alone. Dragging works on touch screens too.

### Preview

The preview is the page itself, drawn on the server by the theme and the template of the page, with what is in the editor now, saved or not. It is drawn again a moment after each change, in the language chosen in the language switch. A click in it selects the block, column or section under the pointer; links and forms do nothing there, and the scripts of the page do not run. A frame narrower than 800 pixels shows the page scaled down. A block that the page does not show yet (nothing to show in it, or its module is gone) is marked **Not shown on the page yet** in the structure.

### Draft, publishing and earlier versions

| Button | What it does |
|---|---|
| **Save draft** | Keeps what is in the editor without changing the site. The blocks may be unfinished. The editor opens with the draft next time, for anyone who may change the page |
| **Publish** | Saves the blocks to the page: checked like the form, written to the [activity log](history.md), kept as a version. The draft is done with. The first problem is shown with its place and the node is selected |
| **Discard changes** | Removes the draft and the unsaved changes: back to the published page |
| **Versions** | The earlier versions of the page in which the blocks changed. **Open in the editor** puts one into the editor; the site changes when you publish it. The newest one is marked **published now** while the page still holds it. Needs [versions](history.md#versions) to be kept |

The sign and words next to the title say where you are: unsaved changes, a draft that is not published, or published. Leaving with unsaved changes asks first.

A page has one draft of its blocks, shared by those who edit it. When someone else saved or discarded it after you opened the page, or the published page changed since your editing started (a restored version, the [API](configuration.md)), the editor asks before it replaces anything: load the newer page, or continue anyway.

The form of a page keeps saving every other field, the **Active** switch too, and leaves the blocks to the editor. Publishing from the editor does not switch a page on.

A page with at least one section is shown from its blocks; the **Text** field is kept but not shown. Delete every section and the text is shown again. On a screen narrower than 40rem the columns stand one under another.

With several content languages the language switch also switches the texts of the blocks. A text that is not written in a language is shown in the default one.

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

Publishing, saving the form, the API and restoring a version check the whole tree: its shape, and the settings of every block against the validation rules of the type's fields. A setting the type does not have is refused. For a translatable setting the default language must satisfy the rules; the others may be empty. The first problem is reported with its place: `Section 1, column 2, block 1 (Button): The Link field is required.`

A draft is checked only for its shape and the settings of sections and columns: the settings of its blocks may be unfinished until it is published.

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

Such a record gets the page editor with no more code: its edit form leads to `/admin/{module}/{id}/builder/{field}`. For a preview there, the model says how one of its records looks on the site by implementing `Atrium\Admin\Contracts\Previewable`:

```php
use Atrium\Admin\Builder\Layout;
use Atrium\Admin\Contracts\Previewable;
use Illuminate\Contracts\Support\Renderable;

class Landing extends Model implements Administrable, Previewable
{
    public function adminPreview(string $language): Renderable
    {
        $content = app(LayoutRenderer::class)->render(Layout::sections($this->layout));

        return view('landing', ['landing' => $this, 'content' => $content]);
    }
}
```

The application's locale is `$language` by then, and the record already holds the tree of the editor, which may be unsaved, so draw from its attributes and save nothing. The site's own page should be drawn by the same code. Without `Previewable` the editor shows the structure and the settings only.

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

The editor takes a tree and sends back the whole tree. Everything it may rely on is the format above; the screens (`Pages/Admin/Builder/Editor.vue` and `Components/Admin/Builder`) can be replaced without touching the stored pages or the block types.

It talks to the panel with these routes, all under `/admin/{module}/{id}/builder/{field}` and all asking for `{module}.update`. A module whose records are only read (`isEditableInAdmin()` is false) answers 403; a field that is disabled, read-only or not on the edit form answers 404:

| Route | What it does |
|---|---|
| `GET` | The editor |
| `POST /preview` | The HTML of the record with the tree sent (`Previewable` only) |
| `PUT /draft`, `DELETE /draft` | Saves or removes the draft |
| `POST /publish` | Saves the field through the module's form |
| `GET /versions/{version}` | The tree of an earlier version |

A draft is a row of `admin_revisions` marked with the name of the field. It is not a version: the history, the API and the count of versions kept leave it out, and it goes with the record. It is kept for modules that keep no versions too.
