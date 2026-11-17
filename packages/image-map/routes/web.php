<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Atrium\ImageMap\Support\Assets;
use Illuminate\Support\Facades\Route;

// The map's script and styles. The version in the address is the file's
// hash, so the answer can be kept for a year.
Route::get('atrium-image-map/{version}/{file}', function (string $version, string $file) {
    abort_unless(in_array($file, Assets::FILES, true), 404);

    return response()->file(Assets::path($file), [
        'Content-Type' => str_ends_with($file, '.css') ? 'text/css; charset=utf-8' : 'text/javascript; charset=utf-8',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where(['version' => '[a-f0-9]{1,10}', 'file' => '[a-z\-]+\.(js|css)'])->name('atrium-image-map.asset');
