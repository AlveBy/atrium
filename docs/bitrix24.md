# Bitrix24

[English](bitrix24.md) · [Русский](bitrix24.ru.md)

`alveby/atrium-bitrix24` connects the site to your Bitrix24 portal:

- every **form submission** becomes a **lead** (or a **contact with a deal**) in Bitrix24;
- the **chat widget** (open lines, online chat) of Bitrix24 shows on the site;
- the connection is one pasted **incoming webhook address**: no application to register, no OAuth.

It stands on the [integrations base](integrations.md): a call that fails is tried again, every attempt is a line of the exchange log, and the administrators get a letter when it gives up. Install that first.

Orders, deals and their stages coming **back** to the site need the shop module, which is not released yet; they are not part of this module (see [What waits for the shop](#what-waits-for-the-shop)).

- [Install](#install)
- [Connect Bitrix24](#connect-bitrix24)
- [Form submissions](#form-submissions)
- [Which field goes where](#which-field-goes-where)
- [The chat widget](#the-chat-widget)
- [If something does not arrive](#if-something-does-not-arrive)
- [What waits for the shop](#what-waits-for-the-shop)
- [Hooks and code](#hooks-and-code)

## Install

```bash
composer require alveby/atrium-integrations alveby/atrium-forms alveby/atrium-bitrix24
php artisan atrium:module:install alveby/atrium-integrations
php artisan atrium:module:install alveby/atrium-forms
php artisan atrium:module:install alveby/atrium-bitrix24
```

The queue must run (`php artisan queue:work`, or the supervisor/cron setup of your host) and cron must call `php artisan schedule:run` every minute. With `QUEUE_CONNECTION=sync` a submission is sent during the request and is never retried.

## Connect Bitrix24

1. In Bitrix24 open **Developer resources → Other → Incoming webhook** (on some portals: **Applications → Developer resources**).
2. Add a webhook and give it the right to **CRM (crm)**. Save.
3. Copy the address Bitrix24 shows. It looks like `https://yourcompany.bitrix24.com/rest/1/abcdef0123456789/`.
4. In Atrium open **Modules → Bitrix24**, paste it into **Incoming webhook** and save.
5. Press **Check connection**. Atrium asks the portal who the webhook belongs to and answers «Connected: yourcompany.bitrix24.com, user Ivan Petrov», or says why not.

The address is a key to your CRM, so Atrium stores it **encrypted** and never shows it again: the field shows only where it points (`https://yourcompany.bitrix24.com/rest/1/••••••••`). To change it, paste a new one; to keep it, leave the field empty. The address is also hidden in the exchange log and in alert letters.

Do not paste a webhook into a chat or a ticket. If one has leaked, delete it in Bitrix24 and make a new one.

## Form submissions

**Send form submissions to Bitrix24** in the module's settings turns sending on or off for the whole site. It is on by default and does nothing until a webhook is set.

By default **every form** of the [Forms module](forms.md) goes to Bitrix24 as a **lead**. The submission is stored first, then sent from the queue: a portal that is down loses nothing, the send is tried again after 10 seconds, a minute, five minutes and a quarter of an hour.

To treat a form differently, open **Bitrix24: forms** in the menu and add a link:

| Setting | Meaning |
|---|---|
| Form | The form this line is about; one line per form |
| Send as | **Lead**, or **Deal with a contact**: a contact is made first and the deal points to it |
| Send this form to Bitrix24 | Off keeps this form out of Bitrix24 while the others go |
| Fields | Which form field goes to which Bitrix24 field (below) |

A lead gets the title «Website: form name», the source «Web» and the page the visitor sent it from. A deal takes the same title; the contact takes the name, phone, e-mail and address fields.

## Which field goes where

Without any setting Atrium places the fields by their **type**: an e-mail field to `EMAIL`, a phone field to `PHONE`, and a field named `name` (also `first_name`, `fio`, `full_name`) to `NAME` or `last_name`/`surname` to `LAST_NAME`. Everything else is written into the lead's **comment**, each as «Label: value», so nothing a visitor typed is lost.

To place a field yourself, write one line per field in **Fields** of the form's link, «form field = Bitrix24 field»:

```
phone = PHONE
message = COMMENTS
city = ADDRESS_CITY
budget = OPPORTUNITY
promo = UF_CRM_1700000000
# a line that starts with # is a note
```

- The left side is the field's name in the form, the right side is the code of a Bitrix24 field, capitals or not. Custom fields of your portal are `UF_CRM_…`.
- Phones, e-mails, sites (`WEB`) and messengers (`IM`) are lists in Bitrix24: two form fields sent to `PHONE` make two phones.
- Two form fields sent to another field are joined, one under the other.
- `TITLE` and `SOURCE_ID` can be set the same way when the defaults do not suit you.
- A line that is not «field = CODE» is refused when you save, with its number.

In **deal** mode the contact takes `NAME`, `LAST_NAME`, `SECOND_NAME`, `PHONE`, `EMAIL`, `WEB`, `ADDRESS` and `POST`, and the deal takes the rest. A visitor who left no name becomes the contact «Website visitor». A send that fails after the contact was made does not make a second contact on the next attempt.

## The chat widget

In Bitrix24 open **Contact center → Online chat** (or an open line), choose **Widget** and copy the code it gives. Paste it into **Chat widget code** in **Modules → Bitrix24**. It is added at the end of every page of the site, before `</body>`, without editing a theme. Empty means no widget. The admin never gets it.

The code is yours and is put on the page as it is; paste only what Bitrix24 gave you.

## If something does not arrive

Open **Exchange log** (Modules → Integrations) and filter by `bitrix24`. Every send is a line: done («Lead #15 created»), failed and will retry, or failed for good, with the reason. The usual ones:

| The log says | What to do |
|---|---|
| The Bitrix24 webhook is not set | Paste it in **Modules → Bitrix24** |
| Bitrix24 does not answer | The portal address in the webhook is wrong, or the portal is down; a retry may help |
| Bitrix24 refused: `INVALID_CREDENTIALS` | The webhook was deleted or is wrong; make a new one |
| Bitrix24 refused: `insufficient_scope` | The webhook has no right to CRM; add it |
| Bitrix24 refused: `QUERY_LIMIT_EXCEEDED` | Too many calls at once; it is tried again by itself |

Nothing in the log at all means the send did not start: sending is switched off, no webhook is set, the form's link is switched off, or the queue is not running.

## What waits for the shop

The shop module is not released yet, so these parts of the Bitrix24 plan are **not here**: orders as deals, customers as contacts, and the deal stages (paid, shipped) coming back to the site. They will be added to this module when the shop is released.

## Hooks and code

A submission is sent by `Atrium\Bitrix24\Jobs\SendSubmission::dispatch($submissionId)->afterCommit()`; you may call it for a submission of your own. To call Bitrix24 yourself with the saved webhook:

```php
$result = app(Atrium\Bitrix24\Support\Client::class)->call('crm.lead.add', ['fields' => ['TITLE' => 'Hello']]);
```

It returns the `result` of the portal's answer and throws `Atrium\Bitrix24\Support\Bitrix24Exception` with a readable message (never with the webhook in it) when there is no webhook, the portal does not answer or refuses.
