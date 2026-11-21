# This file is part of Atrium (https://github.com/AlveBy/atrium).
# Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.

# The PHP that `compose.yaml` runs Atrium with: PHP and Composer, nothing else.
# zip, intl and ftp are the extensions the lock file asks for that php:8.4-cli
# lacks (`composer check-platform-reqs --lock` lists them).
# The database is SQLite in database/database.sqlite and the admin bundle is
# prebuilt, so neither MySQL nor Node is needed to try it.
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
    && docker-php-ext-install zip intl ftp \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_HOME=/tmp/composer

WORKDIR /app
