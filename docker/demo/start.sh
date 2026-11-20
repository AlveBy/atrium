#!/bin/sh
# This file is part of Atrium (https://github.com/AlveBy/atrium).
# Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.

# Starts the demo: puts it back as it was (a new database, the demo content,
# the `demo` administrator), runs the scheduler, which does the same every
# hour (ATRIUM_DEMO_RESET), and serves the site.
set -e

mkdir -p storage/framework/views storage/framework/cache/data storage/framework/sessions storage/logs
touch "${DB_DATABASE}"
php artisan storage:link --no-interaction >/dev/null 2>&1 || true
php artisan atrium:demo:reset --no-interaction

echo "Atrium demo: ${APP_URL}/admin (${ATRIUM_DEMO_EMAIL:-demo@atrium.test} / ${ATRIUM_DEMO_PASSWORD:-demo})"

php artisan schedule:work --no-interaction &

exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
