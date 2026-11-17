# Backups

[English](backup.md) · [Русский](backup.ru.md)

`alveby/atrium-backup` copies the site's databases and files to one or more places on a schedule, keeps a sensible number of old copies, e-mails when something is wrong, and puts a copy back from the admin.

- [Install](#install)
- [What goes into an archive](#what-goes-into-an-archive)
- [Where archives go](#where-archives-go)
- [Schedule and retention](#schedule-and-retention)
- [Health checks and e-mail](#health-checks-and-e-mail)
- [The admin screen](#the-admin-screen)
- [Restoring](#restoring)
- [Commands](#commands)
- [What it adds to spatie/laravel-backup](#what-it-adds-to-spatielaravel-backup)
- [Testing it](#testing-it)

## Install

```bash
composer require alveby/atrium-backup
php artisan atrium:module:install alveby/atrium-backup
```

The schedule needs Laravel's scheduler in cron, once a minute:

```bash
* * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
```

The dump tools must be on the server: `mysqldump` for MySQL and MariaDB, `pg_dump` for PostgreSQL (its version at least the server's), `sqlite3` for SQLite. Restoring uses `mysql`, `psql` and `sqlite3`.

Everything is set in `.env`, or in `config/atrium-backup.php` after `php artisan vendor:publish --tag=atrium-backup-config`. There is no `config/backup.php` to keep in step: the module builds spatie's settings from its own.

## What goes into an archive

| Setting | Default | What it does |
|---|---|---|
| `ATRIUM_BACKUP_DATABASES` | the default connection | Connections to dump, comma separated: `mysql,analytics` |
| `ATRIUM_BACKUP_DISKS` | `uploads` | Laravel disks whose files go in. `uploads` is the disk the admin uploads to. Only disks on this server can be read: a bucket keeps its own copies |
| `ATRIUM_BACKUP_FOLDERS` | none | More folders, absolute or from the project: `public/upload` |
| `ATRIUM_BACKUP_PASSWORD` | none | Encrypts the archive (AES-256 zip). Without the password nobody can open it, the admin included: keep it somewhere other than the server |

Dumps are consistent: MySQL and MariaDB are dumped with `--single-transaction` unless the connection's `dump` settings say otherwise, `pg_dump` and the SQLite dump take a snapshot by themselves. Archives are zip files compressed at level 6, checked after writing.

## Where archives go

Each name in `ATRIUM_BACKUP_DESTINATIONS` is a Laravel disk; the archive is copied to all of them, and one unreachable place does not stop the copy to the others. The module defines these disks unless `config/filesystems.php` already has one of the same name:

| Disk | Needs | `.env` |
|---|---|---|
| `backups` | nothing: `storage/app/backups` on this server | |
| `backup-s3` | `league/flysystem-aws-s3-v3` | `ATRIUM_BACKUP_S3_KEY`, `_SECRET`, `_REGION`, `_BUCKET`, `_ENDPOINT` (for S3-compatible storage), `_PATH_STYLE`, `_ROOT` |
| `backup-ftp` | `league/flysystem-ftp` | `ATRIUM_BACKUP_FTP_HOST`, `_USERNAME`, `_PASSWORD`, `_PORT`, `_ROOT`, `_SSL` (on by default) |
| `backup-sftp` | `league/flysystem-sftp-v3` | `ATRIUM_BACKUP_SFTP_HOST`, `_USERNAME`, `_PASSWORD` or `_PRIVATE_KEY` and `_PASSPHRASE`, `_PORT`, `_ROOT` |
| `backup-google` | `masbug/flysystem-google-drive-ext` | `ATRIUM_BACKUP_GOOGLE_CLIENT_ID`, `_CLIENT_SECRET`, `_REFRESH_TOKEN`, `_FOLDER` |
| `backup-dropbox` | `spatie/flysystem-dropbox` | `ATRIUM_BACKUP_DROPBOX_APP_KEY`, `_APP_SECRET`, `_REFRESH_TOKEN` (or a short-lived `_TOKEN` for a quick try), `_ROOT` |

For example, a copy on the server and one in an S3 bucket:

```bash
ATRIUM_BACKUP_DESTINATIONS=backups,backup-s3
ATRIUM_BACKUP_S3_BUCKET=site-backups
```

Install the adapter a disk needs with Composer; until then that disk fails with a message naming the package and the others work. Accounts, keys and tokens are the site owner's: create them in the provider's console and put them in `.env`.

Dropbox: `spatie/flysystem-dropbox` does not install alongside Guzzle 8 yet, so the `backup-dropbox` disk waits for a release of it that does. Google Drive: create an OAuth client of type "Desktop app" in Google Cloud, allow the Drive API and get a refresh token once (for example with Google's OAuth Playground); the module refreshes access tokens itself.

## Schedule and retention

| Setting | Default | What it does |
|---|---|---|
| `ATRIUM_BACKUP_FREQUENCY` | `daily` | `daily`, `weekly` or `off` |
| `ATRIUM_BACKUP_TIME` | `02:00` | The time of day, in the app's time zone |
| `ATRIUM_BACKUP_DAY` | `0` | For weekly: 0 is Sunday, 6 is Saturday |
| `ATRIUM_BACKUP_KEEP_ALL_DAYS` | `2` | Every archive is kept for this many days |
| `ATRIUM_BACKUP_KEEP_DAILY` | `7` | Then one a day for this many days |
| `ATRIUM_BACKUP_KEEP_WEEKLY` | `4` | Then one a week for this many weeks |
| `ATRIUM_BACKUP_KEEP_MONTHLY` | `6` | Then one a month for this many months |
| `ATRIUM_BACKUP_KEEP_YEARLY` | `2` | Then one a year for this many years |
| `ATRIUM_BACKUP_MAX_MEGABYTES` | `5000` | Then the oldest go until the archives take no more than this |

The newest archive is never deleted. Old archives are cleaned up every day an hour after the backup time, the health check runs an hour after that.

## Health checks and e-mail

| Setting | Default | What it does |
|---|---|---|
| `ATRIUM_BACKUP_MAX_AGE_DAYS` | 1 for daily, 8 for weekly | The newest archive may be this old |
| `ATRIUM_BACKUP_SIZE_JUMP_PERCENT` | `50` | The newest archive grew or shrank by more than this against the one before: half the site went missing, or something swelled. `0` turns it off |
| `ATRIUM_BACKUP_MAIL` | every superadmin | Who gets the e-mail, comma separated |

An e-mail goes out when a backup fails, when a destination cannot be reached, when an archive is too old or too big a jump, and when cleaning up fails. Successful runs send nothing.

## The admin screen

**Backups** is in the menu of superadmins only: an archive holds every administrator's password hash and every form submission, so no role can be given it. The screen shows:

- every archive on every destination, with its size, date and place, and links to download, restore or delete it;
- **Back up now**, for everything, the databases only or the files only;
- each destination: reachable, how many archives, the newest one, space used, and any failed checks;
- the settings in force, and the log of runs, restores, downloads and deletions with who did them.

"Back up now" and restores run after the page has answered, so a big site does not hit the web server's time limit; the page shows the run and reloads when it ends. With `ATRIUM_BACKUP_QUEUE=redis` (any queue connection) they go to a queue worker instead. Only one backup or restore runs at a time.

## Restoring

Restoring replaces data. Before anything changes, the restore dialog reads the archive and shows what is in it: each database with the kind of database it came from, the size of its dump and the database it goes into, each folder with how many files are new, changed, unchanged and not in the archive, and warnings (a dump from a different kind of database, a connection that no longer exists, a dump missing from the archive). To go on, type the archive's file name.

What a restore does, and the risks:

- Each database in the archive is emptied and filled from the dump. Everything written since the archive was made is lost: orders, form submissions, edits.
- Administrators' sessions and API tokens come back as they were then: someone added since loses access, a token revoked since works again.
- Files from the archive overwrite the files with the same names. Files added since stay, unless "Delete files that are not in the archive" is ticked.
- While it runs, the site may show errors or half-restored data. Put it in maintenance mode first if visitors must not see that: `php artisan down`.
- A dump is restored only into the same kind of database: a MySQL dump into MySQL, PostgreSQL into PostgreSQL.

To make a wrong restore undoable, a fresh backup of the current state is made first (on by default: "Back up the current state first"). If it cannot be made, the restore does not start.

From the command line, the same with a summary and a question, or `--dry-run` for the summary alone:

```bash
php artisan atrium:backup:restore --dry-run
php artisan atrium:backup:restore 2026-11-17-02-00-00.zip --disk=backups --only-db
```

## Commands

| Command | What it does |
|---|---|
| `atrium:backup:run` | A backup now; `--only-db`, `--only-files` |
| `atrium:backup:restore {archive?}` | Restore, the newest archive by default; `--disk=`, `--only-db`, `--only-files`, `--delete-extra`, `--no-safety-backup`, `--password=`, `--dry-run`, `--force` |
| `backup:list`, `backup:monitor`, `backup:clean` | spatie's own commands work as well, on the same settings |

## What it adds to spatie/laravel-backup

[spatie/laravel-backup](https://spatie.be/docs/laravel-backup) does the dumping, zipping, encryption, copying, cleaning up and monitoring. Atrium adds:

- settings in one place, from `.env`, with the destination disks already defined;
- restore, which spatie does not do: a summary from the archive, a typed confirmation, a safety backup first, MySQL, PostgreSQL and SQLite, encrypted archives, archives on remote disks;
- the admin screen, superadmins only, with downloads and a log of who did what;
- running from the admin after the response or on a queue, one at a time;
- schedules by frequency, time and weekday, without editing the console kernel;
- a health check for sudden size changes, and e-mail to superadmins by default;
- consistent MySQL dumps by default, and Google Drive and Dropbox disks with refreshed tokens.

## Testing it

The package's tests run on SQLite, MySQL and PostgreSQL like the rest. Destinations are tested against real servers when they are given, and skipped otherwise:

```bash
ATRIUM_TEST_S3_ENDPOINT=http://127.0.0.1:9090 ATRIUM_TEST_SFTP=127.0.0.1:2222 ATRIUM_TEST_FTP=127.0.0.1:2121 vendor/bin/phpunit packages/backup/tests
```

The S3 test expects a bucket named `backups` (`ATRIUM_TEST_S3_BUCKET`), the SFTP and FTP ones a user `atrium` with password `atrium`; the SFTP folder is `/backups`, `ATRIUM_TEST_SFTP_ROOT` and `ATRIUM_TEST_FTP_ROOT` change the folders.
