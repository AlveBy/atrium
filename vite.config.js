/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { existsSync, readFileSync, readdirSync } from 'node:fs';

// The web root, as the console finds it (Atrium\Admin\Support\PublicFolder):
// public while it has its index.php, otherwise the only folder whose
// index.php starts the app, for hostings that serve www or public_html.
function publicDirectory() {
    if (existsSync('public/index.php')) {
        return 'public';
    }

    const found = readdirSync('.', { withFileTypes: true })
        .filter((entry) => entry.isDirectory() && existsSync(`${entry.name}/index.php`))
        .filter((entry) => readFileSync(`${entry.name}/index.php`, 'utf8').includes('/bootstrap/app.php'))
        .map((entry) => entry.name);

    return found.length === 1 ? found[0] : 'public';
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            publicDirectory: publicDirectory(),
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
