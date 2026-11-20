# This file is part of Atrium (https://github.com/AlveBy/atrium).
# Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.

# The public demo (docs/demo.md): the demo shop, an administrator `demo` that
# cannot hurt anything, and a reset every hour. Build from the repository root:
#
#   docker build -f docker/demo/Dockerfile -t atrium-demo .
#   docker run --rm -p 8000:8000 atrium-demo
#
# then open http://localhost:8000/admin.
FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
    && docker-php-ext-install zip intl opcache pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/demo/php.ini /usr/local/etc/php/conf.d/zz-demo.ini

WORKDIR /app
COPY . /app

RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && mkdir -p /data \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/public /app/database /data

# The SQLite database is /data/demo.sqlite; the reset recreates everything, so
# nothing needs a volume.
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_URL=http://localhost:8000 \
    ATRIUM_DEMO=true \
    ATRIUM_2FA_ENABLED=false \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/data/demo.sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=log \
    LOG_CHANNEL=stderr \
    PHP_CLI_SERVER_WORKERS=6

USER www-data
EXPOSE 8000
CMD ["sh", "docker/demo/start.sh"]
