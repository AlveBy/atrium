# Record history

[English](history.md) · [Русский](history.ru.md)

Every module of the panel keeps a history of its records with no code of its own: who created, changed or deleted a record, when, from where, and which fields went from what to what; and the versions of each record, to compare two of them and put an older one back.

- [The activity log](#the-activity-log)
- [What is written, and from where](#what-is-written-and-from-where)
- [Versions](#versions)
- [Passwords and other secrets](#passwords-and-other-secrets)
- [Who sees what](#who-sees-what)
- [Turning it off](#turning-it-off)
- [For module authors](#for-module-authors)

## The activity log

**Activity log** in the menu (`/admin/activity`) lists the changes of every module, newest first, 50 to a page. Each entry says what happened (created, changed, deleted, restored), to which record of which module, by whom, from where (in the panel, through the API, by an import, by a command, by the scheduler) and when, and has a table of the fields that changed: the field's name from the form, what it was and what it is now. The old value is struck through and the new one is not, and the columns are called **Was** and **Now**, so the two never differ by colour alone. A link leads to the record while it is still there.

Above the list the journal is narrowed by module, by administrator and by days (**From** and **To**, both days included). The filters stay in the address, so a narrowed journal can be bookmarked or sent.

The form of every record has a **History** button: the same entries for that one record.

## What is written, and from where

Every save goes through one place, so an entry is written whatever made the change:

| Where the change came from | Source in the entry | Who |
|---|---|---|
| The record form in the panel | in the panel | the administrator signed in |
| `/api/v1` and the MCP tools | via API | the owner of the token |
| An import of a CSV or Excel file | by import | whoever started the import |
| A module's button on a record, a bulk action | in the panel / via API | whoever pressed it, also when it runs on the queue |
| A console command, a queue job of its own | by a command | nobody ("System") |

A new record lists every value it was given, the defaults of its model included. A changed one lists only the fields that differ: a save that changes nothing leaves no entry, and an empty text and no value count as the same. Translations are compared language by language, relation fields by the ids chosen, custom fields and SEO fields as the form shows them. Read-only fields of a form are not compared. A deleted record is written with its title, so the entry still says what it was.

The entry is written in the same database transaction as the record: a save that fails leaves no entry, an entry is never lost for a save that went through.

A module's own button (`RecordAction`, `BulkAction`) may change records in any way it likes; whatever it saves through Eloquent while it runs is logged field by field, against the values that were in the database.

## Versions

Every save keeps a version of the record: what its form held after it. The **History** of a record lists them above its entries, newest first, each with when it was saved, by whom and from where; the first is the record as it is now. The first save of a record that was there before the history began keeps it as it was too, as **Before the history began**.

**Compare** shows two versions side by side, field by field, as an entry of the log shows a change; the two chosen stay in the address. **Restore** on an older version, after a confirmation, puts its values back. It saves through the module's form as an import does: the same validation, the same hooks, an entry in the log and a version of its own, so what the record held before stays in its history and the restore can be undone the same way. A version the form no longer takes (a field became required, a value is no longer allowed) is not put back, and the message says why.

The newest 50 versions of each record are kept, the older go; the number is set with `ATRIUM_HISTORY_REVISIONS` (0 keeps none). A version holds no password, key or token (see below). A record deleted for good takes its versions along.

**Restore** needs the right to change the record (`{module}.update`); looking at and comparing versions needs the right to view it. The API has the same: `GET /api/v1/{module}/{id}/versions` lists the versions with the values of their fields, `POST /api/v1/{module}/{id}/versions/{version}/restore` puts one back and answers with the record, or 422 with what failed; MCP has the tools `list_versions` and `restore_version`.

Administrators keep no versions: putting an old one back would get around the checks of their form (the last superadministrator, the password).

## Passwords and other secrets

A password, a key or a token is never written to the log, not even hashed. When one is given a new value, the entry says that the field changed, without the value. A field counts as secret when it is:

- a password input (`TextField::make('password')->password()`);
- an attribute the model hides (`$hidden`, as `password` and `remember_token` of administrators);
- a field whose name contains one of `atrium.history.secrets`, in any case: `password`, `secret`, `token`, `api_key`, `private_key` by default.

Add your own words to the list in `config/atrium.php`:

```php
'history' => [
    'enabled' => env('ATRIUM_HISTORY_ENABLED', true),
    'revisions' => env('ATRIUM_HISTORY_REVISIONS', 50),
    'secrets' => ['password', 'secret', 'token', 'api_key', 'private_key', 'passport'],
],
```

## Who sees what

The journal shows the entries of the modules the administrator may view (`{module}.view`) and nothing else; the module filter lists only those. A record's **History** button needs the same permission as its form. With several sites, the journal shows what was done in the site the panel works with and in the modules all sites share; the history of a record of another site is not found.

## Turning it off

For the whole site: `ATRIUM_HISTORY_ENABLED=false` in `.env`. Nothing is written, and the journal, its menu entry and the **History** buttons go away. Entries written before stay in the `admin_activity` table, versions in `admin_revisions`. Versions alone: `ATRIUM_HISTORY_REVISIONS=0`.

For one module, in its model:

```php
public static function hasAdminHistory(): bool
{
    return false;
}
```

The module's records are not logged and its form has no **History** button. To keep the log and no versions:

```php
public static function hasAdminRevisions(): bool
{
    return false;
}
```

## For module authors

Saving through the form, the API, MCP or an import needs nothing: the entry is written by the core. A module that writes records in its own way, outside a request of the panel, can do it explicitly:

```php
use Atrium\Admin\History\ActivityLog;

$log = app(ActivityLog::class);

// Everything saved inside is logged field by field, as by a module's button.
$log->watch(fn () => $order->update(['status' => 'paid']));

// Names the source and the administrator of what is written inside.
$log->during(ActivityLog::SCHEDULE, null, fn () => $this->publishDue());

// One entry by hand.
$log->record($order, ActivityLog::UPDATED, ['status' => ['old' => 'new', 'new' => 'paid']]);
```

`ActivityLog::changes($before, $after)` compares two arrays of field values the way the form does and gives what `record()` takes.
