<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Unit;

use ArrayObject;
use Atrium\Language\Context;
use Atrium\Language\Formula;
use Atrium\Language\FormulaError;
use Atrium\Language\Functions\Functions;
use Atrium\Language\Limits;
use Atrium\Language\Names;
use Atrium\Language\Position;
use Atrium\Language\Problem;
use Atrium\Language\Schema;
use Atrium\Language\Tests\TestCase;
use Atrium\Language\Types\Type;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

/** The fields a formula may name, what it runs against, and its problems. */
final class SchemaTest extends TestCase
{
    public function test_names_match_whatever_their_case_and_spaces(): void
    {
        $this->assertSame('дата сдачи', Names::normalise("  Дата \t\n СДАЧИ "));
        $this->assertSame('Дата СДАЧИ', Names::tidy("  Дата \u{00A0} СДАЧИ "));
    }

    public function test_a_field_is_found_by_its_key_and_its_names(): void
    {
        $schema = Schema::make()->text('client_name', 'Client', 'Имя  клиента');
        $field = $schema->find('client_name');

        $this->assertSame('client_name', $field->key);
        $this->assertEquals(Type::text(), $field->type);
        $this->assertSame($field, $schema->find('CLIENT'));
        $this->assertSame($field, $schema->find(' имя клиента '));
        $this->assertNull($schema->find('name'));
        $this->assertSame(['client_name' => $field], $schema->fields());
    }

    public function test_each_method_adds_a_field_of_its_type(): void
    {
        $line = Schema::make();
        $schema = Schema::make()->number('n', 'Number')->text('t', 'Text')->boolean('b', 'Boolean')->date('d', 'Date')->records('r', $line, 'Records')->field('a', Type::any(), 'Any');

        $this->assertSame(['n', 't', 'b', 'd', 'r', 'a'], array_keys($schema->fields()));
        $this->assertEquals(
            [Type::number(), Type::text(), Type::boolean(), Type::date(), Type::listOf(Type::record($line)), Type::any()],
            array_map(static fn (string $name): Type => $schema->find($name)->type, ['Number', 'Text', 'Boolean', 'Date', 'Records', 'Any']),
        );
    }

    public function test_a_field_may_repeat_its_own_name(): void
    {
        $schema = Schema::make()->number('price', 'Price', 'PRICE');

        $this->assertSame('price', $schema->find('Price')->key);
    }

    /** @return iterable<string,array{\Closure(Schema): Schema,string}> */
    public static function clashes(): iterable
    {
        yield 'one key twice' => [static fn (Schema $schema): Schema => $schema->number('amount'), 'amount'];
        yield 'a name of another' => [static fn (Schema $schema): Schema => $schema->number('total', 'sum'), 'sum'];
        yield 'a key that is a name of another' => [static fn (Schema $schema): Schema => $schema->text('SUM'), 'SUM'];
    }

    /** @param  \Closure(Schema): Schema  $add */
    #[DataProvider('clashes')]
    public function test_two_fields_cannot_share_a_name(\Closure $add, string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Two fields are called [{$name}].");

        $add(Schema::make()->number('amount', 'Sum'));
    }

    public function test_a_context_gives_values_by_key(): void
    {
        $schema = Schema::make()->number('a');

        $this->assertSame($schema, Context::make($schema)->schema());
        $this->assertNull(Context::make($schema)->value('a'));
        $this->assertSame(1, Context::make($schema, ['a' => 1])->value('a'));
        $this->assertSame(2, Context::make($schema, new ArrayObject(['a' => 2]))->value('a'));
        $this->assertNull(Context::make($schema, new ArrayObject)->value('a'));
    }

    public function test_each_change_of_a_context_makes_a_copy(): void
    {
        $context = Context::make(Schema::make());
        $changed = $context->allow('a')->withToday(new DateTimeImmutable('2020-02-03'));
        $more = $changed->allow('b', 'c');

        $this->assertSame([], $context->permissions());
        $this->assertSame(['a'], $changed->permissions());
        $this->assertSame(['a', 'b', 'c'], $more->permissions());
        $this->assertSame('2020-02-03', (string) $more->today());
        $this->assertNotSame('2020-02-03', (string) $context->today());

        foreach ([
            static fn (Context $c): Context => $c->withLimits(new Limits(steps: 1)),
            static fn (Context $c): Context => $c->withClock(static fn (): int => -1),
            static fn (Context $c): Context => $c->withFunctions(new Functions),
        ] as $change) {
            $copy = $change($context);
            $this->assertNotSame($context, $copy);
        }

        $this->assertSame(200000, $context->limits()->steps);
        $this->assertGreaterThan(0, $context->clock()());
        $this->assertNotNull($context->functions()->find('SUM'));
        $this->assertSame(-1, $context->withClock(static fn (): int => -1)->clock()());
    }

    public function test_a_given_today_stays_in_its_copy(): void
    {
        $first = Context::make(Schema::make())->withToday(new DateTimeImmutable('2020-02-03'));
        $second = $first->withToday(new DateTimeImmutable('2021-03-04'));

        $this->assertSame('2020-02-03', (string) $first->today());
        $this->assertSame('2021-03-04', (string) $second->today());
    }

    public function test_today_must_be_a_day_the_language_knows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The day is outside the years 1 to 9999.');

        Context::make(Schema::make())->withToday((new DateTime)->setDate(10000, 1, 1));
    }

    public function test_a_problem_is_said_with_its_place_or_without(): void
    {
        $this->assertSame('There is no field “x”.', (new Problem('check.unknown_field', ['name' => 'x']))->message('en'));
        $this->assertSame(
            'Line 2, column 7: Argument 1 of the function F needs: date, found: list of text.',
            (new Problem(
                'check.argument',
                ['argument' => 1, 'function' => 'F', 'expected' => Type::date(), 'actual' => Type::listOf(Type::text())],
                new Position(2, 7),
            ))->message('en'),
        );
        $this->assertSame('Деление на ноль.', (new Problem('value.division_by_zero'))->message('ru'));

        $this->app->setLocale('de');
        $this->assertSame((new Problem('value.division_by_zero'))->message('de'), (new Problem('value.division_by_zero'))->message());
        $this->assertNotSame('Division by zero.', (new Problem('value.division_by_zero'))->message());
    }

    public function test_an_error_says_all_its_problems(): void
    {
        $error = new FormulaError([new Problem('value.division_by_zero'), new Problem('value.negative_root', [], new Position(1, 2))]);

        $this->assertSame('Division by zero. Line 1, column 2: There is no square root of a negative number.', $error->getMessage());
        $this->assertSame('value.division_by_zero', $error->problem()->code);
        $this->assertSame('Division by zero.', FormulaError::of('value.division_by_zero')->getMessage());

        $placed = FormulaError::of('check.unknown_field', new Position(3, 4), ['name' => 'y']);
        $this->assertSame('Line 3, column 4: There is no field “y”.', $placed->getMessage());
    }

    public function test_a_formula_that_cannot_be_read_says_it_in_the_applications_language(): void
    {
        $this->app->setLocale('ru');
        $this->expectException(FormulaError::class);
        $this->expectExceptionMessage('Строка 1');

        Formula::parse('1 +');
    }
}
