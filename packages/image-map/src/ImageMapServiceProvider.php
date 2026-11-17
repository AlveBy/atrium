<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Modules\Modules;
use Atrium\ImageMap\Landing\ImageMapBlock;
use Atrium\ImageMap\Support\Assets;
use Atrium\ImageMap\Support\MapPresenter;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ImageMapServiceProvider extends ServiceProvider
{
    public const PACKAGE = 'alveby/atrium-image-map';

    /**
     * The models, the permissions and the migrations come from extra.atrium
     * in composer.json, once `atrium:module:install alveby/atrium-image-map`
     * has run.
     */
    public function register(): void
    {
        $this->app->scoped(Assets::class);
        $this->app->singleton(MapPresenter::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-image-map');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'atrium-image-map');

        Blade::componentNamespace('Atrium\\ImageMap\\View\\Components', 'atrium-image-map');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->make(Modules::class)->isInstalled(self::PACKAGE)) {
            Route::prefix(config('atrium.prefix', 'admin'))
                ->middleware([...config('atrium.middleware', ['web']), 'atrium.inertia', 'admin.auth', 'admin.can'])
                ->group(__DIR__.'/../routes/admin.php');

            // A block of the landing builder, when that module is there.
            Hook::filter('atrium.landing.blocks', fn (array $blocks): array => [...$blocks, ImageMapBlock::class]);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/atrium-image-map'),
            ], 'atrium-image-map-views');

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/atrium-image-map'),
            ], 'atrium-image-map-lang');
        }
    }
}
