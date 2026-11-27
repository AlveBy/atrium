<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Unit;

use Atrium\Language\Schema;
use Atrium\Language\Tests\TestCase;
use Atrium\Language\Types\Type;
use Atrium\Language\Values\Date;
use Atrium\Language\Values\Record;
use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\DataProvider;

final class TypeTest extends TestCase
{
    public function test_the_type_of_each_value(): void
    {
        $schema = Schema::make();

        $this->assertEquals(Type::empty(), Type::of(null));
        $this->assertEquals(Type::boolean(), Type::of(false));
        $this->assertEquals(Type::text(), Type::of(''));
        $this->assertEquals(Type::number(), Type::of(BigDecimal::zero()));
        $this->assertEquals(Type::date(), Type::of(Date::parse('2026-01-01')));
        $this->assertSame($schema, Type::of(new Record($schema, []))->fields);
        $this->assertEquals(Type::listOf(Type::any()), Type::of([1]));
    }

    /** @return iterable<string,array{Type,Type,bool}> */
    public static function accepting(): iterable
    {
        $number = Type::number();
        $text = Type::text();
        $union = Type::oneOf($number, $text);

        yield 'the same' => [$number, $number, true];
        yield 'another' => [$number, $text, false];
        yield 'anything is accepted by any' => [Type::any(), $text, true];
        yield 'any may be anything' => [$number, Type::any(), true];
        yield 'empty fits' => [$number, Type::empty(), true];
        yield 'empty takes only empty' => [Type::empty(), $number, false];
        yield 'the first of a union' => [$union, $number, true];
        yield 'the second of a union' => [$union, $text, true];
        yield 'outside a union' => [$union, Type::date(), false];
        yield 'a union of other types' => [$union, Type::oneOf(Type::date(), Type::boolean()), false];
        yield 'any for a union' => [$union, Type::any(), true];
        yield 'empty for a union' => [$union, Type::empty(), true];
        yield 'lists of the same' => [Type::listOf($number), Type::listOf($number), true];
        yield 'lists of others' => [Type::listOf($number), Type::listOf($text), false];
        yield 'a list of anything' => [Type::listOf(Type::any()), Type::listOf($text), true];
        yield 'a list for a number' => [$number, Type::listOf($number), false];
        yield 'a number for a list' => [Type::listOf($number), $number, false];
        yield 'a record for a record' => [Type::record(Schema::make()), Type::record(Schema::make()->text('a')), true];
        yield 'a number for a record' => [Type::record(Schema::make()), $number, false];
    }

    #[DataProvider('accepting')]
    public function test_which_type_may_stand_for_which(Type $expected, Type $actual, bool $accepted): void
    {
        $this->assertSame($accepted, $expected->accepts($actual));
    }

    /** @return iterable<string,array{Type,mixed,bool}> */
    public static function admitting(): iterable
    {
        $one = BigDecimal::one();
        $numbers = Type::listOf(Type::number());
        $either = Type::oneOf(Type::number(), $numbers);

        yield 'a number' => [Type::number(), $one, true];
        yield 'a text for a number' => [Type::number(), 'a', false];
        yield 'nothing for a number' => [Type::number(), null, true];
        yield 'a list of numbers' => [$numbers, [$one, $one], true];
        yield 'an empty list' => [$numbers, [], true];
        yield 'empty items' => [$numbers, [null, $one], true];
        yield 'a wrong item after an empty one' => [$numbers, [null, 'a'], false];
        yield 'a wrong item' => [$numbers, [$one, 'a'], false];
        yield 'a number for a list' => [$numbers, $one, false];
        yield 'nothing for a list' => [$numbers, null, false];
        yield 'a list of lists' => [Type::listOf($numbers), [[$one]], true];
        yield 'a wrong list of lists' => [Type::listOf($numbers), [['a']], false];
        yield 'the first of a union' => [$either, $one, true];
        yield 'the second of a union' => [$either, [$one], true];
        yield 'outside a union' => [$either, ['a'], false];
        yield 'a list where any is expected' => [Type::any(), [$one], true];
    }

    #[DataProvider('admitting')]
    public function test_which_value_may_stand_for_a_type(Type $expected, mixed $value, bool $admitted): void
    {
        $this->assertSame($admitted, $expected->admits($value));
    }

    /** @return iterable<string,array{Type,Type,?Type}> */
    public static function unions(): iterable
    {
        $number = Type::number();
        $text = Type::text();
        $schema = Schema::make();
        $record = Type::record($schema);

        yield 'the same' => [$number, $number, $number];
        yield 'different' => [$number, $text, null];
        yield 'empty first' => [Type::empty(), $text, $text];
        yield 'empty second' => [$text, Type::empty(), $text];
        yield 'two empty' => [Type::empty(), Type::empty(), Type::empty()];
        yield 'any first' => [Type::any(), $text, Type::any()];
        yield 'any second' => [$text, Type::any(), Type::any()];
        yield 'empty and any' => [Type::empty(), Type::any(), Type::any()];
        yield 'any and empty' => [Type::any(), Type::empty(), Type::any()];
        yield 'one record' => [$record, Type::record($schema), $record];
        yield 'two records' => [$record, Type::record(Schema::make()), null];
        yield 'lists of the same' => [Type::listOf($number), Type::listOf($number), Type::listOf($number)];
        yield 'lists of different' => [Type::listOf($number), Type::listOf($text), null];
        yield 'a list of any' => [Type::listOf($number), Type::listOf(Type::any()), Type::listOf(Type::any())];
        yield 'an empty list type' => [Type::listOf(Type::empty()), Type::listOf($text), Type::listOf($text)];
        yield 'a list and a number' => [Type::listOf($number), $number, null];
    }

    #[DataProvider('unions')]
    public function test_the_type_two_types_share(Type $first, Type $second, ?Type $common): void
    {
        $this->assertEquals($common, Type::unify($first, $second));
    }

    public function test_types_are_named_for_people_in_every_language(): void
    {
        $type = Type::oneOf(Type::number(), Type::listOf(Type::listOf(Type::text())));

        $this->assertSame('number or list of list of text', $type->describe('en'));
        $this->assertSame('число или список: список: текст', $type->describe('ru'));
        $this->assertSame(
            ['yes/no', 'date', 'nothing', 'any value', 'record'],
            array_map(
                static fn (Type $type): string => $type->describe('en'),
                [Type::boolean(), Type::date(), Type::empty(), Type::any(), Type::record(Schema::make())],
            ),
        );

        $this->app->setLocale('de');
        $this->assertSame('Zahl', Type::number()->describe());
    }
}
