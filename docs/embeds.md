# Embeds: a form or a block inside a text

[Русский](embeds.ru.md)

An embed is something an editor puts into the text of a page and the site draws in its place: a contact form, a block with the phone number, later a list of products. The text keeps only a placeholder, so what the visitor sees is always the current form or block, even when the page itself is cached.

## What an editor does

In every visual editor (the content of a page, the text of a news item, a product description, a repeatable text) the toolbar has a **+** button, "Insert form or block".

1. Put the caret where the embed should go.
2. Click the button and choose a type: **Form**, **Block** (with [the blocks module](blocks.md)) and so on. Only the types of installed modules, and only those the editor may see, are offered.
3. Type a part of the name to narrow the list and click the item.

The editor shows a labelled chip, `Form: Contact us`, in the text. It moves, copies and deletes like a picture; the Delete key removes it. Save the form as usual. If the form or block is later switched off or deleted, the page simply shows nothing in its place: no error, no empty box.

In the **HTML** mode of a content field the same thing is one line, and typing it by hand works as well:

```html
<div data-atrium-embed="form" data-id="3"></div>
```

## What the site does

The theme draws every text of a page, a section, a product and so on through the `@embeds` Blade directive, which swaps each placeholder for the item's HTML on every request. A template of your own does the same:

```blade
@embeds($page->detail_text)
```

instead of `{!! $page->detail_text !!}`. The directive leaves text without placeholders untouched (one string search), accepts a string, `null` or an `Htmlable`, and a type that is not installed, an id that points at nothing or an embed that throws an error gives an empty string; the error is reported to the log and the rest of the page is drawn.

## Registering your own type

A module adds a type once, when it boots:

```php
use Atrium\Admin\Embeds\Embeds;
use Atrium\Admin\Hooks\Hooks;

$this->app->make(Hooks::class)->register(Embeds::REGISTRY, 'banner', new BannerEmbed);
```

The key (`banner`) goes into the placeholder, so keep it stable. `BannerEmbed` implements `Atrium\Admin\Embeds\Embed`:

| Method | What it does |
|---|---|
| `label()` | The name in the **Insert** list, in the admin's language. |
| `available()` | `false` when the module behind it is not installed: the type is then neither offered nor drawn. |
| `permitted(?AdminUser)` | Whether this administrator may pick items of the type. The picker answers 403 otherwise. |
| `options(string $search)` | The items for a few typed words, as `[['id' => '3', 'label' => 'Contact us']]`. Cap them; the picker has no paging. |
| `labels(array $ids)` | The names of items by id, for the chip in the editor. |
| `render(string $id)` | The HTML on the site. An id that points at nothing or an item that is off gives `''`. |

For a record of an Eloquent model, extend `Atrium\Admin\Embeds\ModelEmbed` and write two methods, the model and how one record looks on the site; the list, the search, the permission (the module's `view`), the "installed" check and the empty answer for a missing record come with it. This is the whole of the forms embed:

```php
final class FormEmbed extends ModelEmbed
{
    public function label(): string
    {
        return __('atrium-forms::forms.fields.form');
    }

    protected function model(): string
    {
        return Form::class;
    }

    protected function renderRecord(Model $record): string
    {
        return FormComponent::inline((string) $record->getAttribute('code'), ['ajax' => true]);
    }
}
```

The picker is served at `GET /admin/embeds/{type}?q=…` (and `?ids=1,2` for the names) and its list of types is the Inertia prop `embeds`, so a module needs no routes or JavaScript of its own.

## Good to know

- The placeholder is plain HTML, so it survives export, import, the API and a Bitrix or WordPress migration like any other text. The editor stores it as `<div data-atrium-embed="form" data-id="3"></div>`; spelling with single quotes or another case is read too.
- Texts are cached with the placeholder in them, not with the form: a form always carries a fresh CSRF and spam-protection token.
- `Embeds::render()` is the one entry point; call it from PHP when you build HTML yourself.
