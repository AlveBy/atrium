<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

/** How names of fields and functions are matched: whatever their case and spacing. */
final class Names
{
    public static function normalise(string $name): string
    {
        return mb_strtolower(self::tidy($name));
    }

    /** One space between words, none around. */
    public static function tidy(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }
}
