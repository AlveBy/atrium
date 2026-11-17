<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Tests\Unit;

use Atrium\ImageMap\Geometry\Geometry;
use Atrium\ImageMap\Shape;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GeometryTest extends TestCase
{
    /**
     * @return iterable<string,array{0:Shape,1:mixed}>
     */
    public static function validShapes(): iterable
    {
        yield 'rectangle' => [Shape::Rect, ['x' => 10, 'y' => 20, 'width' => 300, 'height' => 120]];
        yield 'rectangle touching the edges' => [Shape::Rect, ['x' => 0, 'y' => 0, 'width' => 1000, 'height' => 600]];
        yield 'rectangle as strings' => [Shape::Rect, ['x' => '10.5', 'y' => '0', 'width' => '20', 'height' => '20']];
        yield 'circle' => [Shape::Circle, ['cx' => 500, 'cy' => 300, 'r' => 120]];
        yield 'point in a corner' => [Shape::Point, ['x' => 1000, 'y' => 600]];
        yield 'triangle' => [Shape::Polygon, ['points' => [[10, 10], [200, 10], [100, 150]]]];
        yield 'concave L' => [Shape::Polygon, ['points' => [[720, 480], [1000, 480], [1000, 590], [900, 590], [900, 520], [720, 520]]]];
        yield 'clockwise square' => [Shape::Polygon, ['points' => [[0, 0], [0, 100], [100, 100], [100, 0]]]];
    }

    #[DataProvider('validShapes')]
    public function test_good_shapes_pass(Shape $shape, mixed $data): void
    {
        $this->assertSame([], Geometry::check($shape, $data, 1000, 600));
    }

    /**
     * @return iterable<string,array{0:Shape,1:mixed,2:string}>
     */
    public static function invalidShapes(): iterable
    {
        yield 'not an array' => [Shape::Rect, 'x=1', 'geometry.invalid'];
        yield 'missing key' => [Shape::Rect, ['x' => 1, 'y' => 1, 'width' => 10], 'geometry.invalid'];
        yield 'text instead of a number' => [Shape::Circle, ['cx' => 'ten', 'cy' => 1, 'r' => 4], 'geometry.invalid'];
        yield 'infinite' => [Shape::Point, ['x' => INF, 'y' => 1], 'geometry.invalid'];
        yield 'not a number' => [Shape::Point, ['x' => NAN, 'y' => 1], 'geometry.invalid'];
        yield 'boolean' => [Shape::Point, ['x' => true, 'y' => 1], 'geometry.invalid'];
        yield 'points not a list' => [Shape::Polygon, ['points' => 'a,b'], 'geometry.invalid'];
        yield 'point of one number' => [Shape::Polygon, ['points' => [[1, 1], [5], [9, 9]]], 'geometry.invalid'];
        yield 'flat rectangle' => [Shape::Rect, ['x' => 1, 'y' => 1, 'width' => 300, 'height' => 1], 'geometry.too_small'];
        yield 'negative width' => [Shape::Rect, ['x' => 100, 'y' => 1, 'width' => -50, 'height' => 40], 'geometry.too_small'];
        yield 'tiny circle' => [Shape::Circle, ['cx' => 10, 'cy' => 10, 'r' => 1], 'geometry.too_small'];
        yield 'two points' => [Shape::Polygon, ['points' => [[1, 1], [50, 50]]], 'geometry.too_few_points'];
        yield 'repeated point' => [Shape::Polygon, ['points' => [[1, 1], [50, 1], [50, 1], [1, 50]]], 'geometry.repeated_point'];
        yield 'all on a line folds back on itself' => [Shape::Polygon, ['points' => [[0, 0], [50, 50], [100, 100]]], 'geometry.self_intersecting'];
        yield 'bow tie' => [Shape::Polygon, ['points' => [[0, 0], [100, 100], [100, 0], [0, 100]]], 'geometry.self_intersecting'];
        yield 'star drawn in one stroke' => [Shape::Polygon, ['points' => [[50, 0], [80, 100], [0, 35], [100, 35], [20, 100]]], 'geometry.self_intersecting'];
        yield 'spike back along a side' => [Shape::Polygon, ['points' => [[0, 0], [100, 0], [50, 0], [50, 80]]], 'geometry.self_intersecting'];
        yield 'side touching a corner' => [Shape::Polygon, ['points' => [[0, 0], [100, 0], [100, 100], [50, 0], [0, 100]]], 'geometry.self_intersecting'];
        yield 'rectangle past the right edge' => [Shape::Rect, ['x' => 900, 'y' => 10, 'width' => 101, 'height' => 20], 'geometry.outside'];
        yield 'circle over the top' => [Shape::Circle, ['cx' => 50, 'cy' => 10, 'r' => 20], 'geometry.outside'];
        yield 'point below' => [Shape::Point, ['x' => 5, 'y' => 601], 'geometry.outside'];
        yield 'polygon with a negative corner' => [Shape::Polygon, ['points' => [[-1, 10], [50, 10], [50, 60]]], 'geometry.outside'];
    }

    #[DataProvider('invalidShapes')]
    public function test_bad_shapes_are_rejected(Shape $shape, mixed $data, string $error): void
    {
        $this->assertContains($error, array_column(Geometry::check($shape, $data, 1000, 600), 0));
    }

    public function test_the_error_names_the_picture_size_and_the_point(): void
    {
        $this->assertSame([['geometry.outside', ['width' => 1000, 'height' => 600]]], Geometry::check(Shape::Point, ['x' => 1001, 'y' => 1], 1000, 600));
        $this->assertSame([['geometry.repeated_point', ['index' => 2]]], Geometry::check(Shape::Polygon, ['points' => [[1, 1], [9, 1], [9, 1], [1, 9]]], null, null));
    }

    public function test_without_a_picture_size_only_the_shape_is_checked(): void
    {
        $this->assertSame([], Geometry::check(Shape::Point, ['x' => 99999, 'y' => -5], null, null));
        $this->assertSame([], Geometry::check(Shape::Point, ['x' => 99999, 'y' => -5], 0, 0));
    }

    public function test_a_polygon_may_not_have_too_many_points(): void
    {
        $points = [];

        for ($i = 0; $i <= Geometry::MAX_POINTS; $i++) {
            $angle = 2 * M_PI * $i / (Geometry::MAX_POINTS + 1);
            $points[] = [500 + 200 * cos($angle), 300 + 200 * sin($angle)];
        }

        $this->assertSame([['geometry.too_many_points', ['max' => Geometry::MAX_POINTS]]], Geometry::check(Shape::Polygon, ['points' => $points], 1000, 600));
        $this->assertSame([], Geometry::check(Shape::Polygon, ['points' => array_slice($points, 0, Geometry::MAX_POINTS)], 1000, 600));
    }

    public function test_normalize_keeps_known_keys_and_rounds(): void
    {
        $this->assertSame(
            ['x' => 10.1, 'y' => 20.0, 'width' => 30.6, 'height' => 40.0],
            Geometry::normalize(Shape::Rect, ['x' => '10.123', 'y' => 20, 'width' => 30.55, 'height' => 40, 'onclick' => 'x']),
        );
        $this->assertSame(['points' => [[1.0, 2.0], [3.5, 4.0], [5.0, 6.1]]], Geometry::normalize(Shape::Polygon, ['points' => [[1, 2], [3.49, 4], [5, 6.06]], 'extra' => 1]));
        $this->assertSame(['cx' => 1.0, 'cy' => 2.0, 'r' => 3.0], Geometry::normalize(Shape::Circle, ['cx' => 1, 'cy' => 2, 'r' => 3]));
        $this->assertSame(['x' => 4.0, 'y' => 5.0], Geometry::normalize(Shape::Point, ['x' => 4, 'y' => 5]));
    }

    public function test_bounds_and_centre(): void
    {
        $this->assertSame([10.0, 20.0, 110.0, 70.0], Geometry::bounds(Shape::Rect, ['x' => 10, 'y' => 20, 'width' => 100, 'height' => 50]));
        $this->assertSame([40.0, 40.0, 60.0, 60.0], Geometry::bounds(Shape::Circle, ['cx' => 50, 'cy' => 50, 'r' => 10]));
        $this->assertSame([5.0, 6.0, 5.0, 6.0], Geometry::bounds(Shape::Point, ['x' => 5, 'y' => 6]));
        $this->assertSame([0.0, 0.0, 100.0, 50.0], Geometry::bounds(Shape::Polygon, ['points' => [[0, 0], [100, 0], [100, 50]]]));

        $this->assertSame([60.0, 45.0], Geometry::center(Shape::Rect, ['x' => 10, 'y' => 20, 'width' => 100, 'height' => 50]));
        $this->assertSame([50.0, 50.0], Geometry::center(Shape::Circle, ['cx' => 50, 'cy' => 50, 'r' => 10]));
        $this->assertSame([5.0, 6.0], Geometry::center(Shape::Point, ['x' => 5, 'y' => 6]));
        [$cx, $cy] = Geometry::center(Shape::Polygon, ['points' => [[0, 0], [90, 0], [0, 90]]]);
        $this->assertEqualsWithDelta(30.0, $cx, 0.001);
        $this->assertEqualsWithDelta(30.0, $cy, 0.001);
    }

    public function test_self_intersection_and_area(): void
    {
        $this->assertTrue(Geometry::selfIntersects([[0, 0], [10, 10], [10, 0], [0, 10]]));
        $this->assertFalse(Geometry::selfIntersects([[0, 0], [10, 0], [10, 10], [0, 10]]));
        $this->assertEqualsWithDelta(200.0, abs(Geometry::doubleArea([[0, 0], [10, 0], [10, 10], [0, 10]])), 0.001);
    }
}
