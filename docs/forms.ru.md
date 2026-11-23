# Формы

[English](forms.md) · [Русский](forms.ru.md)

Модуль форм (`alveby/atrium-forms`) даёт редакторам собирать формы в админке и хранит то, что присылают посетители: обратную связь, заказ звонка, бронирование. Каждая заявка сохраняется и уходит письмом на адреса, указанные в форме. Спам отсекается без внешних сервисов.

## Установка

```bash
composer require alveby/atrium-forms
php artisan atrium:module:install alveby/atrium-forms
```

## Как собрать форму

В разделе **Формы** одна запись на форму: название, код (по нему форму просит страница), адреса для заявок, текст кнопки и сообщение после отправки (оба на каждом языке контента) и поля.

Поля собираются на той же странице. Выберите тип, чтобы добавить поле (текст, e-mail, телефон, число, дата, длинный текст, список, переключатели, флажок), перетащите его на место или сдвиньте стрелками и откройте, чтобы задать подпись, код, обязательность, варианты списка, текст в пустом поле, подсказку, наименьшее и наибольшее значение и ширину: от всей строки до 1 из 12 колонок. Поля, которые помещаются рядом, на компьютере стоят в одной строке, а на телефоне одно под другим; предпросмотр рядом с конструктором показывает оба вида.

В разделе **Заявки** то, что прислали, с формой, страницей, языком, IP-адресом и браузером.

## На странице

```blade
<x-atrium-forms::form code="contact" />
```

выводит форму на языке страницы или ничего, если такой формы нет или она выключена. Форма отправляется на `/forms/{code}` и возвращается на страницу с сообщением или ошибками.

Два атрибута меняют поведение:

| Атрибут | Что делает |
|---|---|
| `ajax` | Отправка без перезагрузки страницы: ошибки появляются под своими полями, сообщение после отправки зачитывается экранным диктором, двойной клик отправляет один раз. |
| `modal` | Кнопка, которая открывает форму в окне. `trigger` — текст кнопки, `title` — заголовок окна; по умолчанию оба равны названию формы. Фокус не выходит из окна; Escape и кнопка закрытия закрывают его и возвращают фокус на кнопку. Без JavaScript кнопка — это ссылка на собственную страницу формы, `/forms/{code}`, закрытую от поисковиков. |

```blade
<x-atrium-forms::form code="contact" ajax />
<x-atrium-forms::form code="contact" modal trigger="Написать нам" title="Задать вопрос" />
```

То же одной строкой:

```blade
@atriumForm('contact')
@atriumForm('contact', ['modal' => true, 'trigger' => 'Написать нам', 'class' => 'wide'])
```

`ajax`, `modal`, `trigger` и `title` — настройки; всё остальное уходит атрибутами в тег `<form>`.

Оба варианта подключают скрипт модуля, `/forms/script.js`: без зависимостей, 8 КБ (меньше 3 КБ в сжатом виде), кешируется на год по версии и подключается один раз, сколько бы форм ни было на странице.

## Своя вёрстка

Внизу страницы формы в админке лежат фрагменты ниже, уже заполненные кодом и полями этой формы, на основном языке сайта. Здесь они для формы `contact` с именем и e-mail.

Вёрстка для Blade-шаблона сайта, внешний вид на ваше усмотрение:

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

В скрытых полях CSRF-токен, время вывода формы (форму, отправленную быстрее `min_seconds`, не примут) и поле-ловушка, которое заполняют боты. Их нужно оставить; если на сайте включена капча, перед кнопкой добавится `<x-atrium::captcha data-error-for="captcha" />`.

Чтобы форма отправлялась без перезагрузки страницы, добавьте `data-atrium-form` в `<form>` и подключите скрипт на странице один раз:

```blade
<script src="{{ route('atrium.forms.script', ['v' => \Atrium\Forms\Http\Controllers\ScriptController::version()]) }}" defer></script>
```

Скрипт ставит каждую ошибку под поле, чей `data-field` она называет, ошибку капчи — под `data-error-for="captcha"`, сообщение после отправки — в `[data-atrium-sent]`, остальные ошибки — в `[data-atrium-alert]`.

## Отправка своим скриптом

Та же вёрстка, без `data-atrium-form`, отправка через `fetch`:

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

Если включена капча, каждый ответ её расходует и приносит новую; тогда фрагмент на странице формы добавляет

```js
form.querySelector('.atrium-captcha').innerHTML = data.captcha;
```

## Ответы сервера

Запрос с `Accept: application/json` получает JSON:

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

- В `errors` ключи `fields.{код}` для поля, `captcha` для капчи и `form` для формы целиком («отправлена слишком быстро»).
- `message` в ответе 422 — первая ошибка.
- Если включена капча, в `200` и `422` есть ещё `"captcha"`: HTML новой капчи вместо использованной.
- Заполненное поле-ловушка получает тот же `200`, что и настоящая заявка, но ничего не сохраняется.

## События

Скрипт модуля сообщает странице, чем кончилось, событием на форме (всплывает):

```js
document.addEventListener('atrium-form:sent', (event) => {
    // event.target — форма, event.detail.message — показанное сообщение
});

document.addEventListener('atrium-form:failed', (event) => {
    // event.detail.status: 422, 429 или 0 без связи; event.detail.errors
});
```

`window.AtriumForms.open('contact')` открывает окно формы из вашего кода, `window.AtriumForms.submit(form)` отправляет форму.

## Спам и ограничения

- Поле-ловушка, которого люди не видят. Заявку с заполненной ловушкой отвечают «отправлено» и выбрасывают.
- Время вывода формы, зашифрованное ключом приложения. Быстрее `min_seconds` (3) или без него — отказ.
- Не больше `per_minute` (5) заявок одной формы с одного IP-адреса в минуту; дальше `429`.
- Капча сайта (`ATRIUM_CAPTCHA`: встроенная `image`, `recaptcha`) на каждой форме, если в `config/atrium-forms.php` не стоит `captcha => false`.

`php artisan vendor:publish --tag=atrium-forms-config` копирует настройки; `ATRIUM_FORMS_MIN_SECONDS` и `ATRIUM_FORMS_PER_MINUTE` работают и без этого. Чтобы поменять вёрстку компонента — `php artisan vendor:publish --tag=atrium-forms-views`.

## Письма и своя обработка

Каждая заявка уходит письмом на все адреса своей формы через почту Laravel (`MAIL_*` в `.env`). Если почтовый сервер лежит, заявка не теряется: она сохраняется первой. Чтобы отправить её ещё куда-то, слушайте событие модели:

```php
use Atrium\Forms\Models\FormSubmission;

FormSubmission::created(function (FormSubmission $submission) {
    // $submission->form_name, $submission->data: [['name' => 'email', 'type' => 'email', 'label' => 'E-mail', 'value' => '…'], …]
});
```
