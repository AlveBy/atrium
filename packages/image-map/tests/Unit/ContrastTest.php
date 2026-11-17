<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Tests\Unit;

use Atrium\ImageMap\Support\Contrast;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContrastTest extends TestCase
{
    /**
     * The demo statuses and the colours of the styles, against white.
     *
     * @return iterable<string,array{0:string,1:float}>
     */
    public static function colours(): iterable
    {
        yield 'black' => ['#000000', 21.0];
        yield 'white' => ['#fff', 1.0];
        yield 'free, bluish green' => ['#009E73', 3.42];
        yield 'booked, vermilion' => ['#D55E00', 3.87];
        yield 'sold, grey' => ['#5A5A5A', 6.90];
        yield 'for lease, blue' => ['#0072B2', 5.19];
        yield 'orange is too light' => ['#E69F00', 2.25];
        yield 'text' => ['#1a1a1a', 17.4];
        yield 'muted text' => ['#4d4d4d', 8.45];
        yield 'borders' => ['#767676', 4.54];
        yield 'save button' => ['#005a9c', 7.14];
        yield 'errors' => ['#b3261e', 6.54];
    }

    #[DataProvider('colours')]
    public function test_ratio_against_white(string $colour, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, Contrast::ratio($colour, '#ffffff'), 0.01);
        $this->assertEqualsWithDelta($expected, Contrast::ratio('#ffffff', $colour), 0.01, 'The order does not matter');
    }

    public function test_colours_are_read_in_any_usual_form(): void
    {
        $this->assertSame([0, 114, 178], Contrast::rgb('#0072B2'));
        $this->assertSame([0, 114, 178], Contrast::rgb('0072b2'));
        $this->assertSame([255, 0, 0], Contrast::rgb('#f00'));
        $this->assertSame([0, 0, 0], Contrast::rgb('not a colour'));
        $this->assertSame([0, 0, 0], Contrast::rgb('#12345'));
    }

    public function test_text_on_a_colour_is_black_or_white_whichever_reads_better(): void
    {
        $this->assertSame('#ffffff', Contrast::textOn('#0072B2'));
        $this->assertSame('#000000', Contrast::textOn('#E69F00'));
    }
}
