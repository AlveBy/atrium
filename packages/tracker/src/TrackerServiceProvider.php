<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Modules\Modules;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Plugins\Plugins;
use Illuminate\Support\ServiceProvider;

/**
 * The models, the migration, the settings and the demo come from the manifest
 * in composer.json once `atrium:module:install alveby/atrium-tracker` has run;
 * this loads the texts and the views, adds the "My tasks" button and holds
 * the registry of the plugins.
 */
final class TrackerServiceProvider extends ServiceProvider
{
    public const MODULE = 'alveby/atrium-tracker';

    public function register(): void
    {
        $this->app->singleton(Plugins::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-tracker');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'atrium-tracker');

        Hook::filter('atrium.module.links', static function (array $links, string $model): array {
            if ($model !== Task::class || ! app(Modules::class)->isInstalled(self::MODULE)) {
                return $links;
            }

            return [...$links, [
                'name' => 'my-tasks',
                'label' => __('atrium-tracker::tracker.my_tasks'),
                'href' => route(Task::getAdminRouteName().'.index', ['mine' => 1]),
            ]];
        });
    }
}
