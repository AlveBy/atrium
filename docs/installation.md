# Installing Atrium

[English](installation.md) · [Русский](installation.ru.md)

Atrium is a Laravel 13 application with the admin panel in a package. You need:

- PHP 8.3 or newer with the extensions Laravel needs (`pdo` with the driver of your database, `mbstring`, `openssl`, `fileinfo`, `xml`, `ctype`, `tokenizer`), and GD with WebP support if you want lighter copies of uploaded images;
- Composer 2;
- SQLite, MySQL 8 or PostgreSQL.

Node is not needed: the admin comes prebuilt. Neither is a queue worker or a cron job: the panel sends its mail at once and needs no scheduled tasks.

To try Atrium on your own computer, see [Try it](../README.md#try-it) in the README. This page is about a site that people will use.

- [Your own server](#your-own-server)
- [Hosting with a control panel (ISPmanager, cPanel)](#hosting-with-a-control-panel-ispmanager-cpanel)
- [Docker](#docker)
- [Updating](#updating)
- [Into an existing Laravel app](#into-an-existing-laravel-app)

## Your own server

```bash
git clone https://github.com/AlveBy/atrium.git /var/www/site
cd /var/www/site
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Edit `.env` before going further:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=site
DB_USERNAME=site
DB_PASSWORD=secret

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=465
MAIL_USERNAME=site@example.com
MAIL_PASSWORD=secret
MAIL_FROM_ADDRESS=site@example.com
```

Mail matters from the first sign-in: the admin asks for a code sent by email. See [Mail and the sign-in code](configuration.md#mail-and-the-sign-in-code).

Then install. The command asks for the first administrator's name, email and password:

```bash
php artisan atrium:install
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`atrium:install` publishes the config and the admin bundle, runs the migrations, creates the first administrator and links `public/storage`, where the files uploaded in the admin are served from.

The web server must be able to write to `storage` and `bootstrap/cache`:

```bash
chown -R www-data:www-data storage bootstrap/cache
```

### The web server

The document root is the `public` folder, never the project folder.

**Apache** reads `public/.htaccess`; it needs `mod_rewrite`, and `mod_headers` for the WebP rule. Allow `.htaccess` for the site with `AllowOverride All`.

**nginx**, with PHP-FPM:

```nginx
map $http_accept $webp_suffix {
    default "";
    "~*image/webp" ".webp";
}

server {
    listen 80;
    server_name example.com;
    root /var/www/site/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # photo.jpg.webp in place of photo.jpg for browsers that take WebP.
    location ~* \.(jpe?g|png)$ {
        add_header Vary Accept;
        try_files $uri$webp_suffix $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

## Hosting with a control panel (ISPmanager, cPanel)

Ordinary shared hosting is enough: no Docker, no Node, no root access.

1. **PHP version.** Pick PHP 8.3 or newer for the site in the panel. The console may have its own PHP: in ISPmanager it is usually `/opt/php83/bin/php` (or `php8.3`), in cPanel `/opt/cpanel/ea-php83/root/usr/bin/php`. Use that path in place of `php` below; `php -v` shows which one you got.
2. **Database.** Create a MySQL database and a user in the panel.
3. **Files.** Open the panel's terminal (ISPmanager: "Shell client", cPanel: "Terminal") or connect over SSH, go to the site's folder and run the commands from [Your own server](#your-own-server): `git clone` (or upload and unpack an archive of the repository), `composer install --no-dev --optimize-autoloader`, `.env`, `atrium:install`. If there is no `composer` command, download `composer.phar` from getcomposer.org and run it with the same PHP.
4. **Document root.** Point the site at the `public` folder: in ISPmanager, the site's settings → "Root directory"; in cPanel, Domains → the domain → "Document Root". Hosting runs Apache (often behind nginx), so `public/.htaccess` works as is. If the panel does not let you choose the folder, see [When the document root is fixed](#when-the-document-root-is-fixed-public_html-www).
5. **Mail.** Create a mailbox for the site in the panel and put its SMTP settings into `.env`.

If the hosting forbids symbolic links, `storage:link` fails and uploaded files answer 404. Then serve the `public` disk from inside `public`: in `config/filesystems.php` set the disk's `root` to `public_path('storage')`.

No console at all? Install on your own computer with the same `.env`, pointed at a local database, then upload the files and import a dump of that database through the panel.

### When the document root is fixed (public_html, www)

Many shared hostings serve a fixed folder that cannot be changed to `public`. Timeweb and Beget, for example, give a site a folder of its own and serve `public_html` inside it. Put the project into the site's folder, next to that one, and do one of two things:

- **A link.** Empty `public_html` of what the hosting put there, remove it and put a link to `public` in its place: `rm -r public_html && ln -s public public_html`. Timeweb's own guide for Laravel does this. The project stays as it is, and `git pull` works as usual.
- **A rename.** If the hosting does not serve a site through a link, remove `public_html` and rename `public` to `public_html` (or whatever the hosting calls its folder). Nothing else needs setting: a request learns its folder from `index.php`, and artisan finds it by itself, as the only folder of the project whose `index.php` starts Atrium. `storage:link`, `atrium:install` and `npm run build` write there.

After a rename there must be no `public` with an `index.php` left: while there is one, artisan writes into it. Two renamed copies are not guessed between either, and artisan keeps to `public`. In a git checkout the renamed folder shows as deleted, and `git pull` stops when `public` changes upstream: for updates through git the link is simpler.

## Docker

`compose.yaml` in the repository is for trying Atrium only: the administrator's password is public and the sign-in code is off.

For a live site use any image for Laravel (PHP-FPM with nginx, FrankenPHP, Apache) and add to its start or deploy step:

```bash
php artisan atrium:install --no-interaction --name="Admin" --email="admin@example.com" --password="a long password"
php artisan config:cache
php artisan route:cache
```

The install is safe to repeat: the administrator is created only when there is none, the rest only applies what is new. Keep `storage` on a volume, and the database file too if it is SQLite. Pass the `.env` values as environment variables.

## Updating

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan atrium:install
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`atrium:install` on an installed site runs the new migrations and republishes the admin bundle; it does not touch the content or the administrators. See [CHANGELOG.md](../CHANGELOG.md) for what changed.

## Into an existing Laravel app

The steps above install this repository: a Laravel app with Atrium already in it. The packages also go into any Laravel 13 app. They are on Packagist:

```bash
composer require alveby/atrium:^0.2
php artisan atrium:install
```

The public theme and the 1C-Bitrix migrator are packages of their own:

```bash
composer require alveby/atrium-theme
composer require alveby/atrium-bitrix --dev
```

If the web root of your app is not called `public`, tell Laravel where it is: `$app->usePublicPath(__DIR__);` in its `index.php` after `bootstrap/app.php` is loaded, and `->usePublicPath(\Atrium\Admin\Support\PublicFolder::find(dirname(__DIR__)))` after `->create()` in `bootstrap/app.php` for artisan, as this repository does.

Routes in your `routes/web.php` win over the theme's. A new Laravel app answers `/` with its welcome page from there: delete that route, and the theme draws the home page.
