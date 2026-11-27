<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Feature;

use ArrayAccess;
use Atrium\Language\Context;
use Atrium\Language\Formula;
use Atrium\Language\Functions\FormulaFunction;
use Atrium\Language\Functions\Functions;
use Atrium\Language\Functions\Invocation;
use Atrium\Language\Ir\Call;
use Atrium\Language\Ir\Each;
use Atrium\Language\Ir\Field;
use Atrium\Language\Ir\Literal;
use Atrium\Language\Ir\Member;
use Atrium\Language\Ir\Node;
use Atrium\Language\Ir\Operation;
use Atrium\Language\Ir\Operator;
use Atrium\Language\Ir\Variable;
use Atrium\Language\Position;
use Atrium\Language\Schema;
use Atrium\Language\Tests\TestCase;
use Atrium\Language\Types\Type;
use Atrium\Language\Values\Record;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

final class EvaluateTest extends TestCase
{
    /** Fields whose types are only known while running, and a related record. */
    private function open(array $values = []): Context
    {
        $schema = $this->schema()
            ->field('x', Type::any())
            ->field('y', Type::any())
            ->field('client', Type::record(Schema::make()->number('age', 'Age')->text('name', 'Name')))
            ->field('groups', Type::listOf(Type::listOf(Type::record(Schema::make()->number('age', 'Age')))));

        return $this->context($values, $schema);
    }

    /** @return iterable<string,array{string,array<string,mixed>,mixed}> */
    public static function results(): iterable
    {
        $number = static fn (string $number): array => self::num($number);

        yield 'a number' => ['1.50', [], $number('1.5')];
        yield 'a text' => ['"a"', [], 'a'];
        yield 'a yes' => ['TRUE', [], true];
        yield 'a date' => ['28.11.2026', [], self::day('2026-11-28')];
        yield 'a field' => ['Amount', ['amount' => 5], $number('5')];
        yield 'a field by another name' => ['сумма', ['amount' => 5], $number('5')];
        yield 'a missing field' => ['Amount', [], null];

        yield 'numbers added' => ['Amount + 1 + 0.5', ['amount' => 2], $number('3.5')];
        yield 'a date and days' => ['Due + 1', ['due' => '2026-12-31'], self::day('2027-01-01')];
        yield 'days and a date' => ['1 + Due', ['due' => '2026-12-31'], self::day('2027-01-01')];
        yield 'a date and more days' => ['Due + 2 + 3', ['due' => '2026-01-01'], self::day('2026-01-06')];
        yield 'halves that make a day' => ['1.5 + 0.5 + Due', ['due' => '2026-01-01'], self::day('2026-01-03')];
        yield 'a whole number with a point' => ['Due + 1.0', ['due' => '2026-01-01'], self::day('2026-01-02')];
        yield 'a date less days' => ['Due - 1', ['due' => '2026-01-01'], self::day('2025-12-31')];
        yield 'a date less a date' => ['Due - 28.11.2026', ['due' => '2026-12-01'], $number('3')];
        yield 'an earlier date less a date' => ['28.11.2026 - Due', ['due' => '2026-12-01'], $number('-3')];
        yield 'the last day' => ['30.12.9999 + 1', [], self::day('9999-12-31')];
        yield 'subtracted' => ['5 - 7', [], $number('-2')];
        yield 'anything less a number' => ['X - 1', ['x' => 3], $number('2')];
        yield 'a date from anything' => ['X - 1', ['x' => new \DateTimeImmutable('2026-01-02')], self::day('2026-01-01')];
        yield 'anything less anything' => ['X - Y', ['x' => 3, 'y' => 1], $number('2')];
        yield 'anything and a date' => ['X + Due', ['x' => 1, 'due' => '2026-01-01'], self::day('2026-01-02')];

        yield 'multiplied' => ['2 * 3 * 4', [], $number('24')];
        yield 'divided' => ['1 / 4', [], $number('0.25')];
        yield 'divided without end' => ['1 / 3', [], $number('0.33333333333333333333')];
        yield 'divided, half up' => ['2 / 3', [], $number('0.66666666666666666667')];
        yield 'rounded at every factor' => ['0.00000000000000000001 * 0.00000000000000000001 * 10000000000000000000000', [], $number('0')];
        yield 'raised' => ['2 ^ 10', [], $number('1024')];
        yield 'raised to an odd power' => ['3 ^ 5', [], $number('243')];
        yield 'raised to nothing' => ['2 ^ 0', [], $number('1')];
        yield 'nothing raised to nothing' => ['0 ^ 0', [], $number('1')];
        yield 'nothing raised' => ['0 ^ 2', [], $number('0')];
        yield 'raised to less than nothing' => ['2 ^ -2', [], $number('0.25')];
        yield 'raised to less than nothing, half up' => ['1.5 ^ -1', [], $number('0.66666666666666666667')];
        yield 'raised from the left' => ['2 ^ 3 ^ 2', [], $number('64')];
        yield 'the sign first' => ['-2 ^ 2', [], $number('4')];
        yield 'the highest power' => ['1 ^ 1000', [], $number('1')];
        yield 'the lowest power' => ['1 ^ -1000', [], $number('1')];
        yield 'a power with a point' => ['2 ^ 2.0', [], $number('4')];
        // Squaring past the last step would overflow.
        yield 'a large number once' => ['(10 ^ 30) ^ 1', [], $number('1'.str_repeat('0', 30))];
        yield 'the largest power of ten' => ['10 ^ 39', [], $number('1'.str_repeat('0', 39))];
        yield 'percent' => ['50%', [], $number('0.5')];
        yield 'percent is rounded' => ['0.00000000000000000001% = 0', [], true];
        yield 'negated' => ['-Amount', ['amount' => '1.5'], $number('-1.5')];
        yield 'negated twice' => ['-(-2)', [], $number('2')];

        yield 'joined' => ['"a" & 1.50 & TRUE & 28.11.2026 & FALSE', [], 'a1.5TRUE2026-11-28FALSE'];
        yield 'joined with nothing' => ['Status & "!" & Amount', [], '!'];
        yield 'anything joined with nothing' => ['X & "a"', [], 'a'];

        foreach ([
            '1 = 1' => true, '1 = 2' => false, '2 = 1' => false, '1 <> 2' => true, '1 <> 1' => false,
            '1 < 2' => true, '2 < 2' => false, '3 < 2' => false,
            '1 <= 2' => true, '2 <= 2' => true, '3 <= 2' => false,
            '3 > 2' => true, '2 > 2' => false, '1 > 2' => false,
            '3 >= 2' => true, '2 >= 2' => true, '1 >= 2' => false,
            '1.50 = 1.5' => true,
            '"abc" = "ABC"' => true, '"Ёж" = "ёж"' => true, '"a" = "b"' => false,
            '"ёж" = "ЁЖ"' => true, '2.5 < 10' => true, '10 < 2.5' => false, '-2.5 < -10' => false,
            '"a" < "B"' => true, '"B" > "a"' => true, '"b" < "A"' => false, '"10" < "9"' => true,
            '1.1.2026 < 2.1.2026' => true, '2.1.2026 < 1.1.2026' => false, '1.1.2026 = 1.1.2026' => true,
            'TRUE = TRUE' => true, 'TRUE = FALSE' => false, 'FALSE <> TRUE' => true,
        ] as $source => $answer) {
            yield $source => [$source, [], $answer];
        }

        yield 'nothing equals an empty text' => ['Status = ""', [], true];
        yield 'an empty text equals it too' => ['"" = Status', ['status' => ''], true];
        yield 'nothing equals nothing' => ['Amount = Discount', [], true];
        yield 'nothing is not zero' => ['Amount = 0', [], false];
        yield 'zero is not nothing' => ['0 = Amount', [], false];
        yield 'nothing differs from zero' => ['Amount <> 0', [], true];
        yield 'nothing is not a text' => ['Status = "a"', [], false];
        yield 'nothing differs from nothing no more' => ['Amount <> Discount', [], false];

        yield 'both' => ['TRUE AND TRUE', [], true];
        yield 'the second false' => ['TRUE AND FALSE', [], false];
        yield 'the first false ends it' => ['FALSE AND 1 / 0 = 1', [], false];
        yield 'neither' => ['FALSE OR FALSE', [], false];
        yield 'the second true' => ['FALSE OR TRUE', [], true];
        yield 'the first true ends it' => ['TRUE OR 1 / 0 = 1', [], true];
        yield 'not yes' => ['NOT(TRUE)', [], false];
        yield 'not no' => ['NOT(FALSE)', [], true];

        yield 'then' => ['IF(Paid; 1; 1 / 0)', ['paid' => true], $number('1')];
        yield 'otherwise' => ['IF(Paid; 1 / 0; 2)', ['paid' => false], $number('2')];
        yield 'no otherwise' => ['IF(Paid; 1)', ['paid' => false], null];

        yield 'a member of records' => ['Lines.Price', ['lines' => [['price' => 1], ['price' => '2.5'], []]], [$number('1'), $number('2.5'), null]];
        yield 'a member of no records' => ['Lines.Price', ['lines' => []], []];
        yield 'a member of a missing list' => ['Lines.Price', [], null];
        yield 'a member of a record' => ['Client.Name', ['client' => ['name' => 'Ann']], 'Ann'];
        yield 'a member of a missing record' => ['Client.Name', [], null];
        yield 'a member of lists in lists' => ['Groups.Age', ['groups' => [[['age' => 1]], [], [['age' => 2], []]]], [[$number('1')], [], [$number('2'), null]]];
        yield 'a total of a member' => ['SUM(Lines.Price)', ['lines' => [['price' => 1], ['price' => 2]]], $number('3')];
    }

    /** @param  array<string,mixed>  $values */
    #[DataProvider('results')]
    public function test_it_works_out_a_formula(string $source, array $values, mixed $result): void
    {
        $this->assertSame($result, $this->value($source, context: $this->open($values)));
    }

    /** Computed apart: each product rounded to twenty places before the next. */
    public function test_a_power_is_rounded_as_it_is_counted(): void
    {
        $round = static fn (BigDecimal $number): BigDecimal => $number->toScale(20, RoundingMode::HalfUp);
        $first = BigDecimal::of('1.23456789012345678901');
        $second = $round($first->multipliedBy($first));
        $fourth = $round($second->multipliedBy($second));
        $seventh = $round($round($first->multipliedBy($second))->multipliedBy($fourth));

        $this->assertSame(
            self::num((string) $seventh->strippedOfTrailingZeros()),
            $this->value('1.23456789012345678901 ^ 7'),
        );
        $this->assertNotSame((string) $round($first->power(7)), (string) $seventh);
    }

    /** @return iterable<string,array{string,array<string,mixed>,string,string}> */
    public static function failures(): iterable
    {
        $at = static fn (int $column, string $message): string => "Line 1, column {$column}: {$message}";
        $large = static fn (int $column): array => ['value.too_large', $at($column, 'A number is too large: it has more than 40 digits before the decimal point.')];
        $zero = static fn (int $column): array => ['value.division_by_zero', $at($column, 'Division by zero.')];
        $power = static fn (int $column): array => ['value.exponent', $at($column, 'The power must be a whole number between -1000 and 1000.')];
        $days = static fn (int $column): array => ['value.whole_days', $at($column, 'Only whole days can be added to a date or taken from it.')];
        $range = static fn (int $column): array => ['value.date_out_of_range', $at($column, 'The date is outside the years 1 to 9999.')];
        $empty = static fn (int $column, string $field, string $expected): array => ['value.empty_field', $at($column, "The field “{$field}” is empty, but the formula needs: {$expected}.")];
        $type = static fn (int $column, string $expected, string $actual): array => ['value.wrong_type', $at($column, "The formula needs: {$expected}, found: {$actual}.")];
        $compare = static fn (int $column, string $left, string $right): array => ['value.compare', $at($column, "These cannot be compared: {$left} and {$right}.")];
        $nine = str_repeat('9', 40);
        $ordered = 'number or text or date';

        yield 'an empty number added' => ['Amount + 1', [], ...$empty(1, 'Amount', 'number or date')];
        yield 'added to an empty number' => ['1 + Скидка', [], ...$empty(5, 'Скидка', 'number or date')];
        yield 'an empty number multiplied' => ['Amount * 2', [], ...$empty(1, 'Amount', 'number')];
        yield 'multiplied by an empty one' => ['2 * 3 * Amount', [], ...$empty(9, 'Amount', 'number')];
        yield 'an empty number divided' => ['Amount / 2', [], ...$empty(1, 'Amount', 'number')];
        yield 'divided by an empty one' => ['2 / Amount', [], ...$empty(5, 'Amount', 'number')];
        yield 'an empty number negated' => ['-Amount', [], ...$empty(2, 'Amount', 'number')];
        yield 'percent of an empty one' => ['Amount%', [], ...$empty(1, 'Amount', 'number')];
        yield 'an empty number raised' => ['Amount ^ 2', [], ...$empty(1, 'Amount', 'number')];
        yield 'raised to an empty one' => ['2 ^ Amount', [], ...$empty(5, 'Amount', 'number')];
        yield 'less an empty one' => ['2 - Amount', [], ...$empty(5, 'Amount', 'number or date')];
        yield 'an empty condition' => ['IF(Paid; 1)', [], ...$empty(4, 'Paid', 'yes/no')];
        yield 'an empty operand of and' => ['TRUE AND Paid', [], ...$empty(10, 'Paid', 'yes/no')];
        yield 'an empty operand of or' => ['Paid OR TRUE', [], ...$empty(1, 'Paid', 'yes/no')];
        yield 'not nothing' => ['NOT(Paid)', [], ...$empty(5, 'Paid', 'yes/no')];
        yield 'ordering nothing' => ['Amount < 1', [], ...$empty(1, 'Amount', $ordered)];
        yield 'ordering against nothing' => ['1 > Amount', [], ...$empty(5, 'Amount', $ordered)];
        yield 'an empty member' => ['Client.Age * 2', ['client' => []], ...$empty(8, 'Age', 'number')];
        yield 'an empty result' => ['IF(FALSE; 1) + 1', [], 'value.empty', $at(1, 'A value is empty here, but the formula needs: number or date.')];

        yield 'a text for a number' => ['X * 2', ['x' => 'a'], ...$type(1, 'number', 'text')];
        yield 'a yes added' => ['X + 1', ['x' => true], ...$type(1, 'number or date', 'yes/no')];
        yield 'two dates added' => ['Due + X', ['due' => '2026-01-01', 'x' => new \DateTimeImmutable('2026-01-01')], ...$type(7, 'number', 'date')];
        yield 'a date added to anything' => ['X + Due', ['due' => '2026-01-01', 'x' => new \DateTimeImmutable('2026-01-01')], ...$type(5, 'number', 'date')];
        yield 'a date from a number' => ['Amount - X', ['amount' => 1, 'x' => new \DateTimeImmutable('2026-01-01')], ...$type(10, 'number', 'date')];
        yield 'a number for a condition' => ['IF(X; 1)', ['x' => 1], ...$type(4, 'yes/no', 'number')];
        yield 'ordering yes and no' => ['X < 1', ['x' => true], ...$type(1, $ordered, 'yes/no')];
        yield 'ordering against a list' => ['1 < X', ['x' => [1]], ...$type(5, $ordered, 'list of any value')];
        yield 'joining a list' => ['"a" & X', ['x' => [1]], ...$type(7, 'number or text or yes/no or date', 'list of any value')];
        yield 'a member of a number' => ['X.Name', ['x' => 1], ...$type(1, 'record', 'number')];
        yield 'a member the record has not' => ['X.Name', ['x' => new Record(Schema::make(), [])], 'check.unknown_member', $at(3, 'The related record has no field “Name”.')];

        yield 'comparing two kinds' => ['X = Y', ['x' => 1, 'y' => 'a'], ...$compare(3, 'number', 'text')];
        yield 'ordering two kinds' => ['X < Y', ['x' => 'a', 'y' => 1], ...$compare(3, 'text', 'number')];
        yield 'comparing lists' => ['X = Y', ['x' => [1], 'y' => [1]], ...$compare(3, 'list of any value', 'list of any value')];
        yield 'comparing a record' => ['X <> X', ['x' => new Record(Schema::make(), [])], ...$compare(3, 'record', 'record')];

        yield 'dividing by zero' => ['1 / 0', [], ...$zero(3)];
        yield 'dividing by what is zero' => ['1 / (2 - 2)', [], ...$zero(3)];
        yield 'zero raised to less than nothing' => ['0 ^ -1', [], ...$zero(3)];
        yield 'too high a power' => ['2 ^ 1001', [], ...$power(3)];
        yield 'too low a power' => ['2 ^ -1001', [], ...$power(3)];
        yield 'a power with a fraction' => ['2 ^ 0.5', [], ...$power(3)];
        yield 'a number too large to add' => ["{$nine} + 1", [], ...$large(42)];
        yield 'a number too large to subtract' => ["-{$nine} - 1", [], ...$large(43)];
        yield 'a number too large to multiply' => ['1000000000000000000000000000000 * 10000000000', [], ...$large(33)];
        yield 'a product too large on the way' => ['(10 ^ 39) * 10 * 0', [], ...$large(11)];
        yield 'a number too large to divide' => ["{$nine} / 0.1", [], ...$large(42)];
        yield 'a power too large' => ['10 ^ 40', [], ...$large(4)];
        yield 'a square too large' => ['(10 ^ 20) ^ 2', [], ...$large(11)];
        yield 'a huge power' => ['(10 ^ 39) ^ 1000', [], ...$large(11)];
        yield 'a small number raised to less than nothing' => ['0.00000000000000000001 ^ -3', [], ...$large(24)];

        yield 'half a day added' => ['Due + 0.5', ['due' => '2026-01-01'], ...$days(5)];
        yield 'half a day taken' => ['Due - 0.5', ['due' => '2026-01-01'], ...$days(5)];
        yield 'past the last day' => ['31.12.9999 + 1', [], ...$range(12)];
        yield 'before the first day' => ['1.1.0001 - 1', [], ...$range(10)];
        yield 'more days than there are' => ['Due + 4000001', ['due' => '2026-01-01'], ...$range(5)];
        yield 'a number of days beyond any integer' => ['Due - 100000000000000000000', ['due' => '2026-01-01'], ...$range(5)];
    }

    /** @param  array<string,mixed>  $values */
    #[DataProvider('failures')]
    public function test_it_stops_and_says_why_and_where(string $source, array $values, string $code, string $message): void
    {
        $this->assertStops($code, $message, fn () => Formula::parse($source)->evaluate($this->open($values)));
    }

    private static function item(string $name): Node
    {
        return new Member(new Variable('line'), $name);
    }

    private function through(Node $node, array $values): mixed
    {
        return self::plain(Formula::fromNode($node)->evaluate($this->open($values))->value);
    }

    public function test_a_loop_makes_a_list_of_what_each_item_gives(): void
    {
        $total = new Operation(Operator::Multiply, [self::item('price'), self::item('qty')]);
        $lines = ['lines' => [['price' => 2, 'qty' => 3], ['price' => 1, 'qty' => 1], ['price' => 5, 'qty' => 2]]];

        $this->assertSame(
            [self::num(6), self::num(1), self::num(10)],
            $this->through(new Each(new Field('lines'), 'line', $total), $lines),
        );
        $this->assertSame(
            [self::num(6), self::num(10)],
            $this->through(new Each(
                new Field('lines'),
                'line',
                $total,
                new Operation(Operator::Greater, [self::item('price'), new Literal(BigDecimal::one())]),
            ), $lines),
        );
        $this->assertSame([], $this->through(new Each(new Field('lines'), 'line', $total, new Literal(false)), $lines));
        $this->assertSame([], $this->through(new Each(new Field('lines'), 'line', $total), []));
        $this->assertSame([], $this->through(new Each(new Literal(null), 'line', $total), []));
        $this->assertSame([self::num(1), null], $this->through(new Each(new Field('x'), 'v', new Variable('v')), ['x' => [1, null]]));
    }

    public function test_a_loop_inside_a_loop_gives_the_name_back(): void
    {
        $inner = new Each(new Member(new Field('rows'), 'price'), 'line', new Variable('line'));
        $outer = new Each(new Field('lines'), 'line', new Operation(Operator::Concat, [
            new Call('SUM', [$inner]),
            self::item('title'),
        ]));

        $this->assertSame(['3a', '3b'], $this->through($outer, [
            'lines' => [['title' => 'a'], ['title' => 'b']],
            'rows' => [['price' => 1], ['price' => 2]],
        ]));
    }

    public function test_a_loop_stops_at_what_is_no_list(): void
    {
        $loop = new Each(new Field('x', new Position(1, 4)), 'v', new Variable('v'));

        $this->assertStops(
            'value.wrong_type',
            'Line 1, column 4: The formula needs: list of any value, found: number.',
            fn () => $this->through($loop, ['x' => 5]),
        );
    }

    public function test_a_filter_must_be_yes_or_no_for_every_item(): void
    {
        $loop = new Each(new Field('x'), 'v', new Variable('v'), new Variable('v', new Position(1, 9)));

        $this->assertStops(
            'value.empty',
            'Line 1, column 9: A value is empty here, but the formula needs: yes/no.',
            fn () => $this->through($loop, ['x' => [true, null]]),
        );
    }

    public function test_a_member_of_an_item_that_is_no_record(): void
    {
        $loop = new Each(new Field('x'), 'line', new Member(new Variable('line', new Position(1, 2)), 'a'));

        $this->assertStops(
            'value.wrong_type',
            'Line 1, column 2: The formula needs: record, found: text.',
            fn () => $this->through($loop, ['x' => ['a']]),
        );
    }

    public function test_a_record_may_hold_less_than_its_fields(): void
    {
        $functions = Functions::standard()->add(new FormulaFunction(
            ['BARE'],
            [],
            Type::any(),
            static fn (Invocation $call): Record => new Record(Schema::make()->number('age'), []),
        ));

        $this->assertNull($this->value('BARE().Age', context: $this->context()->withFunctions($functions)));
    }

    public function test_the_result_names_the_fields_that_were_read(): void
    {
        $formula = Formula::parse('IF(Paid; Сумма + Amount; Discount)');
        $result = $formula->evaluate($this->context(['paid' => true, 'amount' => 2, 'discount' => 1]));

        $this->assertSame(['paid', 'amount'], array_keys($result->fields));
        $this->assertSame([true, self::num(2)], array_values(self::plain($result->fields)));
        $this->assertSame(self::num(4), self::plain($result->value));
        $this->assertNull($result->trace);
    }

    public function test_a_field_is_read_once_and_only_when_needed(): void
    {
        $values = new class implements ArrayAccess
        {
            /** @var list<string> */
            public array $read = [];

            public function offsetExists(mixed $offset): bool
            {
                return true;
            }

            public function offsetGet(mixed $offset): mixed
            {
                $this->read[] = $offset;

                return 1;
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
                throw new LogicException('Nothing is written.');
            }

            public function offsetUnset(mixed $offset): void
            {
                throw new LogicException('Nothing is removed.');
            }
        };

        $result = Formula::parse('Amount + Amount + IF(FALSE; Скидка; Amount)')->evaluate(Context::make($this->schema(), $values));

        $this->assertSame(self::num(3), self::plain($result->value));
        $this->assertSame(['amount'], $values->read);
    }

    public function test_the_same_formula_may_run_again_with_other_values(): void
    {
        $formula = Formula::parse('Amount * 2');

        $this->assertSame(self::num(2), self::plain($formula->evaluate($this->context(['amount' => 1]))->value));
        $this->assertSame(self::num(4), self::plain($formula->evaluate($this->context(['amount' => 2]))->value));
    }

    public function test_a_stored_formula_runs_like_the_written_one(): void
    {
        $stored = Formula::parse('IF(SUM(Lines.Price) > 2; "big"; "small")')->toJson();

        $this->assertSame('big', Formula::fromJson($stored)->evaluate($this->context(['lines' => [['price' => 3]]]))->value);
    }
}
