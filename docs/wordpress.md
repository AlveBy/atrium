# Moving from WordPress

[English](wordpress.md) · [Русский](wordpress.ru.md)

`alveby/atrium-wordpress` moves the content of a WordPress site to Atrium: posts become news, pages become pages, every other post type gets a module of its own with its custom fields, and categories, tags, authors, files, SEO and translations come along. Then it proves that nothing was lost and writes the 301 map from the old addresses to the new ones.

The WordPress database is only read. The old site keeps working until you switch the domain.

- [What you need](#what-you-need)
- [1. Connect the WordPress database](#1-connect-the-wordpress-database)
- [2. Bring the files](#2-bring-the-files)
- [3. Move](#3-move)
- [4. Check](#4-check)
- [5. Redirect the old addresses](#5-redirect-the-old-addresses)
- [6. Catch up and switch](#6-catch-up-and-switch)
- [Messy data](#messy-data)
- [What does not move yet](#what-does-not-move-yet)

## What you need

- An installed Atrium ([Installing](installation.md)), best on your own computer first: the move is repeatable, and the live site comes later.
- The News and Pages modules, if posts and pages should go there: `php artisan atrium:module:install alveby/atrium-news` and `alveby/atrium-pages`. Without them posts and pages get modules of their own, like any other post type.
- A copy of the WordPress database (a dump from the hosting panel, `mysqldump` or `wp db export`), imported into a database your Atrium can reach. WordPress does not need to run.
- A copy of the `wp-content/uploads` folder.

```bash
composer require alveby/atrium-wordpress --dev
php artisan vendor:publish --tag=atrium-wordpress-config
```

## 1. Connect the WordPress database

Add a connection named `wordpress` to `config/database.php`, next to the others:

```php
'wordpress' => [
    'driver' => 'mysql',
    'host' => env('WORDPRESS_DB_HOST', '127.0.0.1'),
    'port' => env('WORDPRESS_DB_PORT', '3306'),
    'database' => env('WORDPRESS_DB_DATABASE', 'wordpress'),
    'username' => env('WORDPRESS_DB_USERNAME', 'root'),
    'password' => env('WORDPRESS_DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
],
```

and the values to `.env`. If the tables do not start with `wp_` (look at `$table_prefix` in the old `wp-config.php`), set `WORDPRESS_TABLE_PREFIX`. Every command also takes `--connection` and `--prefix`. Check what is there:

```bash
php artisan atrium:wordpress:analyze
```

It lists the languages, the post types with their record counts, custom fields and where each type goes, the taxonomies, and what will not move and why. `--json` gives the same for a script.

Where each type goes is decided like this, and changed in `post_types` in `config/atrium-wordpress.php`:

- `post` goes to News and `page` to Pages, when the module is installed and the type has no custom fields: the modules have no place for them. Posts with custom fields get a module of their own instead. Force News or Pages anyway with `'post' => 'news'`, and the fields are reported as lost.
- Every other public type (a recipe, a project, a product of a plugin) gets a module of its own: a migration and a model with its admin form, like a Bitrix iblock. ACF fields keep their labels, types and order; other custom fields are found by looking at the values.
- `'product' => 'skip'` leaves a type behind; `'recipe' => ['table' => 'recipes', 'model' => 'Recipe', 'prefix' => 'recipes']` names its table, model and address.

## 2. Bring the files

Copy `wp-content/uploads` anywhere on the new server and set `WORDPRESS_UPLOADS_PATH` to that folder. The files go to `public/wp-content/uploads`: their addresses stay as they were, so old links and image search keep working, and the texts point to them on the new site.

## 3. Move

```bash
php artisan atrium:wordpress:migrate
```

runs the whole sequence: models and migrations for the generated modules, `migrate`, the data, and the check. Each step can also run on its own, in this order:

```bash
php artisan atrium:wordpress:generate
php artisan migrate
php artisan atrium:wordpress:migrate:data
php artisan atrium:wordpress:parity
```

`generate` writes the models to `app/Models` and the migrations to `database/migrations`, and prints the lines to add to `config/atrium.php`: `resources` puts the modules in the admin's menu, `public_urls` opens their records on the site.

```php
'resources' => [
    App\Models\Recipe::class,
    App\Models\Cuisine::class,
],
'public_urls' => [
    App\Models\Recipe::class => 'recipes',
],
```

Generated files are meant to be regenerated, not edited: `generate` refuses to overwrite a file it did not write, `--force` overwrites it anyway, and after a regeneration look at `git diff app/Models`.

What moves:

- Posts, pages and custom post types: the title, the text (Gutenberg's block comments taken out, the classic editor's line breaks turned into paragraphs even between headings and lists, `[caption]` and `[gallery]` turned into figures), the excerpt, the slug, the date (in the time zone of `config('app.timezone')`), the order, the parent of a page, the featured image. Published and scheduled posts are active; drafts, pending, private and password-protected ones move hidden; the trash, revisions and auto-drafts are left behind.
- Categories, tags and custom taxonomies, with their tree, descriptions and links to the records.
- Users who wrote something, as authors: the name, the login, the email. Never their passwords.
- Attachments: the files into `public/wp-content/uploads` and the media library (a "WordPress" collection) with the title, alt text, description and the sizes WordPress made. Links in texts to the old domain's uploads point to the new site.
- ACF: text, number, true/false, dates, choices, images, files, galleries, links, groups, repeaters and flexible content.
- SEO from Yoast or Rank Math: the title and description (with their `%%title%%` / `%title%` variables filled in), focus keywords, Open Graph, the canonical address, noindex and nofollow, for records and for terms.
- Languages from WPML or Polylang: the translations of a post become the languages of one record, and the languages are added to Atrium with WordPress's default as the default. A site without a language plugin goes under the language of its `WPLANG`, or the `language` set in the config.
- Menus, into the menus module (`php artisan atrium:module:install alveby/atrium-menus`): every menu becomes a menu with a code made from its slug (`main-menu`), its items stay nested and in their order, a draft item is switched off. An item that links to a post, page or category links to the record that was moved, so it follows the record's new address; a custom link stays as it is, with the old domain's uploads and a port in the address fixed as in the texts; an archive of a post type goes to its list; `#` and an empty link make a heading for the items below. A link to something that does not move (a product of a shop, a deleted page) becomes a heading too, with a note. Menus nest four levels deep in Atrium; the items below the fourth go up to it, with a note. Without the menus module the menus are skipped with a note, and moving again after installing it brings them.

Run it again any time: records are matched by their WordPress ID in the `wordpress_map` table, and a second run updates them instead of doubling them. A menu moved before gets its items written again from WordPress, so edit imported menus in WordPress until the switch.

WordPress shows a menu in a theme **location** (`primary`, `footer`); Atrium has none, the theme asks for a menu by its code: `<x-atrium-menu code="main-menu" />`. The notes of the move list every location and the code of the menu that was in it. With WPML or Polylang a menu in another language becomes a menu of that language with the same code, so the theme asks for one and gets the language it shows.

## 4. Check

```bash
php artisan atrium:wordpress:parity
```

builds again from the WordPress database what every record should hold, and compares it with what Atrium has: every language, author, term, menu and file, every record's fields, parent, categories and tags, SEO and redirects. An empty report means nothing was lost; it exits with 1 when something differs, so a script can stop on it. `--format=markdown --output=parity.md` writes it to a file for the client, `--format=json` for a script.

Warnings are not differences: they list what WordPress itself did not have (a featured image whose file is missing in `uploads`) and what was moved on purpose without a place (custom fields of posts forced into News). Notes tell what was repaired on the way, see [Messy data](#messy-data).

That checks the data. Whether every page of the old site opens on the new one is checked against the live old site, with the redirects below.

## 5. Redirect the old addresses

```bash
php artisan atrium:wordpress:redirects --format=nginx --output=wordpress-redirects.conf
```

writes a 301 from every old address to its new one: posts in the site's permalink structure (`/%year%/%monthnum%/%postname%/`, `/%category%/%postname%/` and the rest, or plain `/?p=123`), short links `/?p=123`, pages with their parents' slugs, categories and tags with the category and tag bases, each language with its folder or its `?lang=`. `--format=apache` writes `RewriteRule` lines for `.htaccess`, `--format=csv` a table to read or to load elsewhere.

For nginx, include the file in `http` and redirect in the site's `server`:

```nginx
if ($atrium_wordpress_redirect) {
    return 301 $atrium_wordpress_redirect;
}
```

## 6. Catch up and switch

Editors keep working in WordPress while you build the new site. Before switching, bring their edits over with a fresh dump:

```bash
php artisan atrium:wordpress:sync --prune --audit
```

moves only the posts changed since the last run and the new ones, `--prune` removes those deleted or trashed in WordPress, and `--audit` runs `parity` when done.

The importer is a development dependency, so the last `sync` runs where you did the move. Then carry that database and `public/wp-content/uploads` over to the live site ([Installing](installation.md)), put the redirects in the web server and point the domain at it.

## Messy data

Real WordPress databases are old, and the importer expects that:

- Serialized PHP broken by a search and replace on the database (wrong string lengths in `wp_postmeta` and `wp_options`) is repaired and read; a note says where.
- A file the database names but `uploads` lacks is listed, and the record moves without it.
- Two records with the same slug get `-2`, `-3`: pages within their parent, the rest within their language.
- Texts in another encoding or with broken characters are converted to UTF-8; percent-encoded slugs (Cyrillic ones) are decoded.
- A page whose parent is gone or of another type, and a loop of parents, go to the top level, with a note.
- An author who is not in `wp_users` any more: the post moves without an author, with a note.
- A post protected by a password moves hidden, with a note: Atrium has no password gate, and the text would go public.
- A value saved twice through `update_post_meta` (a serialized string inside a serialized string) is read through both layers.
- The redirect map for nginx carries every Cyrillic address twice, in lower-case and upper-case `%xx`: browsers send capitals, WordPress stores small letters, and nginx compares them as written.

## What does not move yet

- **A WXR export**: the importer reads the database. The export file is on the list.
- **Comments, widgets and the theme**: the new site is drawn by the public theme (`alveby/atrium-theme`) from the modules, or by your own Blade templates. Menus move, see [3. Move](#3-move), but the theme's own locations do not.
- **Shortcodes and blocks of plugins** stay in the text as they were; look for `[` in the texts of the new site.
- **Relationship and post object fields** of ACF keep WordPress IDs.
- **SEO of translations**: Yoast and Rank Math values move from the post of the default language.
- **Addresses with a domain per language**: the redirects cover folders and `?lang=`; for domains, write them on each domain.
- **Attachment pages, author and date archives**: no redirects are written for them.
- **Multisite networks**: their sites share `wp_users` while the rest of the tables carry the site's number (`wp_2_posts`); the importer expects one prefix for all.
- **Users who did not write, roles, passwords, WooCommerce**: the importer moves content. The shop is on the [roadmap](../ROADMAP.md).
