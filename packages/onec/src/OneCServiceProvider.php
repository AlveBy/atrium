<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC;

use Atrium\Admin\Modules\Modules;
use Atrium\OneC\Http\ExchangeController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * 1C for Atrium: the address 1C:Enterprise calls with the standard CommerceML
 * exchange. The model, the migration and the settings come from the manifest
 * in composer.json once `atrium:module:install alveby/atrium-1c` has run;
 * until then the address is not there either.
 */
final class OneCServiceProvider extends ServiceProvider
{
    public const MODULE = 'alveby/atrium-1c';

    /** The address to give 1C. */
    public const PATH = 'atrium-1c/exchange';

    /** The address a 1C configuration that asks only for a site calls. */
    public const PATH_BITRIX = 'bitrix/admin/1c_exchange.php';

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-1c');

        $this->app->booted(function (): void {
            if ($this->app->routesAreCached() || ! app(Modules::class)->isInstalled(self::MODULE)) {
                return;
            }

            // No session and no CSRF token: 1C is not a browser, it keeps the cookie it is given.
            Route::match(['get', 'post'], self::PATH, ExchangeController::class)->name('atrium.onec.exchange');
            Route::match(['get', 'post'], self::PATH_BITRIX, ExchangeController::class)->name('atrium.onec.exchange.bitrix');
        });
    }
}
