#!/bin/sh
# This file is part of Atrium (https://github.com/AlveBy/atrium).
# Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.

# Brings the checkout up to date and serves it. `atrium:install` is safe to
# repeat: the first start creates the admin and the demo shop, every later one
# only applies what `git pull` brought (migrations, the admin bundle).
set -e

composer install --no-interaction --no-progress

# The stable branch keeps the admin bundle built; a checkout of main builds it.
if ! git ls-files --error-unmatch packages/admin/dist/manifest.json >/dev/null 2>&1; then
    npm ci --prefix packages/admin --no-audit --no-fund
    npm run build --prefix packages/admin
fi

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --no-interaction
fi

php artisan atrium:install --demo --no-interaction \
    --name="${ATRIUM_ADMIN_NAME}" \
    --email="${ATRIUM_ADMIN_EMAIL}" \
    --password="${ATRIUM_ADMIN_PASSWORD}"

echo "Atrium: http://localhost:${ATRIUM_PORT}/admin (${ATRIUM_ADMIN_EMAIL} / ${ATRIUM_ADMIN_PASSWORD})"

exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
