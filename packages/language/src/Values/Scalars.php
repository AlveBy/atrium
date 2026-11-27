<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Values;

use Atrium\Language\Types\Type;
use Brick\Math\BigDecimal;

/** The single values: numbers, texts, booleans and dates. */
final class Scalars
{
    public static function type(): Type
    {
        return Type::oneOf(Type::number(), Type::text(), Type::boolean(), Type::date());
    }

    /** Whether there is nothing in a value: it is missing or an empty text. */
    public static function blank(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    /** How a value reads inside a text; a missing one adds nothing. */
    public static function text(BigDecimal|Date|string|bool|null $value): string
    {
        return match ($value) {
            true => 'TRUE',
            false => 'FALSE',
            default => (string) $value,
        };
    }
}
