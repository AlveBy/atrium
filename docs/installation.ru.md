# Установка Atrium

[English](installation.md) · [Русский](installation.ru.md)

Atrium — приложение на Laravel 13, админка лежит в пакете. Понадобится:

- PHP 8.3 или новее с расширениями, которые нужны Laravel (`pdo` с драйвером вашей базы, `mbstring`, `openssl`, `fileinfo`, `xml`, `ctype`, `tokenizer`), и GD с поддержкой WebP, если нужны облегчённые копии загруженных картинок;
- Composer 2;
- SQLite, MySQL 8 или PostgreSQL.

Node не нужен: админка приходит собранной. Обработчик очередей и cron тоже не нужны: письма уходят сразу, задач по расписанию нет.

Попробовать Atrium у себя на компьютере — раздел [Попробовать](readme/README.ru.md#попробовать) в README. Эта страница — про сайт, которым будут пользоваться.

- [Свой сервер](#свой-сервер)
- [Хостинг с панелью (ISPmanager, cPanel)](#хостинг-с-панелью-ispmanager-cpanel)
- [Docker](#docker)
- [Обновление](#обновление)

## Свой сервер

```bash
git clone https://github.com/AlveBy/atrium.git /var/www/site
cd /var/www/site
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Прежде чем идти дальше, поправьте `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
ATRIUM_DEMO=false

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

Почта нужна с первого входа: админка спрашивает код из письма. Подробнее — [Почта и код входа](configuration.ru.md#почта-и-код-входа).

Потом установка. Команда спросит имя, почту и пароль первого администратора:

```bash
php artisan atrium:install
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`atrium:install` публикует конфиг и сборку админки, запускает миграции, создаёт первого администратора и делает ссылку `public/storage`, через которую отдаются файлы, загруженные в админке.

Веб-сервер должен иметь право писать в `storage` и `bootstrap/cache`:

```bash
chown -R www-data:www-data storage bootstrap/cache
```

### Веб-сервер

Корень сайта — папка `public`, а не папка проекта.

**Apache** читает `public/.htaccess`; нужен `mod_rewrite`, а для правила WebP — `mod_headers`. Разрешите сайту `.htaccess`: `AllowOverride All`.

**nginx** с PHP-FPM:

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

## Хостинг с панелью (ISPmanager, cPanel)

Хватит обычного хостинга: без Docker, без Node, без root-доступа.

1. **Версия PHP.** Выберите для сайта в панели PHP 8.3 или новее. У консоли бывает свой PHP: в ISPmanager обычно `/opt/php83/bin/php` (или `php8.3`), в cPanel — `/opt/cpanel/ea-php83/root/usr/bin/php`. Подставляйте этот путь вместо `php` в командах ниже; `php -v` покажет, какой попался.
2. **База.** Создайте в панели базу MySQL и пользователя.
3. **Файлы.** Откройте терминал панели (в ISPmanager — «Shell-клиент», в cPanel — «Terminal») или зайдите по SSH, перейдите в папку сайта и выполните команды из раздела [Свой сервер](#свой-сервер): `git clone` (или загрузите и распакуйте архив репозитория), `composer install --no-dev --optimize-autoloader`, `.env`, `atrium:install`. Если команды `composer` нет, скачайте `composer.phar` с getcomposer.org и запускайте его тем же PHP.
4. **Корень сайта.** Направьте сайт в папку `public`: в ISPmanager — настройки сайта → «Корневая директория», в cPanel — Domains → домен → «Document Root». На хостинге работает Apache (часто за nginx), так что `public/.htaccess` подхватится сам.
5. **Почта.** Заведите в панели ящик для сайта и пропишите его SMTP в `.env`.

Если хостинг запрещает символические ссылки, `storage:link` не сработает, и загруженные файлы будут отвечать 404. Тогда храните диск `public` прямо внутри `public`: в `config/filesystems.php` поставьте диску `root` в `public_path('storage')`.

Консоли нет совсем? Установите у себя на компьютере с тем же `.env`, но с локальной базой, потом загрузите файлы и импортируйте дамп этой базы через панель.

## Docker

`compose.yaml` в репозитории — только чтобы попробовать: пароль администратора общеизвестен, код входа выключен.

Для боевого сайта берите любой образ для Laravel (PHP-FPM с nginx, FrankenPHP, Apache) и добавьте в шаг запуска или выкладки:

```bash
php artisan atrium:install --no-interaction --name="Admin" --email="admin@example.com" --password="a long password"
php artisan config:cache
php artisan route:cache
```

Установку можно повторять: администратор создаётся, только если его ещё нет, остальное лишь применяет новое. Держите `storage` на томе, файл базы тоже, если это SQLite. Значения `.env` передавайте переменными окружения.

## Обновление

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan atrium:install
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`atrium:install` на установленном сайте применяет новые миграции и заново публикует сборку админки; контент и администраторов не трогает. Что поменялось — в [CHANGELOG.md](../CHANGELOG.md).
