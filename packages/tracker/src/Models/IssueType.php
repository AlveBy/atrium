<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Models;

use Atrium\Admin\Fields\CheckboxField;

/**
 * What kind of work a task is: a task, a bug, an epic, a sub-task or a type of
 * your own. A type may stand at the top (it has no parent: an epic) or need a
 * parent (a sub-task).
 *
 * @property bool $top_level
 * @property bool $needs_parent
 */
final class IssueType extends Term
{
    protected $table = 'tracker_issue_types';

    protected function casts(): array
    {
        return [...parent::casts(), 'top_level' => 'boolean', 'needs_parent' => 'boolean'];
    }

    public static function taskColumn(): string
    {
        return 'type';
    }

    public static function group(): string
    {
        return 'types';
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-tracker::tracker.terms.types');
    }

    public static function getAdministrationFields(): array
    {
        return [
            ...self::commonFields(),
            CheckboxField::make('top_level')->label(__('atrium-tracker::tracker.terms.fields.top_level'))->hint(__('atrium-tracker::tracker.terms.fields.top_level_hint'))->default(false),
            CheckboxField::make('needs_parent')->label(__('atrium-tracker::tracker.terms.fields.needs_parent'))->hint(__('atrium-tracker::tracker.terms.fields.needs_parent_hint'))->default(false),
        ];
    }
}
