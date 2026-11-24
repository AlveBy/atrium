<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Modules\Modules;
use Atrium\Bitrix24\Http\Middleware\InjectWidget;
use Atrium\Bitrix24\Jobs\SendSubmission;
use Atrium\Bitrix24\Models\FormLink;
use Atrium\Bitrix24\Support\Client;
use Atrium\Forms\Models\FormSubmission;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Support\ServiceProvider;

/**
 * Bitrix24 for Atrium: a form submission is sent to the portal through the
 * incoming webhook of the settings, and the chat widget code of the settings
 * goes onto the pages of the site.
 */
final class Bitrix24ServiceProvider extends ServiceProvider
{
    public const MODULE = 'alveby/atrium-bitrix24';

    public function register(): void
    {
        $this->app->singleton(Client::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'atrium-bitrix24');

        $this->app->make(HttpKernel::class)->appendMiddlewareToGroup('web', InjectWidget::class);

        // After the submission is stored: a portal that is down loses nothing.
        FormSubmission::created(static function (FormSubmission $submission): void {
            if (! app(Modules::class)->isInstalled(self::MODULE) || ! Setting::get('bitrix24.send_forms') || app(Client::class)->webhook() === null) {
                return;
            }

            if (FormLink::query()->where('form_id', $submission->form_id)->where('active', false)->exists()) {
                return;
            }

            SendSubmission::dispatch($submission->id)->afterCommit();
        });
    }
}
