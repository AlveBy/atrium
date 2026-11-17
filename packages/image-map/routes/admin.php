<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Atrium\ImageMap\Http\Controllers\EditorController;
use Illuminate\Support\Facades\Route;

// The drawing page of a layer and its save. The names are not module
// actions, so the controller checks the permissions itself.
Route::get('image-map/layers/{layer}/editor', [EditorController::class, 'show'])
    ->whereNumber('layer')->name('admin.image-map.editor');
Route::put('image-map/layers/{layer}/areas', [EditorController::class, 'sync'])
    ->whereNumber('layer')->name('admin.image-map.sync');
