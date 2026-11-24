<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Enums;

enum TaskType: string
{
    case Task = 'task';
    case Bug = 'bug';
    case Story = 'story';
    case Epic = 'epic';

    public function label(): string
    {
        return __('atrium-tracker::tracker.types.'.$this->value);
    }

    /**
     * @return array<string,string> value => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
