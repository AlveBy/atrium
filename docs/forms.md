# Forms

[English](forms.md) · [Русский](forms.ru.md)

The forms module (`alveby/atrium-forms`) lets editors build forms in the admin and keeps what visitors send: a contact form, a call-back request, a booking. Each submission is stored and mailed to the addresses set on the form. Spam is stopped without an outside service.

## Install

```bash
composer require alveby/atrium-forms
php artisan atrium:module:install alveby/atrium-forms
```

## Building a form

**Forms** has one record per form: its name, its code (what a page asks for), the addresses submissions go to, the button text and the message after sending (both per content language), and the fields.

The fields are built on the same page. Pick a type to add one (text, e-mail, phone, number, date, long text, list, radio buttons, checkbox), drag it into place or move it with the arrows, and open it to set the label, the code, whether it is required, the options of a list, the text in an empty field, a hint, the least and the most, and its width: from the whole row down to 1 of 12 columns. Fields that fit side by side share a row on a computer and stand one under another on a phone; the preview beside the builder shows both.

**Submissions** lists what was sent, with the form, the page, the language, the IP address and the browser.

## On a page

```blade
<x-atrium-forms::form code="contact" />
```

draws the form in the page's language, or nothing when there is no such form or it is off. The form posts to `/forms/{code}` and comes back to the page with the message or the errors.

Two attributes change how it works:

| Attribute | What it does |
|---|---|
| `ajax` | Sent without reloading the page: the errors appear under their fields, the message after sending is read out to screen readers, a double click sends once. |
| `modal` | A button that opens the form in a window. `trigger` is the button's text, `title` the window's heading; both are the form's name by default. The focus stays inside the window; Escape and the close button shut it and give the focus back to the button. Without JavaScript the button is a link to the form's own page, `/forms/{code}`, kept out of search engines. |

```blade
<x-atrium-forms::form code="contact" ajax />
<x-atrium-forms::form code="contact" modal trigger="Write to us" title="Ask a question" />
```

The same in one line:

```blade
@atriumForm('contact')
@atriumForm('contact', ['modal' => true, 'trigger' => 'Write to us', 'class' => 'wide'])
```

`ajax`, `modal`, `trigger` and `title` are options; everything else goes to the `<form>` tag as attributes.

Both bring the module's script, `/forms/script.js`: no dependencies, 8 KB (under 3 KB compressed), cached for a year by its version, added once however many forms a page has.

## Your own markup

The form's page in the admin, at the bottom, has the snippets below already filled in with that form's code and fields, in the site's default language. These are for a `contact` form with a name and an e-mail.

Markup for a Blade template of the site, the look left to you:

```blade
<form id="form-contact" method="post" action="{{ route('atrium.forms.submit', 'contact') }}">
    @csrf
    <input type="hidden" name="_t" value="{{ \Atrium\Forms\Support\SpamGuard::token() }}">
    <div hidden><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
    <p role="status" data-atrium-sent></p>
    <p role="alert" data-atrium-alert></p>

    <div data-field="name">
        <label for="form-contact-name">Your name</label>
        <input type="text" id="form-contact-name" name="fields[name]" required>
    </div>

    <div data-field="email">
        <label for="form-contact-email">E-mail</label>
        <input type="email" id="form-contact-email" name="fields[email]" required>
    </div>

    <button type="submit">Send</button>
</form>
```

The hidden fields carry the CSRF token, the time the form was drawn (a form sent sooner than `min_seconds` after it is refused) and the trap field bots fill in. Keep them; the site's captcha, when one is on, adds `<x-atrium::captcha data-error-for="captcha" />` before the button.

To send it without reloading the page, add `data-atrium-form` to the `<form>` and put the script on the page once:

```blade
<script src="{{ route('atrium.forms.script', ['v' => \Atrium\Forms\Http\Controllers\ScriptController::version()]) }}" defer></script>
```

The script puts each error under the field whose `data-field` it names, the captcha's under `data-error-for="captcha"`, the message after sending in `[data-atrium-sent]` and other errors in `[data-atrium-alert]`.

## Sending it from your own script

The same markup, without `data-atrium-form`, sent with `fetch`:

```js
const form = document.getElementById('form-contact');

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const body = new FormData(form);
    const xsrf = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    // A page kept in a cache has an old token: the cookie has the fresh one.
    if (xsrf) {
        body.delete('_token');
    }

    const response = await fetch(form.action, {
        method: 'POST',
        headers: { Accept: 'application/json', ...(xsrf && { 'X-XSRF-TOKEN': decodeURIComponent(xsrf[1]) }) },
        body,
    });
    const data = await response.json();
    const errors = Object.values(data.errors ?? {}).flat();

    form.querySelector('[data-atrium-sent]').textContent = response.ok ? data.message : '';
    form.querySelector('[data-atrium-alert]').textContent = response.ok ? '' : errors.join(' ') || data.message;

    if (response.ok) {
        form.reset();
    }
});
```

With a captcha on, every answer uses it up and brings a new one; the snippet on the form's page then adds

```js
form.querySelector('.atrium-captcha').innerHTML = data.captcha;
```

## The answers

A request with `Accept: application/json` gets JSON:

```text
200 OK
{
    "message": "Thank you! Your message has been sent."
}

422 Unprocessable Content
{
    "message": "The your name field is required.",
    "errors": {
        "fields.name": [
            "The your name field is required."
        ]
    }
}

429 Too Many Requests
{
    "message": "Too many attempts. Please try again in a minute."
}
```

- `errors` is keyed by `fields.{code}` for a field, `captcha` for the captcha and `form` for the form as a whole ("sent too quickly").
- `message` of a 422 is the first error.
- With a captcha on, `200` and `422` also carry `"captcha"`: the HTML of a new one, to put in place of the used one.
- A filled-in trap field gets the same `200` as a real submission, and nothing is stored.

## Events

The module's script tells the page how it went, on the form, bubbling:

```js
document.addEventListener('atrium-form:sent', (event) => {
    // event.target is the form, event.detail.message the message shown
});

document.addEventListener('atrium-form:failed', (event) => {
    // event.detail.status: 422, 429 or 0 without a connection; event.detail.errors
});
```

`window.AtriumForms.open('contact')` opens a form's window from your own code, `window.AtriumForms.submit(form)` sends a form.

## Spam and limits

- A trap field people never see. A submission with it filled is answered as sent and dropped.
- The time the form was drawn, encrypted with the app key. Sooner than `min_seconds` (3), or without it: refused.
- At most `per_minute` (5) submissions of one form from one IP address a minute; then `429`.
- The site's captcha (`ATRIUM_CAPTCHA`: `image` built in, `recaptcha`), on every form unless `captcha => false` in `config/atrium-forms.php`.

`php artisan vendor:publish --tag=atrium-forms-config` copies the config; `ATRIUM_FORMS_MIN_SECONDS` and `ATRIUM_FORMS_PER_MINUTE` work without it. To change the component's markup, `php artisan vendor:publish --tag=atrium-forms-views`.

## Mail and your own handling

Each submission is mailed to every address of its form through Laravel's mail (`MAIL_*` in `.env`). A mail server that is down does not lose a submission: it is stored first. To send it somewhere else as well, listen to the model's event:

```php
use Atrium\Forms\Models\FormSubmission;

FormSubmission::created(function (FormSubmission $submission) {
    // $submission->form_name, $submission->data: [['name' => 'email', 'type' => 'email', 'label' => 'E-mail', 'value' => '…'], …]
});
```
