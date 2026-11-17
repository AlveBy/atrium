<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Geometry;

use Atrium\ImageMap\Shape;

/**
 * The shape of an area, in pixels of the layer's picture, origin at the top
 * left:
 *
 *     rect     {"x": 10, "y": 20, "width": 200, "height": 80}
 *     circle   {"cx": 300, "cy": 200, "r": 40}
 *     point    {"x": 120, "y": 340}
 *     polygon  {"points": [[10, 10], [200, 10], [120, 160]]}
 *
 * check() says what is wrong with a shape: not a number, outside the
 * picture, too small, a polygon that crosses itself. normalize() rounds the
 * numbers and drops what does not belong. Both the editor and the admin
 * form (and the API, which saves like the form) go through here.
 */
final class Geometry
{
    public const MAX_POINTS = 500;

    /** Smaller than this (in px or px²) is a slip of the mouse, not an area. */
    public const MIN_SIZE = 2.0;

    private const EPSILON = 1e-9;

    /**
     * What is wrong with the shape, as translation keys with their
     * replacements; empty when it is fine.
     *
     * @return array<int,array{0:string,1:array<string,int|float|string>}>
     */
    public static function check(Shape $shape, mixed $data, ?float $width, ?float $height): array
    {
        if (! is_array($data)) {
            return [['geometry.invalid', []]];
        }

        $numbers = self::numbers($shape, $data);

        if ($numbers === null) {
            return [['geometry.invalid', []]];
        }

        $errors = [];

        if ($shape === Shape::Polygon) {
            /** @var array<int,array{0:float,1:float}> $numbers */
            $errors = self::checkPolygon($numbers);
        } elseif ($shape === Shape::Rect && ($numbers['width'] < self::MIN_SIZE || $numbers['height'] < self::MIN_SIZE)) {
            $errors[] = ['geometry.too_small', []];
        } elseif ($shape === Shape::Circle && $numbers['r'] < self::MIN_SIZE) {
            $errors[] = ['geometry.too_small', []];
        }

        if ($width !== null && $height !== null && $width > 0 && $height > 0) {
            [$minX, $minY, $maxX, $maxY] = self::boundsOf($shape, $numbers);

            if ($minX < -self::EPSILON || $minY < -self::EPSILON || $maxX > $width + self::EPSILON || $maxY > $height + self::EPSILON) {
                $errors[] = ['geometry.outside', ['width' => (int) $width, 'height' => (int) $height]];
            }
        }

        return $errors;
    }

    /**
     * The shape with only its own keys and numbers rounded to a tenth of a
     * pixel. Call it after check() passed.
     *
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    public static function normalize(Shape $shape, array $data): array
    {
        $numbers = self::numbers($shape, $data) ?? [];

        if ($shape === Shape::Polygon) {
            return ['points' => array_map(
                static fn (array $point): array => [self::round($point[0]), self::round($point[1])],
                $numbers,
            )];
        }

        return array_map(self::round(...), $numbers);
    }

    /**
     * [minX, minY, maxX, maxY] of a shape that passed check().
     *
     * @param  array<string,mixed>  $data
     * @return array{0:float,1:float,2:float,3:float}
     */
    public static function bounds(Shape $shape, array $data): array
    {
        return self::boundsOf($shape, self::numbers($shape, $data) ?? []);
    }

    /**
     * The middle of the shape: where a popover opened from the list points,
     * and how areas are ordered for the keyboard (top to bottom, then left
     * to right).
     *
     * @param  array<string,mixed>  $data
     * @return array{0:float,1:float}
     */
    public static function center(Shape $shape, array $data): array
    {
        $numbers = self::numbers($shape, $data) ?? [];

        return match ($shape) {
            Shape::Rect => [$numbers['x'] + $numbers['width'] / 2, $numbers['y'] + $numbers['height'] / 2],
            Shape::Circle => [$numbers['cx'], $numbers['cy']],
            Shape::Point => [$numbers['x'], $numbers['y']],
            Shape::Polygon => self::polygonCenter($numbers),
        };
    }

    /**
     * Twice the signed area of a polygon (shoelace formula).
     *
     * @param  array<int,array{0:float,1:float}>  $points
     */
    public static function doubleArea(array $points): float
    {
        $sum = 0.0;
        $count = count($points);

        for ($i = 0; $i < $count; $i++) {
            [$x1, $y1] = $points[$i];
            [$x2, $y2] = $points[($i + 1) % $count];
            $sum += $x1 * $y2 - $x2 * $y1;
        }

        return $sum;
    }

    /**
     * Whether a closed polygon crosses or touches itself: two edges that
     * meet anywhere but at the vertex they share, or an edge that doubles
     * back on the one before it.
     *
     * @param  array<int,array{0:float,1:float}>  $points
     */
    public static function selfIntersects(array $points): bool
    {
        $count = count($points);

        for ($i = 0; $i < $count; $i++) {
            $a = $points[$i];
            $b = $points[($i + 1) % $count];

            for ($j = $i + 1; $j < $count; $j++) {
                $c = $points[$j];
                $d = $points[($j + 1) % $count];

                $adjacent = $j === $i + 1 || ($i === 0 && $j === $count - 1);

                if ($adjacent) {
                    // They share a vertex; they are wrong only when they lie
                    // on one line and overlap (a spike going back).
                    $shared = $j === $i + 1 ? $b : $a;
                    $p = $j === $i + 1 ? $a : $b;
                    $q = $j === $i + 1 ? $d : $c;

                    if (abs(self::cross($shared, $p, $q)) <= self::EPSILON
                        && (($p[0] - $shared[0]) * ($q[0] - $shared[0]) + ($p[1] - $shared[1]) * ($q[1] - $shared[1])) > 0) {
                        return true;
                    }

                    continue;
                }

                if (self::segmentsTouch($a, $b, $c, $d)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<int,array{0:float,1:float}>  $points
     * @return array<int,array{0:string,1:array<string,int|float|string>}>
     */
    private static function checkPolygon(array $points): array
    {
        $count = count($points);

        if ($count < 3) {
            return [['geometry.too_few_points', ['min' => 3]]];
        }

        if ($count > self::MAX_POINTS) {
            return [['geometry.too_many_points', ['max' => self::MAX_POINTS]]];
        }

        for ($i = 0; $i < $count; $i++) {
            $next = $points[($i + 1) % $count];

            if (abs($points[$i][0] - $next[0]) <= self::EPSILON && abs($points[$i][1] - $next[1]) <= self::EPSILON) {
                return [['geometry.repeated_point', ['index' => $i + 1]]];
            }
        }

        if (self::selfIntersects($points)) {
            return [['geometry.self_intersecting', []]];
        }

        if (abs(self::doubleArea($points)) / 2 < self::MIN_SIZE) {
            return [['geometry.too_small', []]];
        }

        return [];
    }

    /**
     * The shape's numbers as floats, or null when one is missing or is not a
     * finite number.
     *
     * @param  array<mixed>  $data
     * @return array<string,float>|array<int,array{0:float,1:float}>|null
     */
    private static function numbers(Shape $shape, array $data): ?array
    {
        if ($shape === Shape::Polygon) {
            $points = $data['points'] ?? null;

            if (! is_array($points) || ! array_is_list($points)) {
                return null;
            }

            $result = [];

            foreach ($points as $point) {
                if (! is_array($point) || ! array_is_list($point) || count($point) !== 2) {
                    return null;
                }

                $x = self::number($point[0]);
                $y = self::number($point[1]);

                if ($x === null || $y === null) {
                    return null;
                }

                $result[] = [$x, $y];
            }

            return $result;
        }

        $keys = match ($shape) {
            Shape::Rect => ['x', 'y', 'width', 'height'],
            Shape::Circle => ['cx', 'cy', 'r'],
            Shape::Point => ['x', 'y'],
        };

        $result = [];

        foreach ($keys as $key) {
            $value = self::number($data[$key] ?? null);

            if ($value === null) {
                return null;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private static function number(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            $value = (float) $value;
        } elseif (is_string($value) && is_numeric(trim($value))) {
            $value = (float) trim($value);
        } else {
            return null;
        }

        return is_finite($value) ? $value : null;
    }

    /**
     * @param  array<mixed>  $numbers
     * @return array{0:float,1:float,2:float,3:float}
     */
    private static function boundsOf(Shape $shape, array $numbers): array
    {
        return match ($shape) {
            Shape::Rect => [$numbers['x'], $numbers['y'], $numbers['x'] + $numbers['width'], $numbers['y'] + $numbers['height']],
            Shape::Circle => [$numbers['cx'] - $numbers['r'], $numbers['cy'] - $numbers['r'], $numbers['cx'] + $numbers['r'], $numbers['cy'] + $numbers['r']],
            Shape::Point => [$numbers['x'], $numbers['y'], $numbers['x'], $numbers['y']],
            Shape::Polygon => [
                min(array_column($numbers, 0)), min(array_column($numbers, 1)),
                max(array_column($numbers, 0)), max(array_column($numbers, 1)),
            ],
        };
    }

    /**
     * The centroid of a simple polygon, or the middle of its vertices when
     * it has no area.
     *
     * @param  array<int,array{0:float,1:float}>  $points
     * @return array{0:float,1:float}
     */
    private static function polygonCenter(array $points): array
    {
        $doubleArea = self::doubleArea($points);
        $count = count($points);

        if (abs($doubleArea) <= self::EPSILON) {
            return [array_sum(array_column($points, 0)) / max(1, $count), array_sum(array_column($points, 1)) / max(1, $count)];
        }

        $cx = 0.0;
        $cy = 0.0;

        for ($i = 0; $i < $count; $i++) {
            [$x1, $y1] = $points[$i];
            [$x2, $y2] = $points[($i + 1) % $count];
            $factor = $x1 * $y2 - $x2 * $y1;
            $cx += ($x1 + $x2) * $factor;
            $cy += ($y1 + $y2) * $factor;
        }

        return [$cx / (3 * $doubleArea), $cy / (3 * $doubleArea)];
    }

    /**
     * @param  array{0:float,1:float}  $o
     * @param  array{0:float,1:float}  $a
     * @param  array{0:float,1:float}  $b
     */
    private static function cross(array $o, array $a, array $b): float
    {
        return ($a[0] - $o[0]) * ($b[1] - $o[1]) - ($a[1] - $o[1]) * ($b[0] - $o[0]);
    }

    /**
     * Whether segments ab and cd have any point in common.
     *
     * @param  array{0:float,1:float}  $a
     * @param  array{0:float,1:float}  $b
     * @param  array{0:float,1:float}  $c
     * @param  array{0:float,1:float}  $d
     */
    private static function segmentsTouch(array $a, array $b, array $c, array $d): bool
    {
        $d1 = self::cross($c, $d, $a);
        $d2 = self::cross($c, $d, $b);
        $d3 = self::cross($a, $b, $c);
        $d4 = self::cross($a, $b, $d);

        if ((($d1 > self::EPSILON && $d2 < -self::EPSILON) || ($d1 < -self::EPSILON && $d2 > self::EPSILON))
            && (($d3 > self::EPSILON && $d4 < -self::EPSILON) || ($d3 < -self::EPSILON && $d4 > self::EPSILON))) {
            return true;
        }

        return (abs($d1) <= self::EPSILON && self::onSegment($c, $d, $a))
            || (abs($d2) <= self::EPSILON && self::onSegment($c, $d, $b))
            || (abs($d3) <= self::EPSILON && self::onSegment($a, $b, $c))
            || (abs($d4) <= self::EPSILON && self::onSegment($a, $b, $d));
    }

    /**
     * Whether p, already on the line through a and b, lies between them.
     *
     * @param  array{0:float,1:float}  $a
     * @param  array{0:float,1:float}  $b
     * @param  array{0:float,1:float}  $p
     */
    private static function onSegment(array $a, array $b, array $p): bool
    {
        return $p[0] >= min($a[0], $b[0]) - self::EPSILON && $p[0] <= max($a[0], $b[0]) + self::EPSILON
            && $p[1] >= min($a[1], $b[1]) - self::EPSILON && $p[1] <= max($a[1], $b[1]) + self::EPSILON;
    }

    private static function round(float $value): float
    {
        return round($value, 1);
    }
}
