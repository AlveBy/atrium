<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations;

use Atrium\Integrations\Console\PruneCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * The model, the migration and the settings come from the manifest in
 * composer.json once `atrium:module:install alveby/atrium-integrations` has
 * run; this loads the texts and the views and schedules the clean-up.
 */
final class IntegrationsServiceProvider extends ServiceProvider
{
    public const MODULE = 'alveby/atrium-integrations';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atrium-integrations.php', 'atrium-integrations');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-integrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'atrium-integrations');

        $this->publishes([
            __DIR__.'/../config/atrium-integrations.php' => config_path('atrium-integrations.php'),
        ], 'atrium-integrations-config');

        $this->commands([PruneCommand::class]);

        if (! config('atrium-integrations.schedule', true)) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('atrium:integrations:prune')->dailyAt('03:41')->withoutOverlapping(60);
        });
    }
}
