<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

use Atrium\Admin\Hooks\Hooks;
use Atrium\Language\Functions\Functions;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class LanguageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Atrium's core is optional here: with it, modules add functions with
        // Hook::register(Functions::REGISTRY, ...). They are asked for when a
        // name is looked up, so it does not matter which module boots first.
        $this->app->singleton(Functions::class, static fn (Application $app): Functions => Functions::standard(
            static fn (): array => $app->bound(Hooks::class)
                ? $app->make(Hooks::class)->registered(Functions::REGISTRY)
                : [],
        ));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-language');
    }
}
