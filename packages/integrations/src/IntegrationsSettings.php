<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations;

use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Modules\ModuleSettings;

/**
 * Who is told when a connector fails and how long its log is kept. One set
 * for all sites: an exchange belongs to the server.
 */
final class IntegrationsSettings extends ModuleSettings
{
    public function description(): string
    {
        return __('atrium-integrations::integrations.settings.description');
    }

    public function fields(): array
    {
        return [
            TextareaField::make('alert_emails')->label(__('atrium-integrations::integrations.settings.alert_emails'))
                ->hint(__('atrium-integrations::integrations.settings.alert_emails_hint'))
                ->placeholder('ops@example.com')
                ->rules(['max:2000']),
            NumberField::make('retention_days')->label(__('atrium-integrations::integrations.settings.retention_days'))
                ->hint(__('atrium-integrations::integrations.settings.retention_days_hint'))
                ->default((int) config('atrium-integrations.retention_days'))
                ->rules(['integer', 'min:1', 'max:3650']),
        ];
    }
}
