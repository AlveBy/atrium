<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Feature;

use ArrayIterator;
use ArrayObject;
use Atrium\Language\Formula;
use Atrium\Language\Schema;
use Atrium\Language\Tests\TestCase;
use Atrium\Language\Types\Type;
use Atrium\Language\Values\Date;
use Atrium\Language\Values\Record;
use Brick\Math\BigDecimal;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Stringable;

/** What the host gives as the values of fields, as the language takes it. */
final class ConversionTest extends TestCase
{
    private static function line(): Schema
    {
        static $line;

        return $line ??= Schema::make()->number('price')->text('title');
    }

    private function read(Type $type, mixed $raw): mixed
    {
        $context = $this->context(['x' => $raw], Schema::make()->field('x', $type));

        return self::plain(Formula::parse('X')->evaluate($context)->value);
    }

    private static function stringable(string $text): Stringable
    {
        return new class($text) implements Stringable
        {
            public function __construct(private readonly string $text) {}

            public function __toString(): string
            {
                return $this->text;
            }
        };
    }

    /** @return iterable<string,array{Type,mixed,mixed}> */
    public static function fitting(): iterable
    {
        $number = Type::number();
        $text = Type::text();
        $boolean = Type::boolean();
        $date = Type::date();
        $list = Type::listOf($number);
        $record = Type::record(self::line());
        $any = Type::any();
        $union = Type::oneOf($number, $text);
        $ann = ['record', ['price' => self::num(1), 'title' => 'Ann']];

        yield 'nothing' => [$number, null, null];
        yield 'an empty input for a number' => [$number, '', null];
        yield 'an empty input for a yes or no' => [$boolean, '', null];
        yield 'an empty input for a date' => [$date, '', null];
        yield 'an empty text' => [$text, '', ''];
        yield 'an empty text where anything goes' => [$any, '', ''];
        yield 'an integer' => [$number, -5, self::num(-5)];
        yield 'a float' => [$number, 0.1, self::num('0.1')];
        yield 'a whole float' => [$number, 2.0, self::num(2)];
        yield 'a large float' => [$number, 1.0E+22, self::num('1'.str_repeat('0', 22))];
        yield 'a number as a text' => [$number, '1.50', self::num('1.5')];
        yield 'a big decimal with too many places' => [$number, BigDecimal::of('0.000000000000000000005'), self::num('0.00000000000000000001')];
        yield 'a text' => [$text, 'a', 'a'];
        yield 'a text that writes itself' => [$text, self::stringable('b'), 'b'];
        yield 'yes' => [$boolean, true, true];
        yield 'no' => [$boolean, false, false];
        yield 'a date' => [$date, Date::parse('2026-01-02'), self::day('2026-01-02')];
        yield 'a date and time' => [$date, new DateTime('2026-01-02 13:00'), self::day('2026-01-02')];
        yield 'a date as a text' => [$date, '2026-01-02', self::day('2026-01-02')];
        yield 'a list' => [$list, ['1', 2, '', null], [self::num(1), self::num(2), null, null]];
        yield 'a list with keys' => [$list, ['a' => 1, 'b' => 2], [self::num(1), self::num(2)]];
        yield 'anything to go through' => [$list, new ArrayIterator([3]), [self::num(3)]];
        yield 'an empty list' => [$list, [], []];
        yield 'a record' => [$record, ['price' => '1', 'title' => 'Ann', 'extra' => 1], $ann];
        yield 'an object that reads as a record' => [$record, new ArrayObject(['price' => 1, 'title' => 'Ann']), $ann];
        yield 'a ready record' => [$record, new Record(self::line(), ['price' => BigDecimal::one(), 'title' => 'Ann']), $ann];
        yield 'a record with less' => [$record, [], ['record', ['price' => null, 'title' => null]]];
        yield 'any yes' => [$any, false, false];
        yield 'any integer' => [$any, 5, self::num(5)];
        yield 'any float' => [$any, 1.5, self::num('1.5')];
        yield 'any decimal' => [$any, BigDecimal::of('2'), self::num(2)];
        yield 'any text' => [$any, 'a', 'a'];
        yield 'any date' => [$any, Date::parse('2026-01-02'), self::day('2026-01-02')];
        yield 'any date and time' => [$any, new DateTimeImmutable('2026-01-02 13:00'), self::day('2026-01-02')];
        yield 'any list' => [$any, [1, 'a', [true]], [self::num(1), 'a', [true]]];
        yield 'any list to go through' => [$any, new ArrayIterator([1]), [self::num(1)]];
        yield 'any record' => [$any, new Record(self::line(), []), ['record', []]];
        yield 'the first of a union' => [$union, 5, self::num(5)];
        yield 'the second of a union' => [$union, '5', '5'];
        yield 'nothing of a union' => [$union, null, null];
        yield 'nothing where nothing goes' => [Type::empty(), null, null];
    }

    #[DataProvider('fitting')]
    public function test_it_takes_a_fitting_value(Type $type, mixed $raw, mixed $value): void
    {
        $this->assertSame($value, $this->read($type, $raw));
    }

    /** @return iterable<string,array{Type,mixed,string}> */
    public static function unfit(): iterable
    {
        $number = Type::number();
        $text = Type::text();
        $date = Type::date();
        $list = Type::listOf($number);
        $record = Type::record(self::line());
        $any = Type::any();

        yield 'a word for a number' => [$number, 'five', 'number'];
        yield 'a comma' => [$number, '1,5', 'number'];
        yield 'a yes for a number' => [$number, true, 'number'];
        yield 'a list for a number' => [$number, [], 'number'];
        yield 'infinity' => [$number, INF, 'number'];
        yield 'not a number' => [$number, NAN, 'number'];
        yield 'a number for a text' => [$text, 5, 'text'];
        yield 'bytes for a text' => [$text, "\xff", 'text'];
        yield 'bytes that write themselves' => [$text, self::stringable("\xff"), 'text'];
        yield 'a text for yes or no' => [Type::boolean(), 'true', 'yes/no'];
        yield 'one for yes' => [Type::boolean(), 1, 'yes/no'];
        yield 'zero for no' => [Type::boolean(), 0, 'yes/no'];
        yield 'a number for a date' => [$date, 20260102, 'date'];
        yield 'a date written another way' => [$date, '02.01.2026', 'date'];
        yield 'a year too late' => [$date, (new DateTime('2026-01-01'))->setDate(10000, 1, 1), 'date'];
        yield 'a text for a list' => [$list, 'a', 'list of number'];
        yield 'an empty text for a list' => [$list, '', 'list of number'];
        yield 'a text for a record' => [$record, 'a', 'record'];
        yield 'an object for a record' => [$record, new stdClass, 'record'];
        yield 'a keyed array where anything goes' => [$any, ['a' => 1], 'any value'];
        yield 'an object where anything goes' => [$any, new stdClass, 'any value'];
        yield 'infinity where anything goes' => [$any, INF, 'any value'];
        yield 'bytes where anything goes' => [$any, "\xff", 'any value'];
        yield 'outside a union' => [Type::oneOf($number, $text), true, 'number or text'];
        yield 'something where nothing goes' => [Type::empty(), '', 'nothing'];
    }

    #[DataProvider('unfit')]
    public function test_it_names_the_field_that_holds_an_unfit_value(Type $type, mixed $raw, string $expected): void
    {
        $this->assertStops(
            'value.bad_field',
            "Line 1, column 1: The field “X” holds a value that does not fit its type: {$expected}.",
            fn () => $this->read($type, $raw),
        );
    }

    public function test_a_value_inside_a_list_or_a_record_is_looked_at_too(): void
    {
        $message = 'Line 1, column 1: The field “X” holds a value that does not fit its type: ';

        $this->assertStops('value.bad_field', $message.'list of number.', fn () => $this->read(Type::listOf(Type::number()), [1, 'a']));
        $this->assertStops('value.bad_field', $message.'record.', fn () => $this->read(Type::record(self::line()), ['price' => 'a']));
        $this->assertStops('value.bad_field', $message.'list of record.', fn () => $this->read(Type::listOf(Type::record(self::line())), [1]));
    }

    public function test_a_number_too_large_stops_the_run(): void
    {
        $this->assertStops(
            'value.too_large',
            'Line 1, column 1: A number is too large: it has more than 40 digits before the decimal point.',
            fn () => $this->read(Type::number(), '1'.str_repeat('0', 40)),
        );
    }

    private static function wrapped(int $times): mixed
    {
        $value = null;

        for ($time = 0; $time < $times; $time++) {
            $value = [$value];
        }

        return $value;
    }

    public function test_lists_inside_one_another_have_an_end(): void
    {
        $this->assertIsArray($this->read(Type::any(), self::wrapped(64)));
        $this->assertStops(
            'value.bad_field',
            'Line 1, column 1: The field “X” holds a value that does not fit its type: any value.',
            fn () => $this->read(Type::any(), self::wrapped(65)),
        );
    }

    /** @return array{Type,mixed} records this many deep, and a value for them */
    private static function related(int $times): array
    {
        $type = Type::number();
        $value = 1;

        for ($time = 0; $time < $times; $time++) {
            $type = Type::record(Schema::make()->field('next', $type));
            $value = ['next' => $value];
        }

        return [$type, $value];
    }

    public function test_records_inside_one_another_have_an_end(): void
    {
        $this->assertIsArray($this->read(...self::related(64)));
        $this->assertStops(
            'value.bad_field',
            'Line 1, column 1: The field “X” holds a value that does not fit its type: record.',
            fn () => $this->read(...self::related(65)),
        );
    }
}
