<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Models;

/**
 * Why a task was closed: done, will not be done, a duplicate.
 */
final class Resolution extends Term
{
    protected $table = 'tracker_resolutions';

    public static function taskColumn(): string
    {
        return 'resolution';
    }

    public static function group(): string
    {
        return 'resolutions';
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-tracker::tracker.terms.resolutions');
    }

    public static function getAdministrationFields(): array
    {
        return self::commonFields();
    }
}
