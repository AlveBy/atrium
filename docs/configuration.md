# Configuring Atrium

[English](configuration.md) · [Русский](configuration.ru.md)

Most of what a site owner changes lives in the admin itself: sites and their domains, content languages, counters and verification codes, `robots.txt`, administrators and roles. This page is about what is set in files: `.env` and `config/atrium.php` (published by `atrium:install`).

After changing either on a live site, run `php artisan config:cache` again.

- [The main settings](#the-main-settings)
- [Mail and the sign-in code](#mail-and-the-sign-in-code)
- [Files and images](#files-and-images)
- [API tokens](#api-tokens)
- [AI assistants (MCP)](#ai-assistants-mcp)
- [Public addresses, sitemap and robots.txt](#public-addresses-sitemap-and-robotstxt)

## The main settings

| Setting | Where | Default | What it does |
|---|---|---|---|
| `prefix` | config | `admin` | The admin's address: `/admin` |
| `resources` | config | `[]` | The modules: Eloquent models that implement `Administrable`. See [Your own module](modules.md) |
| `menu` | config | dashboard, administrators, roles, sites, languages, API tokens | Fixed menu entries; modules not listed here are added after the first entry. A label may be a translation key |
| `ATRIUM_NAME`, `ATRIUM_LOGO`, `ATRIUM_FAVICON` | .env | `Atrium` | The name and pictures in the admin's header and tab |
| `ATRIUM_LOCALE` | .env | `en` | The admin's language for guests whose browser language is not in `locales`; each administrator picks their own in the header |
| `locales` | config | 15 languages | The admin's languages; remove an entry to hide it from the switcher |
| `pagination` | config | 20 per page | List page sizes |
| `ATRIUM_DEMO` | .env | `false` | The demo shop. Turned off, it leaves the menu and the site; its tables stay |
| `ATRIUM_TIMINGS` | .env | `true` | Record how long public pages take, for the two speed tiles on the dashboard. `ATRIUM_TIMINGS_SAMPLE_RATE=0.1` records every tenth page |

Any line of the admin's interface can be reworded:

```bash
php artisan vendor:publish --tag=atrium-lang
```

The files land in `lang/vendor/atrium/{language}/`; change the lines you need and delete the rest.

## Mail and the sign-in code

After the password the admin asks for a code sent to the administrator's email. A password lives until someone changes it; the code is valid for minutes, so a leaked password alone is not enough.

Set up mail before the first sign-in, with Laravel's usual `MAIL_*` variables (see [Installing](installation.md#your-own-server)). To check it without signing in:

```bash
php artisan tinker --execute="Mail::raw('test', fn (\$m) => \$m->to('you@example.com')->subject('test'))"
```

| Setting | Default | What it does |
|---|---|---|
| `ATRIUM_2FA_ENABLED` | `true` | The sign-in code. Turn it off only where there is no mail at all |
| `ATRIUM_2FA_TTL` | `10` | How many minutes a code is valid |
| `ATRIUM_2FA_RESEND_AFTER` | `60` | After how many seconds a new code may be requested |

With `MAIL_MAILER=log` (a local machine) the code is written at the end of `storage/logs/laravel.log`.

Locked out because mail stopped working? Set `ATRIUM_2FA_ENABLED=false`, run `php artisan config:cache`, sign in, fix the mail and turn the code back on.

A forgotten password is changed by a superadmin under "Administrators". If you are the only one, make another superadmin from the console and change the password there:

```bash
php artisan atrium:user --superadmin
```

## Files and images

Files uploaded in the admin go to the `public` disk (`storage/app/public`) and are served through the `public/storage` link that `atrium:install` makes.

| Setting | Default | What it does |
|---|---|---|
| `uploads.directory` | `uploads` | The folder inside the disk |
| `uploads.max_size` | `5120` | The largest file, in KB |
| `uploads.image_max_size` | `2048` | The largest image, in KB |
| `uploads.allowed_extensions` | jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx | What may be uploaded |
| `ATRIUM_UPLOADS_WEBP` | `true` | Put a lighter WebP copy next to an uploaded JPEG or PNG |

The WebP copy is named after the original, `photo.jpg.webp`, and the web server gives it to browsers that take WebP at the original's address: nothing changes in the database or in templates. Apache does it with the rule in `public/.htaccess`, nginx with the rule in [Installing](installation.md#the-web-server). Without such a rule turn the copies off: they would take disk space and never reach visitors. The copies need PHP's GD with WebP support; without it the upload simply goes without a copy.

PHP limits come first: an upload larger than `upload_max_filesize` or `post_max_size` in `php.ini` never reaches Atrium.

## API tokens

Every module is also available over an HTTP API at `/api/v1`, for programs and integrations. The API describes itself: `/api/v1` answers with Markdown documentation of every module and its fields, `/api/v1/openapi.json` with the OpenAPI schema.

A token is issued in the admin, under "API tokens": pick the modules and, for each, reading or writing, and optionally an expiry date. The token is shown once. It is sent as a header:

```bash
curl -H "Authorization: Bearer <token>" https://example.com/api/v1/articles
```

A token never has more rights than its owner has now: if an administrator loses the right to edit articles, so do their tokens. Administrators themselves are never in the API (`api.except`).

| Setting | Default | What it does |
|---|---|---|
| `api.prefix` | `api/v1` | The API's address |
| `api.except` | `['users']` | Module keys that are never in the API |

## AI assistants (MCP)

An AI assistant that speaks MCP (Claude, ChatGPT, Cursor and others) connects to `/mcp` with an API token and reads and edits the modules for you: "add three news items", "raise the prices in this section by 5%". It gets seven tools: `list_modules`, `describe_module`, `list_records`, `get_record`, `create_record`, `update_record`, `delete_record`.

Every call goes through the API, so the assistant can do exactly what its token can: the same modules, the same reading or writing, the same validation as the admin form. Give an assistant its own token with only what it needs, and an expiry date.

Claude Code:

```bash
claude mcp add --transport http atrium https://example.com/mcp --header "Authorization: Bearer <token>"
```

Other clients take the same two things in their MCP settings: the address `https://example.com/mcp` and the header `Authorization: Bearer <token>`.

| Setting | Default | What it does |
|---|---|---|
| `ATRIUM_MCP_ENABLED` | `true` | The MCP server |
| `mcp.path` | `mcp` | Its address |

## Public addresses, sitemap and robots.txt

`public_urls` says which modules have pages on the site and under which address; the slug field then shows the page's address, and the API returns it as `public_url`. See the comment in `config/atrium.php` and [Your own module](modules.md#pages-on-the-site).

| Setting | Default | What it does |
|---|---|---|
| `ATRIUM_ROBOTS` | `true` | Answer `/robots.txt` of every site with the text set in the admin (Sites → robots.txt). Off production it always says `Disallow: /`. While it is on, `public/robots.txt` must not exist |
| `ATRIUM_SITEMAP` | `true` | `/sitemap.xml` of every site, in all its languages |
| `sitemap.paths` | `['/']` | Addresses that are not module records |
| `ATRIUM_COUNTERS_CONSENT` | `false` | Start Google Tag and Yandex Metrica only after the cookie banner calls `window.atriumConsent()` |
| `YANDEX_MAPS_API_KEY`, `GOOGLE_MAPS_API_KEY` | | Keys for the map fields |
