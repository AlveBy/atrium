<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Unit;

use Atrium\Language\Position;
use Atrium\Language\Tests\TestCase;
use Atrium\Language\Types\Kind;
use Atrium\Language\Values\Date;
use Atrium\Language\Values\Numbers;
use Atrium\Language\Values\Scalars;
use Brick\Math\BigDecimal;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;

final class ValuesTest extends TestCase
{
    /** @return iterable<string,array{string,string}> */
    public static function bounded(): iterable
    {
        yield 'as it is' => ['12.5', '12.5'];
        yield 'no trailing zeros' => ['12.500', '12.5'];
        yield 'zero' => ['0.000', '0'];
        yield 'twenty places' => ['0.00000000000000000001', '0.00000000000000000001'];
        yield 'half goes away from zero' => ['0.000000000000000000005', '0.00000000000000000001'];
        yield 'half goes away from zero below it too' => ['-0.000000000000000000005', '-0.00000000000000000001'];
        yield 'less than half goes to zero' => ['0.0000000000000000000049', '0'];
        yield 'forty digits' => ['-'.str_repeat('9', 40).'.5', '-'.str_repeat('9', 40).'.5'];
        yield 'a large round number' => ['1'.str_repeat('0', 39), '1'.str_repeat('0', 39)];
    }

    #[DataProvider('bounded')]
    public function test_numbers_keep_twenty_places_and_forty_digits(string $number, string $bounded): void
    {
        $this->assertSame($bounded, (string) Numbers::bounded(BigDecimal::of($number)));
    }

    /** @return iterable<string,array{string}> */
    public static function tooLarge(): iterable
    {
        yield 'forty one digits' => ['1'.str_repeat('0', 40)];
        yield 'below zero' => ['-1'.str_repeat('0', 40)];
        yield 'rounding up to forty one' => [str_repeat('9', 40).'.999999999999999999999'];
        yield 'an exponent' => ['1e100'];
    }

    #[DataProvider('tooLarge')]
    public function test_a_larger_number_stops_the_run(string $number): void
    {
        $this->assertStops(
            'value.too_large',
            'Line 1, column 2: A number is too large: it has more than 40 digits before the decimal point.',
            fn () => Numbers::bounded(BigDecimal::of($number), new Position(1, 2)),
        );
    }

    /** @return iterable<string,array{string,?string}> */
    public static function written(): iterable
    {
        yield 'whole' => ['12', '12'];
        yield 'below zero' => ['-12.50', '-12.50'];
        yield 'a hundred characters' => [str_repeat('0', 99).'1', '1'];
        yield 'more than a hundred' => [str_repeat('0', 100).'1', null];
        yield 'a comma' => ['1,5', null];
        yield 'an exponent' => ['1e5', null];
        yield 'a plus' => ['+1', null];
        yield 'spaces' => [' 1', null];
        yield 'a line break after' => ["1\n", null];
        yield 'letters after' => ['1a', null];
        yield 'letters before' => ['a1', null];
        yield 'no digits after the point' => ['1.', null];
        yield 'no digits before the point' => ['.5', null];
        yield 'nothing' => ['', null];
    }

    #[DataProvider('written')]
    public function test_it_reads_a_written_number(string $text, ?string $number): void
    {
        $read = Numbers::parse($text);

        $this->assertSame($number, $read === null ? null : (string) $read);
    }

    /** @return iterable<string,array{string,int,int,?int}> */
    public static function wholes(): iterable
    {
        yield 'inside' => ['3', 1, 5, 3];
        yield 'the smallest' => ['1', 1, 5, 1];
        yield 'the largest' => ['5', 1, 5, 5];
        yield 'below' => ['0', 1, 5, null];
        yield 'above' => ['6', 1, 5, null];
        yield 'with zeros after the point' => ['3.000', 1, 5, 3];
        yield 'with a fraction' => ['3.5', 1, 5, null];
        yield 'far beyond an integer' => ['100000000000000000000000', 0, PHP_INT_MAX, null];
    }

    #[DataProvider('wholes')]
    public function test_it_takes_whole_numbers_of_a_range(string $number, int $min, int $max, ?int $whole): void
    {
        $this->assertSame($whole, Numbers::whole(BigDecimal::of($number), $min, $max));
    }

    /** @return iterable<string,array{string,?string}> */
    public static function days(): iterable
    {
        yield 'a day' => ['2026-11-28', '2026-11-28'];
        yield 'the first' => ['0001-01-01', '0001-01-01'];
        yield 'the last' => ['9999-12-31', '9999-12-31'];
        yield 'year zero' => ['0000-12-31', null];
        yield 'a leap day' => ['2028-02-29', '2028-02-29'];
        yield 'no leap day' => ['2026-02-29', null];
        yield 'no such month' => ['2026-13-01', null];
        yield 'short' => ['2026-1-1', null];
        yield 'with time' => ['2026-11-28 10:00', null];
        yield 'text before' => ['on 2026-11-28', null];
        yield 'a line break after' => ["2026-11-28\n", null];
        yield 'another order' => ['28.11.2026', null];
    }

    #[DataProvider('days')]
    public function test_it_reads_a_real_day(string $text, ?string $day): void
    {
        $this->assertSame($day, Date::parse($text)?->__toString());
    }

    public function test_months_and_days_roll_over(): void
    {
        $this->assertSame('2027-01-01', (string) Date::of(2026, 13, 1));
        $this->assertSame('2026-03-03', (string) Date::of(2026, 2, 31));
        $this->assertSame('2025-12-31', (string) Date::of(2026, 1, 0));
        $this->assertSame('9999-12-31', (string) Date::of(9999, 12, 31));
        $this->assertSame('0001-01-01', (string) Date::of(1, 1, 1));
        $this->assertNull(Date::of(9999, 12, 32));
        $this->assertNull(Date::of(1, 1, 0));
        $this->assertNull(Date::of(10000, 1, 1));
    }

    public function test_a_moment_gives_the_day_of_its_own_time_zone(): void
    {
        $moment = new DateTimeImmutable('2026-11-28 23:30:00', new DateTimeZone('Pacific/Auckland'));

        $this->assertSame('2026-11-28', (string) Date::from($moment));
        $this->assertSame('2026-11-28', (string) Date::from(new DateTimeImmutable('2026-11-28 00:30:00', new DateTimeZone('Pacific/Auckland'))));
        $this->assertNull(Date::from((new DateTimeImmutable('2026-01-01'))->setDate(10000, 1, 1)));
    }

    public function test_the_parts_of_a_day(): void
    {
        $date = Date::parse('2026-03-05');

        $this->assertSame([2026, 3, 5, 4], [$date->year(), $date->month(), $date->day(), $date->weekday()]);
        $this->assertSame(7, Date::parse('2026-03-08')->weekday());
        $this->assertSame(1, Date::parse('2026-03-09')->weekday());
    }

    public function test_days_are_added_and_counted(): void
    {
        $date = Date::parse('2026-03-05');

        $this->assertSame('2026-03-06', (string) $date->addDays(1));
        $this->assertSame('2026-03-05', (string) $date->addDays(0));
        $this->assertSame('2026-02-28', (string) $date->addDays(-5));
        $this->assertNull(Date::parse('9999-12-31')->addDays(1));
        $this->assertNull(Date::parse('0001-01-01')->addDays(-1));
        $this->assertSame(30, $date->daysUntil(Date::parse('2026-04-04')));
        $this->assertSame(-5, $date->daysUntil(Date::parse('2026-02-28')));
        $this->assertSame(0, $date->daysUntil($date));
    }

    /** @return iterable<string,array{string,int,?string,?string}> */
    public static function months(): iterable
    {
        yield 'the same month' => ['2026-02-10', 0, '2026-02-10', '2026-02-28'];
        yield 'the next, shorter' => ['2026-01-31', 1, '2026-02-28', '2026-02-28'];
        yield 'a leap February' => ['2028-01-31', 1, '2028-02-29', '2028-02-29'];
        yield 'back over a year' => ['2026-01-15', -2, '2025-11-15', '2025-11-30'];
        yield 'a longer month keeps the day' => ['2026-02-28', 1, '2026-03-28', '2026-03-31'];
        yield 'past the last year' => ['9999-12-01', 1, null, null];
        yield 'before the first' => ['0001-01-31', -1, null, null];
    }

    #[DataProvider('months')]
    public function test_months_are_added(string $day, int $months, ?string $same, ?string $end): void
    {
        $this->assertSame($same, Date::parse($day)->addMonths($months)?->__toString());
        $this->assertSame($end, Date::parse($day)->endOfMonth($months)?->__toString());
    }

    public function test_days_compare_as_the_calendar_does(): void
    {
        $date = Date::parse('2026-03-05');

        $this->assertLessThan(Date::parse('2027-01-01'), $date);
        $this->assertGreaterThan(Date::parse('2025-12-31'), $date);
        $this->assertTrue($date == Date::of(2026, 3, 5));
    }

    public function test_how_values_read_inside_a_text(): void
    {
        $this->assertSame('TRUE', Scalars::text(true));
        $this->assertSame('FALSE', Scalars::text(false));
        $this->assertSame('', Scalars::text(null));
        $this->assertSame('a', Scalars::text('a'));
        $this->assertSame('1.5', Scalars::text(BigDecimal::of('1.5')));
        $this->assertSame('2026-03-05', Scalars::text(Date::parse('2026-03-05')));
    }

    public function test_blank_is_missing_or_an_empty_text(): void
    {
        $this->assertTrue(Scalars::blank(null));
        $this->assertTrue(Scalars::blank(''));
        $this->assertFalse(Scalars::blank(' '));
        $this->assertFalse(Scalars::blank(false));
        $this->assertFalse(Scalars::blank(BigDecimal::zero()));
        $this->assertFalse(Scalars::blank([]));
    }

    public function test_the_single_values_are_four(): void
    {
        $this->assertSame(
            [Kind::Number, Kind::Text, Kind::Boolean, Kind::Date],
            array_column(Scalars::type()->options, 'kind'),
        );
    }
}
