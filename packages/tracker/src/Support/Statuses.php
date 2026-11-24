<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Support;

use Atrium\Admin\Facades\Setting;

/**
 * The statuses a task goes through, in order: the first is where a new task
 * starts, the last is "done" and closes it. Without a list in the module's
 * settings they are the four built-in ones, named in the administrator's
 * language; with one, each line of it is a status and its own name.
 */
final class Statuses
{
    /** @var list<string> */
    public const BUILT_IN = ['todo', 'in_progress', 'review', 'done'];

    /**
     * Status => name, in order.
     *
     * @return array<string,string>
     */
    public static function options(): array
    {
        $names = self::custom() ?: self::BUILT_IN;

        return array_combine($names, array_map(self::label(...), $names));
    }

    public static function first(): string
    {
        return (string) array_key_first(self::options());
    }

    public static function done(): string
    {
        return (string) array_key_last(self::options());
    }

    /**
     * The name of a status. One that is no longer in the list (it was
     * renamed in the settings) is shown as it was stored.
     */
    public static function label(string $status): string
    {
        return in_array($status, self::BUILT_IN, true) ? __('atrium-tracker::tracker.statuses.'.$status) : $status;
    }

    /**
     * @return list<string>
     */
    private static function custom(): array
    {
        $lines = Setting::get('tracker.statuses', []);
        $statuses = [];

        foreach (is_array($lines) ? $lines : [] as $line) {
            $line = is_string($line) ? trim($line) : '';

            if ($line !== '' && ! in_array($line, $statuses, true)) {
                $statuses[] = $line;
            }
        }

        return $statuses;
    }
}
