<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

use Atrium\Classifieds\Http\Controllers\AdActionController;
use Atrium\Classifieds\Http\Controllers\BoardController;
use Atrium\Classifieds\Http\Controllers\CabinetController;
use Atrium\Classifieds\Support\Accounts;
use Illuminate\Support\Facades\Route;

/*
 * Everything sits under one prefix ("classifieds" by default). The words
 * ad, company, city and my are taken: a top category cannot use them as
 * its code. Category paths come last, they catch the rest.
 */
Route::middleware('web')->group(function (): void {
    Route::localized(function (): void {
        $prefix = trim((string) config('atrium-classifieds.prefix', 'classifieds'), '/');
        $auth = app(Accounts::class)->middleware();

        Route::prefix($prefix)->name('atrium.classifieds.')->group(function () use ($auth): void {
            Route::get('/', [BoardController::class, 'index'])->name('index');
            Route::get('ad/{ad}-{slug}', [BoardController::class, 'show'])->whereNumber('ad')->where('slug', '[A-Za-z0-9\-]*')->name('ad');
            Route::get('company/{company}', [BoardController::class, 'company'])->whereNumber('company')->name('company');
            Route::get('city/{city}/{path?}', [BoardController::class, 'city'])->where('city', '[a-z0-9\-]+')->where('path', '[a-z0-9\-/]+')->name('city');

            Route::post('ad/{ad}/phone', [AdActionController::class, 'phone'])->whereNumber('ad')->name('phone');
            Route::post('ad/{ad}/complain', [AdActionController::class, 'complain'])->whereNumber('ad')->name('complain');

            Route::middleware($auth)->group(function (): void {
                Route::post('ad/{ad}/favourite', [AdActionController::class, 'favourite'])->whereNumber('ad')->name('favourite');
                Route::post('ad/{ad}/message', [AdActionController::class, 'message'])->whereNumber('ad')->name('message');
                Route::post('searches', [AdActionController::class, 'saveSearch'])->name('searches.store');

                Route::prefix('my')->name('cabinet.')->group(function (): void {
                    Route::get('/', [CabinetController::class, 'ads'])->name('ads');
                    Route::get('new', [CabinetController::class, 'create'])->name('create');
                    Route::post('new', [CabinetController::class, 'store'])->name('store');
                    Route::get('{ad}/edit', [CabinetController::class, 'edit'])->whereNumber('ad')->name('edit');
                    Route::put('{ad}', [CabinetController::class, 'update'])->whereNumber('ad')->name('update');
                    Route::delete('{ad}', [CabinetController::class, 'destroy'])->whereNumber('ad')->name('destroy');
                    Route::post('{ad}/submit', [CabinetController::class, 'submit'])->whereNumber('ad')->name('submit');
                    Route::post('{ad}/sold', [CabinetController::class, 'sold'])->whereNumber('ad')->name('sold');
                    Route::post('{ad}/renew', [CabinetController::class, 'renew'])->whereNumber('ad')->name('renew');
                    Route::post('{ad}/boost', [CabinetController::class, 'boost'])->whereNumber('ad')->name('boost');
                    Route::get('favourites', [CabinetController::class, 'favourites'])->name('favourites');
                    Route::get('messages', [CabinetController::class, 'messages'])->name('messages');
                    Route::get('messages/{ad}/{buyer}', [CabinetController::class, 'thread'])->whereNumber(['ad', 'buyer'])->name('thread');
                    Route::post('messages/{ad}/{buyer}', [CabinetController::class, 'reply'])->whereNumber(['ad', 'buyer'])->name('reply');
                    Route::get('searches', [CabinetController::class, 'searches'])->name('searches');
                    Route::patch('searches/{search}', [CabinetController::class, 'toggleSearch'])->whereNumber('search')->name('searches.update');
                    Route::delete('searches/{search}', [CabinetController::class, 'destroySearch'])->whereNumber('search')->name('searches.destroy');
                });
            });

            Route::get('{path}', [BoardController::class, 'category'])->where('path', '[a-z0-9\-]+(/[a-z0-9\-]+)*')->name('category');
        });
    });
});
