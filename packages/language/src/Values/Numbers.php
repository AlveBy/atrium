<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Values;

use Atrium\Language\FormulaError;
use Atrium\Language\Position;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Numbers are exact decimals: 0.1 + 0.2 is 0.3. They are bounded, so that no
 * formula can grow one without end: SCALE places after the point, rounded
 * half away from zero, and DIGITS digits before it.
 */
final class Numbers
{
    public const SCALE = 20;

    public const DIGITS = 40;

    /** The longest text read as a number. */
    private const LENGTH = 100;

    public static function bounded(BigDecimal $number, ?Position $at = null): BigDecimal
    {
        $number = $number->toScale(self::SCALE, RoundingMode::HalfUp)->strippedOfTrailingZeros();

        if ($number->getPrecision() - $number->getScale() > self::DIGITS) {
            throw FormulaError::of('value.too_large', $at, ['digits' => self::DIGITS]);
        }

        return $number;
    }

    /** A number written as `-12.5`, or null for any other text. */
    public static function parse(string $text): ?BigDecimal
    {
        if (strlen($text) > self::LENGTH || preg_match('/^-?\d+(\.\d+)?\z/', $text) !== 1) {
            return null;
        }

        return BigDecimal::of($text);
    }

    /** The number as an integer, or null when it has a fraction or is outside the range. */
    public static function whole(BigDecimal $number, int $min, int $max): ?int
    {
        if ($number->strippedOfTrailingZeros()->getScale() > 0 || $number->compareTo($min) < 0 || $number->compareTo($max) > 0) {
            return null;
        }

        return $number->toInt();
    }
}
