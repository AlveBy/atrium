<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Models;

use Atrium\Admin\Fields\SelectField;

/**
 * Where a task is in its work. The category says what the status means
 * whatever it is called: to do, being worked on, or done.
 *
 * @property string $category
 */
final class Status extends Term
{
    public const TODO = 'todo';

    public const ACTIVE = 'active';

    public const DONE = 'done';

    protected $table = 'tracker_statuses';

    public static function taskColumn(): string
    {
        return 'status';
    }

    public static function group(): string
    {
        return 'statuses';
    }

    /**
     * The codes of the statuses that close a task.
     *
     * @return list<string>
     */
    public static function doneCodes(): array
    {
        return array_keys(array_filter(self::terms(), static fn (self $status): bool => $status->category === self::DONE));
    }

    public static function isDone(?string $code): bool
    {
        return in_array($code, self::doneCodes(), true);
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-tracker::tracker.terms.statuses');
    }

    public static function getAdministrationFields(): array
    {
        return [
            ...self::commonFields(),
            SelectField::make('category')->label(__('atrium-tracker::tracker.terms.fields.category'))
                ->options([
                    self::TODO => __('atrium-tracker::tracker.terms.categories.todo'),
                    self::ACTIVE => __('atrium-tracker::tracker.terms.categories.active'),
                    self::DONE => __('atrium-tracker::tracker.terms.categories.done'),
                ])->default(self::ACTIVE)->required(),
        ];
    }
}
