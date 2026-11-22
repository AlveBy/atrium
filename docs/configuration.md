# Configuring Atrium

[English](configuration.md) · [Русский](configuration.ru.md)

Most of what a site owner changes lives in the admin itself: sites and their domains, content languages, counters and verification codes, `robots.txt`, administrators and roles. This page is about what is set in files: `.env` and `config/atrium.php` (published by `atrium:install`).

After changing either on a live site, run `php artisan config:cache` again.

- [The main settings](#the-main-settings)
- [Colour themes](#colour-themes)
- [Mail and the sign-in code](#mail-and-the-sign-in-code)
- [Files and images](#files-and-images)
- [API tokens](#api-tokens)
- [AI assistants (MCP)](#ai-assistants-mcp)
- [Public addresses, sitemap and robots.txt](#public-addresses-sitemap-and-robotstxt)
- [Your own error pages](#your-own-error-pages)

## The main settings

| Setting | Where | Default | What it does |
|---|---|---|---|
| `prefix` | config | `admin` | The admin's address: `/admin` |
| `resources` | config | `[]` | The modules: Eloquent models that implement `Administrable`. See [Your own module](modules.md) |
| `modules` | config | `[]` | The installed module packages, in menu order. `atrium:module:install` and `atrium:module:remove` edit it. See [A module as a package](modules.md#a-module-as-a-package) |
| `menu` | config | dashboard, administrators, roles, sites, languages, API tokens | Fixed menu entries; modules not listed here are added after the first entry. A label may be a translation key |
| `ATRIUM_NAME`, `ATRIUM_LOGO`, `ATRIUM_FAVICON` | .env | `Atrium` | The name and pictures in the admin's header and tab. Without a logo the admin shows the Atrium arch; without a favicon, the Atrium icon |
| `ATRIUM_ADMIN_THEME` | .env | `graphite` | The admin's colours until an administrator picks their own. See [Colour themes](#colour-themes) |
| `ATRIUM_LOCALE` | .env | `en` | The admin's language for guests whose browser language is not in `locales`; each administrator picks their own in the header |
| `locales` | config | 15 languages | The admin's languages; remove an entry to hide it from the switcher |
| `pagination` | config | 20 per page | List page sizes |
| `ATRIUM_TIMINGS` | .env | `true` | Record how long public pages take, for the two speed tiles on the dashboard. `ATRIUM_TIMINGS_SAMPLE_RATE=0.1` records every tenth page |

Any line of the admin's interface can be reworded:

```bash
php artisan vendor:publish --tag=atrium-lang
```

The files land in `lang/vendor/atrium/{language}/`; change the lines you need and delete the rest.

## Colour themes

The admin comes in five themes:

| Theme | Looks like |
|---|---|
| `graphite` | A dark sidebar and a light page. The default |
| `day` | Light all over |
| `night` | Dark all over |
| `sand` | Warm and light, with a brown sidebar |
| `contrast` | Black on white with a yellow selection and thick borders, for poor sight |

Each administrator picks one in the menu under their name, or "As the system": then the admin follows the computer's light or dark mode (graphite or night). The choice is kept with the administrator and applies on every device they sign in from. Until they pick, the admin uses `ATRIUM_ADMIN_THEME` (`atrium.admin.theme` in `config/atrium.php`); the sign-in page always uses it.

Every theme passes WCAG AA: text has a contrast of at least 4.5:1, borders, the focus ring and the selection at least 3:1. The selected menu entry is also bold and has a bar on the left, and an active record has a filled dot where a hidden one has a ring, so nothing depends on colour alone. `packages/admin/tests/Unit/ThemeContrastTest.php` measures every pair from `packages/admin/resources/css/atrium.css`, where the themes are defined.

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
| `ATRIUM_UPLOADS_DISK` | `public` | The disk uploads go to; a bucket works too, see [CDN](cdn.md#uploads-to-s3) |
| `uploads.directory` | `uploads` | The folder inside the disk |
| `uploads.max_size` | `5120` | The largest file, in KB |
| `uploads.image_max_size` | `2048` | The largest image, in KB |
| `uploads.allowed_extensions` | jpg, jpeg, png, gif, webp, pdf, doc, docx, xls, xlsx | What may be uploaded |
| `ATRIUM_UPLOADS_WEBP` | `true` | Put a lighter WebP copy next to an uploaded JPEG or PNG |

The WebP copy is named after the original, `photo.jpg.webp`, and the web server gives it to browsers that take WebP at the original's address: nothing changes in the database or in templates. Apache does it with the rule in `public/.htaccess`, nginx with the rule in [Installing](installation.md#the-web-server). Without such a rule turn the copies off: they would take disk space and never reach visitors. The copies need PHP's GD with WebP support; without it the upload simply goes without a copy.

PHP limits come first: an upload larger than `upload_max_filesize` or `post_max_size` in `php.ini` never reaches Atrium.

To serve pictures and files from a CDN, with resized copies and cache purge, see [CDN](cdn.md).

## API tokens

Every module is also available over an HTTP API at `/api/v1`, for programs and integrations. The API describes itself: `/api/v1` answers with Markdown documentation of every module and its fields, `/api/v1/openapi.json` with the OpenAPI schema.

A token is issued in the admin, under "API tokens": pick the modules and, for each, reading or writing, and optionally an expiry date. The token is shown once. It is sent as a header:

```bash
curl -H "Authorization: Bearer <token>" https://example.com/api/v1/articles
```

The buttons of a module (see [Buttons on a record](modules.md#buttons-on-a-record-and-over-selected-records)) are there too: `GET /api/v1/{module}/actions` lists the ones the token may run, `POST /api/v1/{module}/{id}/actions/{name}` runs one on a record, `POST /api/v1/{module}/bulk/{name}` with `{"ids": [1, 2]}` on several.

A token never has more rights than its owner has now: if an administrator loses the right to edit articles, so do their tokens. Administrators themselves are never in the API (`api.except`).

| Setting | Default | What it does |
|---|---|---|
| `api.prefix` | `api/v1` | The API's address |
| `api.except` | `['users']` | Module keys that are never in the API |

## AI assistants (MCP)

An AI assistant that speaks MCP (Claude, ChatGPT, Cursor and others) connects to `/mcp` with an API token and reads and edits the modules for you: "add three news items", "raise the prices in this section by 5%". It gets ten tools: `list_modules`, `describe_module`, `list_records`, `get_record`, `create_record`, `update_record`, `delete_record`, and `list_actions`, `run_record_action`, `run_bulk_action` for the modules' buttons (publish, hide, delete several records at once).

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

## Your own error pages

With the theme installed, "not found" (404), "access denied" (403), "page expired" (419), "too many requests" (429), "server error" (500) and maintenance (503) are drawn in the site's design and language; other codes get a general 4xx or 5xx page. The 404 page has a link home and, when the site has a search, a search box.

Your own page works the Laravel way: put a Blade file into `resources/views/errors` and it wins over the theme. Your files are part of your app, so `composer update` never touches them. The order:

1. `resources/views/errors/{site code}/404.blade.php`, then `…/4xx.blade.php` — for one site of several;
2. `resources/views/errors/404.blade.php`, then `4xx.blade.php` (`5xx.blade.php` for server errors);
3. the theme's page;
4. Laravel's own.

To start from the theme's pages:

```bash
php artisan vendor:publish --tag=atrium-errors
```

The copies use `<x-atrium-theme::error-page>`, which draws the theme's layout (a 5xx page gets a plain frame that does not read the database); replace it with your own layout if you like. Every error page gets `$siteName`, `$homeUrl` and `$searchUrl`, and is in the language of the address. JSON and API requests still get JSON, and the admin (`/admin`) is not touched by the theme's pages. For maintenance, `php artisan down --render="errors::503"` prerenders the same page.

## Captcha

One captcha guards the public forms of every module that asks for it (the reviews, for one). Off by default; the forms still have their own honeypot and limits.

| Setting | Default | What it does |
|---|---|---|
| `ATRIUM_CAPTCHA` | `none` | `image`: the built-in one, a sum such as "7 + 4" drawn as a picture, with no keys and no outside service. `recaptcha`: Google reCAPTCHA v2, the "I'm not a robot" box |
| `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY` | | Keys for the site's domains from [the reCAPTCHA admin](https://www.google.com/recaptcha/admin). Without them `recaptcha` uses the built-in one |
| `captcha.image.ttl` | `10` | Minutes a question of the built-in captcha can be answered |

A question of the built-in captcha is answered once, right or wrong: a wrong answer brings a new picture. With reCAPTCHA the visitor's browser loads Google's script, so mention it in the site's privacy policy.

## "Made with Atrium" and the licence

The public theme puts a small "Made with Atrium" link in the footer of every page, in the visitor's language, and `<meta name="generator" content="Atrium">` in its head.

The admin shows "Made by AlveSoft Solutions", the company that builds Atrium, with the AlveSoft logo at the bottom of the sidebar and under the sign-in form. It links to [alve.by/en](https://alve.by/en/) in a new tab and speaks the admin's language. On a dark background it shows the white logo.

| Setting | Default | What it does |
|---|---|---|
| `ATRIUM_THEME_CREDIT` | `true` | Show the "Made with Atrium" link (`config/atrium-theme.php` → `credit`) |
| `ATRIUM_CREDIT` | `true` | Show "Made by AlveSoft Solutions" in the admin (`config/atrium.php` → `credit`) |
| `ATRIUM_DEMO` | `false` | Demo mode for a public demo: `atrium:demo:reset` works and the profile is locked, see [demo.md](demo.md) |

Atrium is free under the MIT License, so both links are yours to switch off. If Atrium helped you, please keep them: it is how other people find the project. This is a request, not a condition of the licence.

What MIT does require is that the copyright notice stays in copies of the code: keep the `LICENSE` file and the header at the top of each source file (`This file is part of Atrium …`) when you copy, fork or publish the packages. Your own code, including the files `atrium:make-module` creates in your application, is yours and needs no header.
