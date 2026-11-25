<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker;

use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\MultiSelectField;
use Atrium\Admin\Modules\ModuleSettings;
use Atrium\Tracker\Plugins\Plugins;

/**
 * What the site's owner sets for the tracker: which letters
 * are sent. One set for all sites: a team works in one tracker.
 */
final class TrackerSettings extends ModuleSettings
{
    public function description(): string
    {
        return __('atrium-tracker::tracker.settings.description');
    }

    public function fields(): array
    {
        $fields = [
            CheckboxField::make('notify_assigned')->label(__('atrium-tracker::tracker.settings.notify_assigned'))
                ->hint(__('atrium-tracker::tracker.settings.notify_assigned_hint'))
                ->default(true),
            CheckboxField::make('notify_status')->label(__('atrium-tracker::tracker.settings.notify_status'))
                ->hint(__('atrium-tracker::tracker.settings.notify_status_hint'))
                ->default(true),
        ];

        $plugins = app(Plugins::class)->all();

        if ($plugins !== []) {
            $fields[] = MultiSelectField::make('disabled_plugins')->label(__('atrium-tracker::tracker.settings.disabled_plugins'))
                ->hint(__('atrium-tracker::tracker.settings.disabled_plugins_hint'))
                ->options(array_map(static fn ($plugin): string => $plugin->title(), $plugins));
        }

        return $fields;
    }
}
