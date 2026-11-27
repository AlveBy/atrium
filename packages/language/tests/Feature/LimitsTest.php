<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Feature;

use Atrium\Language\Context;
use Atrium\Language\Formula;
use Atrium\Language\FormulaError;
use Atrium\Language\Functions\FormulaFunction;
use Atrium\Language\Functions\Functions;
use Atrium\Language\Functions\Invocation;
use Atrium\Language\Ir\Each;
use Atrium\Language\Ir\Field;
use Atrium\Language\Ir\Member;
use Atrium\Language\Ir\Node;
use Atrium\Language\Ir\Variable;
use Atrium\Language\Limits;
use Atrium\Language\Schema;
use Atrium\Language\Tests\TestCase;
use Atrium\Language\Types\Type;
use Atrium\Language\Values\Record;
use Closure;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

final class LimitsTest extends TestCase
{
    public function test_the_limits_of_a_run_by_default(): void
    {
        $limits = new Limits;

        $this->assertSame(
            [200000, 1000, 2097152, 64, 8],
            [$limits->steps, $limits->milliseconds, $limits->memory, $limits->depth, $limits->recursion],
        );
        $this->assertEquals($limits, $this->context()->limits());

        $own = new Limits(steps: 1);
        $this->assertSame($own, $this->context()->withLimits($own)->limits());
    }

    private function worked(string|Formula $formula, Limits $limits, array $values = []): mixed
    {
        $formula = is_string($formula) ? Formula::parse($formula) : $formula;

        return self::plain($formula->evaluate($this->context($values)->withLimits($limits))->value);
    }

    /** @return iterable<string,array{string|Formula,array<string,mixed>,int}> */
    public static function stepped(): iterable
    {
        $three = ['lines' => [[], [], []]];

        // Three parts: the sum and the two numbers.
        yield 'every part' => ['1 + 2', [], 3];
        // The call, its two arguments, and the two values added up.
        yield 'every value a function takes' => ['SUM(1; 2)', [], 5];
        // The call and the field, three items read, three counted.
        yield 'every item of a list read' => ['COUNT(Lines)', $three, 8];
        // The member and the field, three items read, three looked into.
        yield 'every item looked into' => ['Lines.Price', $three, 8];
        // The loop and its source, three items read, three turns, three bodies.
        yield 'every turn of a loop' => [Formula::fromNode(new Each(new Field('lines'), 'v', new Variable('v'))), $three, 11];
    }

    /** @param  array<string,mixed>  $values */
    #[DataProvider('stepped')]
    public function test_each_thing_done_is_a_step(string|Formula $formula, array $values, int $steps): void
    {
        $this->worked($formula, new Limits(steps: $steps), $values);

        try {
            $this->worked($formula, new Limits(steps: $steps - 1), $values);
            $this->fail('Nothing stopped.');
        } catch (FormulaError $error) {
            $this->assertSame('limit.steps', $error->problem()->code);
        }
    }

    public function test_it_says_where_the_steps_ran_out(): void
    {
        $this->assertStops(
            'limit.steps',
            'Line 1, column 5: The formula was stopped: it took more than 2 steps.',
            fn () => $this->worked('1 + 2', new Limits(steps: 2)),
        );
    }

    private static function endless(): Generator
    {
        while (true) {
            yield 1;
        }
    }

    public function test_a_list_without_end_is_stopped(): void
    {
        $context = $this->context(['x' => self::endless()], Schema::make()->field('x', Type::listOf(Type::number())));

        $this->assertStops(
            'limit.steps',
            'Line 1, column 1: The formula was stopped: it took more than 200000 steps.',
            fn () => Formula::parse('X')->evaluate($context->withLimits(new Limits(milliseconds: 60000, memory: 100000000))),
        );
    }

    /** @return iterable<string,array{int,int,bool}> */
    public static function times(): iterable
    {
        yield 'no time passed' => [0, 0, true];
        yield 'all of it' => [0, 5000000, true];
        yield 'a nanosecond more' => [0, 5000001, false];
        yield 'counted from the start' => [7000, 5007000, true];
        yield 'past the end, counted from the start' => [7000, 5007001, false];
    }

    #[DataProvider('times')]
    public function test_a_run_has_a_time_limit(int $start, int $now, bool $finishes): void
    {
        $calls = 0;
        $context = $this->context()
            ->withLimits(new Limits(milliseconds: 5))
            ->withClock(static function () use (&$calls, $start, $now): int {
                return $calls++ === 0 ? $start : $now;
            });
        $run = fn () => Formula::parse('1')->evaluate($context);

        if ($finishes) {
            $this->assertSame('1', (string) $run()->value);
        } else {
            $this->assertStops('limit.time', 'Line 1, column 1: The formula was stopped: it ran longer than 5 ms.', $run);
        }
    }

    public function test_time_is_measured_by_a_clock_that_only_grows(): void
    {
        $clock = $this->context()->clock();
        $before = hrtime(true);
        $now = $clock();

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual(hrtime(true), $now);
    }

    /** @return iterable<string,array{string|Formula,array<string,mixed>,int,bool}> */
    public static function weighed(): iterable
    {
        $three = ['lines' => [[], [], []]];

        yield 'a result of an operator' => ['1 + 2', [], 16, false];
        yield 'a joined text and its bytes' => ['"ab" & "cd"', [], 20, false];
        yield 'bytes, not letters' => ['"ё" & ""', [], 18, false];
        yield 'a text read from a field' => ['Status', ['status' => 'abcd'], 20, false];
        yield 'a number read from a field' => ['Amount', ['amount' => 1], 16, false];
        yield 'a result of a function' => ['UPPER("abc")', [], 19, false];
        yield 'the steps kept for explaining' => ['1', [], 16, true];
        yield 'explaining keeps each part' => ['1 + 2', [], 64, true];
        yield 'an empty item of a list' => ['Lines', ['lines' => [null, null]], 48, false];
        // A list, three records with three empty fields each, and three members of them.
        yield 'a member of each item' => ['Lines.Price', $three, 256, false];
        // The same list, and each record kept once more with its fields.
        yield 'each result of a loop' => [Formula::fromNode(new Each(new Field('lines'), 'v', new Variable('v'))), $three, 400, false];
        yield 'a list kept for explaining' => ['Lines', ['lines' => [null, null]], 96, true];
    }

    /** @param  array<string,mixed>  $values */
    #[DataProvider('weighed')]
    public function test_the_values_made_are_weighed(string|Formula $formula, array $values, int $bytes, bool $explain): void
    {
        $formula = is_string($formula) ? Formula::parse($formula) : $formula;

        $this->assertWeighs($bytes, fn (int $memory) => $explain
            ? $formula->explain($this->context($values)->withLimits(new Limits(memory: $memory)))
            : $formula->evaluate($this->context($values)->withLimits(new Limits(memory: $memory))));
    }

    /** Asserts that the run fits in exactly this much memory and not in a byte less. */
    private function assertWeighs(int $bytes, Closure $run): void
    {
        $run($bytes);

        try {
            $run($bytes - 1);
            $this->fail('Nothing stopped.');
        } catch (FormulaError $error) {
            $this->assertSame('limit.memory', $error->problem()->code);
        }
    }

    /** @return iterable<string,array{Schema,array<string,mixed>,Node,int}> */
    public static function shared(): iterable
    {
        $numbers = Type::listOf(Type::number());

        // One list of three numbers, given by each of two turns.
        yield 'a list given by every turn' => [
            Schema::make()->field('big', Type::listOf($numbers))->field('turns', $numbers),
            ['big' => [[1, 2, 3]], 'turns' => [1, 1]],
            new Each(new Field('big'), 'a', new Each(new Field('turns'), 'b', new Variable('a'))),
            400,
        ];

        $order = Schema::make()->field('tags', $numbers);

        yield 'the lists inside a record' => [
            Schema::make()->records('orders', $order),
            ['orders' => [['tags' => [1, 2]]]],
            new Each(new Field('orders'), 'o', new Variable('o')),
            144,
        ];

        yield 'a list that is a member of each item' => [
            Schema::make()->records('orders', $order),
            ['orders' => [['tags' => [1, 2]]]],
            new Member(new Field('orders'), 'tags'),
            128,
        ];

        yield 'a record handed to a field of any type' => [
            Schema::make()->field('x', Type::any()),
            ['x' => new Record($order, [])],
            new Field('x'),
            16,
        ];
    }

    /** @param  array<string,mixed>  $values */
    #[DataProvider('shared')]
    public function test_a_list_put_in_many_places_is_paid_for_in_each(Schema $schema, array $values, Node $root, int $bytes): void
    {
        $formula = Formula::fromNode($root);

        $this->assertWeighs($bytes, fn (int $memory) => $formula->evaluate(
            $this->context($values, $schema)->withLimits(new Limits(memory: $memory)),
        ));
    }

    public function test_a_replacement_is_weighed_before_it_is_made(): void
    {
        // Ten times twenty letters by twenty others: the size stays the same.
        $old = str_repeat('a', 20);
        $source = 'SUBSTITUTE("'.str_repeat($old, 10).'"; "'.$old.'"; "'.str_repeat('b', 20).'")';

        $this->assertSame(str_repeat('b', 200), $this->worked($source, new Limits(memory: 216)));
        $this->assertStops(
            'limit.memory',
            'Line 1, column 1: The formula was stopped: its values took more than 215 bytes.',
            fn () => $this->worked($source, new Limits(memory: 215)),
        );
    }

    /** A hundred times a megabyte of text, from one field: far past the limit. */
    public static function huge(): iterable
    {
        yield 'joined by the operator' => [implode(' & ', array_fill(0, 100, 'Status'))];
        yield 'joined by a function' => ['CONCAT('.implode('; ', array_fill(0, 100, 'Status')).')'];
        yield 'repeated' => ['REPT(Status; 100)'];
        yield 'put in place of letters' => ['SUBSTITUTE("'.str_repeat('a', 100).'"; "a"; Status)'];
    }

    #[DataProvider('huge')]
    public function test_a_text_too_big_is_refused_before_it_takes_memory(string $source): void
    {
        $formula = Formula::parse($source);
        $context = $this->context(['status' => str_repeat('x', 1000000)]);
        memory_reset_peak_usage();
        $before = memory_get_peak_usage();

        try {
            $formula->evaluate($context);
            $this->fail('Nothing stopped.');
        } catch (FormulaError $error) {
            $this->assertSame('limit.memory', $error->problem()->code);
        }

        // The field itself is one copy; the refused text would be a hundred.
        $this->assertLessThan(20000000, memory_get_peak_usage() - $before);
    }

    private function recursive(int &$calls): Context
    {
        $context = null;
        $functions = Functions::standard()->add(new FormulaFunction(
            ['SELF'],
            [],
            Type::number(),
            function (Invocation $call) use (&$context, &$calls): mixed {
                $calls++;

                return Formula::parse('SELF()')->evaluate($context)->value;
            },
        ));

        return $context = $this->context()->withFunctions($functions);
    }

    public function test_formulas_calling_formulas_are_stopped(): void
    {
        $calls = 0;
        $context = $this->recursive($calls);

        $this->assertStops(
            'limit.recursion',
            'The formula was stopped: more than 8 formulas were running inside one another.',
            fn () => Formula::parse('SELF()')->evaluate($context),
        );
        $this->assertSame(8, $calls);
    }

    public function test_a_formula_inside_cannot_ask_for_more_room(): void
    {
        $calls = 0;
        $context = $this->recursive($calls);
        $outer = new Limits(recursion: 2);
        $inner = $context->withLimits(new Limits(recursion: 100));
        $functions = $context->functions()->add(new FormulaFunction(
            ['MORE'],
            [],
            Type::number(),
            static fn (): mixed => Formula::parse('SELF()')->evaluate($inner)->value,
        ));

        $this->assertStops(
            'limit.recursion',
            'The formula was stopped: more than 2 formulas were running inside one another.',
            fn () => Formula::parse('MORE()')->evaluate($context->withFunctions($functions)->withLimits($outer)),
        );
    }

    /** A context whose INNER() works out `1 + 2` with limits of its own. */
    private function nesting(Limits $inner): Context
    {
        $context = $this->context()->withLimits($inner);
        $functions = Functions::standard()->add(new FormulaFunction(
            ['INNER'],
            [],
            Type::number(),
            static fn (): mixed => Formula::parse('1 + 2')->evaluate($context)->value,
        ));

        return $this->context()->withFunctions($functions);
    }

    public function test_formulas_run_inside_share_the_steps(): void
    {
        $context = $this->nesting(new Limits(steps: 1000000));
        $formula = Formula::parse('INNER() + INNER()');

        // The sum, two calls and three parts inside each.
        $this->assertSame('6', (string) $formula->evaluate($context->withLimits(new Limits(steps: 9)))->value);

        $this->assertSame('limit.steps', rescue(
            fn (): string => (string) $formula->evaluate($context->withLimits(new Limits(steps: 8)))->value,
            static fn (FormulaError $error): string => $error->problem()->code,
            false,
        ));
    }

    public function test_any_number_of_formulas_may_run_inside_one_after_another(): void
    {
        $formula = Formula::parse(implode(' + ', array_fill(0, 20, 'INNER()')));
        $context = $this->nesting(new Limits)->withLimits(new Limits(recursion: 2));

        $this->assertSame('60', (string) $formula->evaluate($context)->value);
    }

    public function test_a_run_that_was_stopped_leaves_nothing_behind(): void
    {
        $limits = new Limits(steps: 3, recursion: 1);

        $this->assertStops('limit.steps', 'Line 1, column 9: The formula was stopped: it took more than 3 steps.', fn () => $this->worked('1 + 2 + 3', $limits));
        $this->assertSame(self::num(3), $this->worked('1 + 2', $limits));
        $this->assertSame(self::num(3), $this->worked('1 + 2', $limits));
    }

    public function test_no_run_at_all_may_be_allowed(): void
    {
        $this->assertStops(
            'limit.recursion',
            'The formula was stopped: more than 0 formulas were running inside one another.',
            fn () => $this->worked('1', new Limits(recursion: 0)),
        );
    }
}
