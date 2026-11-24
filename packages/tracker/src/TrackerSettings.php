<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker;

use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\RepeatableField;
use Atrium\Admin\Modules\ModuleSettings;
use Atrium\Tracker\Support\Statuses;

/**
 * What the site's owner sets for the tracker: the statuses of a task and
 * which letters are sent. One set for all sites: a team works in one tracker.
 */
final class TrackerSettings extends ModuleSettings
{
    public function description(): string
    {
        return __('atrium-tracker::tracker.settings.description');
    }

    public function fields(): array
    {
        return [
            RepeatableField::make('statuses')->label(__('atrium-tracker::tracker.settings.statuses'))
                ->hint(__('atrium-tracker::tracker.settings.statuses_hint', ['default' => implode(' / ', array_map(Statuses::label(...), Statuses::BUILT_IN))]))
                ->itemType('text')
                ->maxItems(15)
                ->addLabel(__('atrium-tracker::tracker.settings.add_status')),
            CheckboxField::make('notify_assigned')->label(__('atrium-tracker::tracker.settings.notify_assigned'))
                ->hint(__('atrium-tracker::tracker.settings.notify_assigned_hint'))
                ->default(true),
            CheckboxField::make('notify_status')->label(__('atrium-tracker::tracker.settings.notify_status'))
                ->hint(__('atrium-tracker::tracker.settings.notify_status_hint'))
                ->default(true),
        ];
    }
}
