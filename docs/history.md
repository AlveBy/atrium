# Record history

[English](history.md) · [Русский](history.ru.md)

Every module of the panel keeps a history of its records with no code of its own: who created, changed or deleted a record, when, from where, and which fields went from what to what; the versions of each record, to compare two of them and put an older one back; a trash, from which a deleted record is restored; and a plan of when a record switches itself on and off.

- [The activity log](#the-activity-log)
- [What is written, and from where](#what-is-written-and-from-where)
- [Versions](#versions)
- [Trash](#trash)
- [Scheduled publishing](#scheduled-publishing)
- [Passwords and other secrets](#passwords-and-other-secrets)
- [Who sees what](#who-sees-what)
- [Turning it off](#turning-it-off)
- [For module authors](#for-module-authors)

## The activity log

**Activity log** in the menu (`/admin/activity`) lists the changes of every module, newest first, 50 to a page. Each entry says what happened (created, changed, deleted, restored, deleted for good), to which record of which module, by whom, from where (in the panel, through the API, by an import, by a command, by the scheduler) and when, and has a table of the fields that changed: the field's name from the form, what it was and what it is now. The old value is struck through and the new one is not, and the columns are called **Was** and **Now**, so the two never differ by colour alone. A link leads to the record while it is still there.

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
| **Publish at** or **Unpublish at** coming | by the scheduler | whoever planned it |

A new record lists every value it was given, the defaults of its model included. A changed one lists only the fields that differ: a save that changes nothing leaves no entry, and an empty text and no value count as the same. Translations are compared language by language, relation fields by the ids chosen, custom fields and SEO fields as the form shows them. Read-only fields of a form are not compared. A deleted record is written with its title, so the entry still says what it was.

The entry is written in the same database transaction as the record: a save that fails leaves no entry, an entry is never lost for a save that went through.

A module's own button (`RecordAction`, `BulkAction`) may change records in any way it likes; whatever it saves through Eloquent while it runs is logged field by field, against the values that were in the database.

## Versions

Every save keeps a version of the record: what its form held after it. The **History** of a record lists them above its entries, newest first, each with when it was saved, by whom and from where; the first is the record as it is now. The first save of a record that was there before the history began keeps it as it was too, as **Before the history began**.

**Compare** shows two versions side by side, field by field, as an entry of the log shows a change; the two chosen stay in the address. **Restore** on an older version, after a confirmation, puts its values back. It saves through the module's form as an import does: the same validation, the same hooks, an entry in the log and a version of its own, so what the record held before stays in its history and the restore can be undone the same way. A version the form no longer takes (a field became required, a value is no longer allowed) is not put back, and the message says why.

The newest 50 versions of each record are kept, the older go; the number is set with `ATRIUM_HISTORY_REVISIONS` (0 keeps none). A version holds no password, key or token (see below). A record deleted for good takes its versions along.

**Restore** needs the right to change the record (`{module}.update`); looking at and comparing versions needs the right to view it. The API has the same: `GET /api/v1/{module}/{id}/versions` lists the versions with the values of their fields, `POST /api/v1/{module}/{id}/versions/{version}/restore` puts one back and answers with the record, or 422 with what failed; MCP has the tools `list_versions` and `restore_version`.

The [page editor](site-builder.md#draft-publishing-and-earlier-versions) keeps the draft of a page beside its versions. A draft is not a version: it is not on the record yet, it is not listed here or in the API, it does not count towards the 50, and a module that keeps no versions has drafts all the same.

Administrators keep no versions: putting an old one back would get around the checks of their form (the last superadministrator, the password).

## Trash

A deleted record of a content module (pages, news, products, the sites themselves) goes to the trash rather than away: it disappears from the panel, the site, the search, the sitemap, the API and MCP, and stays in its table with everything it has: files, SEO, custom fields, links, versions, child records. Service modules (administrators, languages, form submissions, logs, custom fields and SEO settings) delete at once, as before. It is the same from the list's **Delete**, the bulk **Delete**, `DELETE /api/v1/{module}/{id}` and the MCP tool `delete_record`.

**Trash** in the menu (`/admin/trash`) lists what was deleted, newest first, 50 to a page: the record, its module, who deleted it and when, narrowed by module. **Restore** brings the record back as it was and opens its form; **Delete for good**, after a confirmation, deletes it with its files and versions (the activity log keeps what happened). Both are in the log: **Restored** and **Deleted for good**.

What has been in the trash for 30 days is deleted for good by the scheduler every night (`atrium:trash:empty`; the site's cron runs `php artisan schedule:run`). The number of days is `ATRIUM_TRASH_DAYS`; 0 keeps everything until it is deleted by hand. A record the module refuses to delete (`beforeAdminDelete()` or a listener of `atrium.record.deleting`) stays.

Restoring needs `{module}.restore` and deleting for good `{module}.force_delete`, two permissions every module with the trash gets in the role editor; the trash shows the modules the administrator may view and, with several sites, the records of the site the panel works with and of the modules all sites share. The API has the same: `GET /api/v1/{module}/trash` lists the records with `id`, `title`, `deleted_at` and `deleted_by`, `POST /api/v1/{module}/trash/{id}/restore` brings one back and answers with the record, `DELETE /api/v1/{module}/trash/{id}` deletes it for good; MCP has `list_trash`, `restore_from_trash` and `delete_forever`.

A record in the trash keeps its code and its address: a new record cannot take them until the old one is restored or deleted for good.

## Scheduled publishing

Every content module whose form has an **Active** field gets two more next to it, on the same tab: **Publish at** and **Unpublish at**. At the first time the record switches itself on, at the second it switches itself off; either may be left empty, and emptying both cancels the plan. The end must come after the start, the one already planned included. The times are kept in `admin_schedules`, not in the module's table, so a module needs no column and no migration for them. The form, `/api/v1` (`publish_at` and `unpublish_at`, ISO 8601 times or null), the MCP tools and an import plan them the same way, and an export writes them in the columns of the same names.

A planned record is marked **Scheduled** with a clock in its list, next to its **Active** column (or its name, when the list has none); the mark's tooltip, also read out by screen readers, says when it switches on and off.

The scheduler checks every minute (`atrium:publish`; the site's cron runs `php artisan schedule:run`) and switches what is due through the record's form, so everything that follows a save follows: the activity log writes it **by the scheduler** on behalf of whoever planned it, a version is kept, caches, the search and the sitemap hear of it. A time that has already passed when it is planned is applied at the next minute; when both times have come, the record ends switched off, as the end is the later one. A switch the module refuses (a filter of `atrium.record.data` throws `Refused`) is reported by the command and not tried again. A record in the trash waits until it is restored; one deleted for good takes its plan along.

**Active from** and **Active until** of a module, where it has them, are left alone: they are dates the site shows, and the record stays switched on with them.

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

For the whole site: `ATRIUM_HISTORY_ENABLED=false` in `.env`. Nothing is written, and the journal, its menu entry and the **History** buttons go away. Entries written before stay in the `admin_activity` table, versions in `admin_revisions`. Versions alone: `ATRIUM_HISTORY_REVISIONS=0`. The trash does not depend on it.

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

To delete the module's records at once, with no trash:

```php
public static function usesAdminTrash(): bool
{
    return false;
}
```

To switch the module's records by hand only, with no **Publish at** and **Unpublish at** (service modules have none already):

```php
public static function usesAdminSchedule(): bool
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

A record in the trash is hidden by a global scope (`Atrium\Admin\Scopes\TrashScope`): a query that drops every scope (`withoutGlobalScopes()`) sees it too, so drop only the one you mean, as `withoutGlobalScope(SiteScope::class)`. `beforeAdminDelete()` and `atrium.record.deleting` run both when a record goes to the trash and when it is deleted for good; `eloquent.deleted` is heard when it goes to the trash, and `eloquent.restored` and `eloquent.saved` when it comes back, so caches and indexes follow it with no code of their own.

`ActivityLog::changes($before, $after)` compares two arrays of field values the way the form does and gives what `record()` takes.
