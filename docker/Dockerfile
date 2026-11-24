# This file is part of Atrium (https://github.com/AlveBy/atrium).
# Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.

# The PHP that `compose.yaml` runs Atrium with: PHP, Composer and Node.
# zip, intl and ftp are the extensions the lock file asks for that php:8.4-cli
# lacks (`composer check-platform-reqs --lock` lists them).
# The database is SQLite in database/database.sqlite, so MySQL is not needed.
# Node builds the admin bundle in a checkout of main, which does not keep it;
# the stable branch has it built.
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev \
    && docker-php-ext-install zip intl ftp \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
# compose.yaml keeps the admin's node_modules in a volume of its own, so the
# Linux packages do not replace the ones of the computer the checkout is on.
RUN mkdir -p /app/packages/admin/node_modules && chmod 1777 /app/packages/admin/node_modules

ENV COMPOSER_HOME=/tmp/composer
ENV NPM_CONFIG_CACHE=/tmp/npm

WORKDIR /app
