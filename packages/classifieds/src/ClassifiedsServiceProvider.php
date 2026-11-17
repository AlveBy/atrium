<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds;

use Atrium\Admin\Modules\Modules;
use Atrium\Classifieds\Console\AlertsCommand;
use Atrium\Classifieds\Console\ExpireCommand;
use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Mail\StatusMail;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\ModerationQueue;
use Atrium\Classifieds\Support\Accounts;
use Atrium\Classifieds\Support\AdSearch;
use Atrium\Classifieds\Support\AdWorkflow;
use Atrium\Classifieds\Support\Alerts;
use Atrium\Classifieds\Support\Boosts;
use Atrium\Classifieds\Support\Cards;
use Atrium\Classifieds\Support\Conversations;
use Atrium\Classifieds\Support\Properties;
use Atrium\Classifieds\Support\Schema;
use Atrium\Classifieds\Support\StopWords;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

/**
 * The models, the migrations and the settings come from the manifest in
 * composer.json (`extra.atrium`) once `atrium:module:install
 * alveby/atrium-classifieds` has run; this adds the site, the cabinet,
 * the night jobs and the bridges to the modules that are there.
 */
final class ClassifiedsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/atrium-classifieds.php', 'atrium-classifieds');

        foreach ([Board::class, Schema::class, Accounts::class, StopWords::class, Properties::class, AdWorkflow::class, AdSearch::class, Cards::class, Boosts::class, Conversations::class, Alerts::class] as $service) {
            $this->app->singleton($service);
        }
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-classifieds');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'atrium-classifieds');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/atrium-classifieds.php' => config_path('atrium-classifieds.php')], 'atrium-classifieds-config');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/atrium-classifieds')], 'atrium-classifieds-views');
        }

        // Required but not installed: no tables yet, so nothing on the site.
        if (! $this->app->make(Modules::class)->isInstalled(Board::MODULE)) {
            return;
        }

        Integrations\Loader::boot($this->app);

        if ($this->app->runningInConsole()) {
            $this->commands([ExpireCommand::class, AlertsCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command('atrium:classifieds:expire')->hourly()->withoutOverlapping();
            $schedule->command('atrium:classifieds:alerts')->everyThirtyMinutes()->withoutOverlapping();
        });

        if (config('atrium-classifieds.routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        // The owner hears of the moderator's decision and of the end of
        // the term, wherever the status changed: the queue is its own class
        // for Eloquent, so it is listened to as well.
        $tell = static function (Ad $ad): void {
            if (! $ad->wasChanged('status') || ! in_array($ad->status, [AdStatus::Active, AdStatus::Rejected, AdStatus::Expired], true)) {
                return;
            }

            $before = $ad->getOriginal('status');

            // Renewing and editing a live ad are the owner's own doing.
            if ($ad->status === AdStatus::Active && $before !== AdStatus::Moderation) {
                return;
            }

            $email = app(Accounts::class)->email($ad->account_user_id);

            if ($email === null) {
                return;
            }

            try {
                Mail::to($email)->send(new StatusMail($ad, app(Board::class)->route('cabinet.ads')));
            } catch (\Throwable $e) {
                report($e);
            }
        };

        Ad::updated($tell);
        ModerationQueue::updated($tell);
    }
}
