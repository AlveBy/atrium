<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Support;

/**
 * WCAG contrast of two colours: from 1 (the same) to 21 (black on white).
 * The outline of an area is a graphic object, which needs at least 3:1
 * against what is behind it (WCAG 2.2, 1.4.11); text needs 4.5:1.
 */
final class Contrast
{
    public const GRAPHICS = 3.0;

    public const TEXT = 4.5;

    public static function ratio(string $first, string $second): float
    {
        $a = self::luminance($first);
        $b = self::luminance($second);

        return round((max($a, $b) + 0.05) / (min($a, $b) + 0.05), 2);
    }

    /**
     * Relative luminance of "#rrggbb" or "#rgb"; anything else reads as black.
     */
    public static function luminance(string $hex): float
    {
        $rgb = self::rgb($hex);

        $channel = static function (int $value): float {
            $c = $value / 255;

            return $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel($rgb[0]) + 0.7152 * $channel($rgb[1]) + 0.0722 * $channel($rgb[2]);
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    public static function rgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (preg_match('/^[0-9a-f]{3}$/i', $hex) === 1) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (preg_match('/^[0-9a-f]{6}$/i', $hex) !== 1) {
            return [0, 0, 0];
        }

        return [(int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2))];
    }

    /**
     * Black or white, whichever reads better on the colour.
     */
    public static function textOn(string $background): string
    {
        return self::ratio($background, '#000000') >= self::ratio($background, '#ffffff') ? '#000000' : '#ffffff';
    }
}
