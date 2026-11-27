<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Feature;

use Atrium\Language\Checking\Checker;
use Atrium\Language\Context;
use Atrium\Language\Formula;
use Atrium\Language\FormulaError;
use Atrium\Language\Functions\FormulaFunction;
use Atrium\Language\Functions\Functions;
use Atrium\Language\Functions\Invocation;
use Atrium\Language\Functions\Parameter;
use Atrium\Language\Ir\Call;
use Atrium\Language\Ir\Condition;
use Atrium\Language\Ir\Each;
use Atrium\Language\Ir\Field;
use Atrium\Language\Ir\Literal;
use Atrium\Language\Ir\Member;
use Atrium\Language\Ir\Node;
use Atrium\Language\Ir\Variable;
use Atrium\Language\Limits;
use Atrium\Language\Position;
use Atrium\Language\Tests\TestCase;
use Atrium\Language\Types\Type;
use PHPUnit\Framework\Attributes\DataProvider;

final class CheckerTest extends TestCase
{
    /** The order of the other tests, and a field whose type is only known while running. */
    private function open(): Context
    {
        return $this->context([], $this->schema()->field('x', Type::any()));
    }

    /** @return iterable<string,array{string,string}> */
    public static function types(): iterable
    {
        yield 'a number' => ['1', 'number'];
        yield 'a text' => ['"a"', 'text'];
        yield 'a yes' => ['TRUE', 'yes/no'];
        yield 'a date' => ['1.1.2026', 'date'];
        yield 'a number field' => ['Amount', 'number'];
        yield 'a text field' => ['Status', 'text'];
        yield 'records' => ['Lines', 'list of record'];
        yield 'a member of records' => ['Lines.Price', 'list of number'];
        yield 'numbers added' => ['Amount + 1 + Discount', 'number'];
        yield 'a date and days' => ['Due + 1', 'date'];
        yield 'days and a date' => ['1 + Due', 'date'];
        yield 'days around a date' => ['1 + Due + 2', 'date'];
        yield 'a date less days' => ['Due - 1', 'date'];
        yield 'a date less a date' => ['Due - Due', 'number'];
        yield 'a number less a number' => ['Amount - 1', 'number'];
        yield 'negated' => ['-Amount', 'number'];
        yield 'percent' => ['5%', 'number'];
        yield 'multiplied' => ['Amount * 2 * 3', 'number'];
        yield 'divided' => ['Amount / 2', 'number'];
        yield 'raised' => ['Amount ^ 2', 'number'];
        yield 'joined' => ['Status & Amount & Paid & Due', 'text'];
        yield 'equal' => ['Amount = 1', 'yes/no'];
        yield 'equal yes' => ['Paid = TRUE', 'yes/no'];
        yield 'not equal' => ['Paid <> FALSE', 'yes/no'];
        yield 'less' => ['Amount < 1', 'yes/no'];
        yield 'less or equal' => ['Status <= "a"', 'yes/no'];
        yield 'greater' => ['Due > Due', 'yes/no'];
        yield 'greater or equal' => ['Amount >= 1', 'yes/no'];
        yield 'both' => ['Paid AND Paid', 'yes/no'];
        yield 'either' => ['Paid OR Paid', 'yes/no'];
        yield 'not' => ['NOT(Paid)', 'yes/no'];
        yield 'a condition' => ['IF(Paid; 1; 2)', 'number'];
        yield 'a condition without otherwise' => ['IF(Paid; "a")', 'text'];
        yield 'a condition inside another' => ['IF(Paid; IF(Paid; 1); Amount)', 'number'];
        yield 'a function' => ['ROUND(Amount; 2)', 'number'];
        yield 'a number or a number' => ['IFBLANK(Amount; 0)', 'number'];
        yield 'a number or a text' => ['IFBLANK(Amount; "none")', 'any value'];
        yield 'anything added' => ['X + 1', 'any value'];
        yield 'anything added to anything' => ['X + X', 'any value'];
        yield 'anything added to a date' => ['Due + X', 'date'];
        yield 'a date added to anything' => ['X + Due', 'date'];
        yield 'less from anything' => ['X - 1', 'any value'];
        yield 'a date from anything' => ['X - Due', 'any value'];
        yield 'anything from a date' => ['Due - X', 'any value'];
        yield 'anything from a number' => ['Amount - X', 'number'];
        yield 'anything compared' => ['X = 1', 'yes/no'];
        yield 'anything ordered' => ['X < Due', 'yes/no'];
        yield 'a member of anything' => ['X.Name', 'any value'];
        yield 'anything as a condition' => ['IF(X; 1)', 'number'];
        yield 'either of two' => ['IF(Paid; X; "a")', 'any value'];
    }

    #[DataProvider('types')]
    public function test_a_sound_formula_passes_and_has_a_type(string $source, string $type): void
    {
        $check = Formula::parse($source)->check($this->open());

        $this->assertSame([], $check->messages('en'));
        $this->assertTrue($check->passes());
        $this->assertSame($type, $check->type->describe('en'));
    }

    /** @return iterable<string,array{string,list<array{string,string}>}> */
    public static function mistakes(): iterable
    {
        $at = static fn (int $column, string $message): string => "Line 1, column {$column}: {$message}";
        $operand = static fn (int $column, string $operator, string $expected, string $actual): array => ['check.operand', $at($column, "The operator “{$operator}” needs: {$expected}, found: {$actual}.")];
        $compare = static fn (int $column, string $operator, string $left, string $right): array => ['check.compare', $at($column, "“{$operator}” cannot compare these: {$left} and {$right}.")];
        $unknown = static fn (int $column, string $name): array => ['check.unknown_field', $at($column, "There is no field “{$name}”.")];
        $argument = static fn (int $column, int $position, string $function, string $expected, string $actual): array => ['check.argument', $at($column, "Argument {$position} of the function {$function} needs: {$expected}, found: {$actual}.")];
        $few = ['arguments.too_few', $at(5, 'The function ROUND needs more arguments: at least 2, given: 1.')];
        $many = ['arguments.too_many', $at(5, 'The function ROUND takes fewer arguments: at most 2, given: 3.')];
        $branches = ['check.branches', $at(5, 'Both results of a condition must be of one type, found: number and text.')];

        yield 'an unknown field' => ['Nothing + 1', [$unknown(1, 'Nothing')]];
        yield 'an unknown field is any type' => ['NOT(Nothing)', [$unknown(5, 'Nothing')]];
        yield 'every problem' => ['Nothing + Status', [$unknown(1, 'Nothing'), $operand(11, '+', 'number', 'text')]];
        yield 'an unknown function, after its arguments' => ['NOPE(1; Missing)', [$unknown(9, 'Missing'), ['check.unknown_function', $at(1, 'There is no function “NOPE”.')]]];
        yield 'an unknown function is any type' => ['NOT(NOPE())', [['check.unknown_function', $at(5, 'There is no function “NOPE”.')]]];
        yield 'too few arguments, and any type' => ['NOT(ROUND(1))', [$few]];
        yield 'too many arguments, and any type' => ['NOT(ROUND(1; 2; 3))', [$many]];
        yield 'the first argument' => ['ROUND("a"; 2)', [$argument(7, 1, 'ROUND', 'number', 'text')]];
        yield 'the second argument' => ['ROUND(1; Paid)', [$argument(10, 2, 'ROUND', 'number', 'yes/no')]];
        yield 'a variadic argument' => ['SUM(1; 2; "a")', [$argument(11, 3, 'SUM', 'number or list of number', 'text')]];
        yield 'a list of the wrong items' => ['SUM(Lines.Title)', [$argument(11, 1, 'SUM', 'number or list of number', 'list of text')]];
        yield 'a function keeps its type with a wrong argument' => ['NOT(ABS("a"))', [$argument(9, 1, 'ABS', 'number', 'text'), $operand(5, 'NOT', 'yes/no', 'number')]];
        yield 'a condition that is a number' => ['IF(1; 2; 3)', [['check.condition', $at(4, 'A condition must be yes or no, found: number.')]]];
        yield 'two types of results, and any type' => ['NOT(IF(Paid; 1; "a"))', [$branches]];
        yield 'multiplying a text' => ['Status * 2', [$operand(1, '*', 'number', 'text')]];
        yield 'multiplying by a text, the third' => ['1 * 2 * Status', [$operand(9, '*', 'number', 'text')]];
        yield 'dividing by a text' => ['2 / Status', [$operand(5, '/', 'number', 'text')]];
        yield 'negating a text' => ['-Status', [$operand(2, '-', 'number', 'text')]];
        yield 'percent of a text' => ['Status%', [$operand(1, '%', 'number', 'text')]];
        yield 'raising a text' => ['Status ^ 1', [$operand(1, '^', 'number', 'text')]];
        yield 'a number and' => ['Amount AND Paid', [$operand(1, 'AND', 'yes/no', 'number')]];
        yield 'or a number' => ['Paid OR 1', [$operand(9, 'OR', 'yes/no', 'number')]];
        yield 'not a number' => ['NOT(1)', [$operand(5, 'NOT', 'yes/no', 'number')]];
        yield 'both operands' => ['1 AND 2', [$operand(1, 'AND', 'yes/no', 'number'), $operand(7, 'AND', 'yes/no', 'number')]];
        yield 'multiplication is a number' => ['NOT(2 * 2)', [$operand(7, 'NOT', 'yes/no', 'number')]];
        yield 'and is yes or no' => ['(Paid AND Paid) * 1', [$operand(7, '*', 'number', 'yes/no')]];
        yield 'joining records' => ['Lines & "a"', [$operand(1, '&', 'number or text or yes/no or date', 'list of record')]];
        yield 'joined text is a text' => ['NOT("a" & "b")', [$operand(9, 'NOT', 'yes/no', 'text')]];
        yield 'two dates added' => ['Due + Due', [$operand(7, '+', 'number', 'date')]];
        yield 'a third date' => ['Due + 1 + Due', [$operand(11, '+', 'number', 'date')]];
        yield 'a text added' => ['Status + 1', [$operand(1, '+', 'number', 'text')]];
        yield 'a text added to a date' => ['Due + Status', [$operand(7, '+', 'number', 'text')]];
        yield 'a date from a number' => ['Amount - Due', [$operand(10, '-', 'number', 'date')]];
        yield 'from a text' => ['Status - 1', [$operand(1, '-', 'number', 'text')]];
        yield 'both sides of a subtraction' => ['Status - Paid', [$operand(1, '-', 'number', 'text'), $operand(10, '-', 'number', 'yes/no')]];
        yield 'a text from a date' => ['Due - Status', [$operand(7, '-', 'number', 'text')]];
        yield 'a text from anything' => ['X - Status', [$operand(5, '-', 'number or date', 'text')]];
        yield 'a number against a text' => ['Amount = Status', [$compare(8, '=', 'number', 'text')]];
        yield 'a failed comparison is yes or no' => ['IF(Amount = Status; 1)', [$compare(11, '=', 'number', 'text')]];
        yield 'lists' => ['Lines = Lines', [$compare(7, '=', 'list of record', 'list of record')]];
        yield 'a list and a number' => ['Lines <> 1', [$compare(7, '<>', 'list of record', 'number')]];
        yield 'yes and no have no order' => ['Paid < Paid', [$compare(6, '<', 'yes/no', 'yes/no')]];
        yield 'no order, or equal' => ['Paid <= TRUE', [$compare(6, '<=', 'yes/no', 'yes/no')]];
        yield 'greater of two kinds' => ['Due > 1', [$compare(5, '>', 'date', 'number')]];
        yield 'greater or equal of two kinds' => ['Status >= Due', [$compare(8, '>=', 'text', 'date')]];
        yield 'a member of a number' => ['Amount.Price', [['check.not_a_record', $at(8, '“.Price” needs a related record before the dot, found: number.')]]];
        yield 'an unknown member' => ['Lines.Nothing', [['check.unknown_member', $at(7, 'The related record has no field “Nothing”.')]]];
    }

    /** @param  list<array{string,string}>  $problems */
    #[DataProvider('mistakes')]
    public function test_it_says_what_is_wrong_and_where(string $source, array $problems): void
    {
        $this->assertProblems($problems, $source, $this->open());
    }

    public function test_the_type_after_a_mistake(): void
    {
        $type = fn (string $source): string => Formula::parse($source)->check($this->open())->type->describe('en');

        $this->assertSame('date', $type('Due - Status'));
        $this->assertSame('any value', $type('X - Status'));
        $this->assertSame('number', $type('Amount - Due'));
        $this->assertSame('list of any value', $type('Lines.Nothing'));
        $this->assertSame('date', $type('Due + Due'));
    }

    /** @return iterable<string,array{string}> */
    public static function counts(): iterable
    {
        yield 'the fewest' => ['ROUND(1; 2) + WEEKDAY(Due)'];
        yield 'the most' => ['WEEKDAY(Due; 2)'];
        yield 'any number' => ['SUM(1; 2; 3; 4; 5; 6)'];
        yield 'none' => ['TODAY()'];
    }

    #[DataProvider('counts')]
    public function test_as_many_arguments_as_a_function_takes(string $source): void
    {
        $this->assertProblems([], $source);
    }

    public function test_it_lists_the_functions_a_formula_calls(): void
    {
        $check = Formula::parse('SUM(1) + СУММ(2) + ABS(SUM(3))')->check($this->context());

        $this->assertSame(['SUM', 'СУММ', 'ABS'], array_keys($check->functions));
        $this->assertSame($check->functions['SUM'], $check->functions['СУММ']);
        $this->assertSame('ABS', $check->functions['ABS']->name());
    }

    public function test_a_function_may_need_a_permission(): void
    {
        $functions = Functions::standard()->add(new FormulaFunction(
            ['SECRET'],
            [Parameter::of(Type::number())],
            Type::number(),
            static fn (Invocation $call): int => 1,
            'secrets.read',
        ));
        $context = $this->context()->withFunctions($functions);
        $refused = ['check.permission', 'Line 1, column 1: The function SECRET needs the permission “secrets.read”.'];

        $this->assertProblems([$refused], 'SECRET(1)', $context);
        $this->assertProblems([$refused], 'SECRET(1)', $context->allow('secrets', 'read'));
        $this->assertProblems([], 'SECRET(1)', $context->allow('other', 'secrets.read'));

        // The rest of the call is checked all the same.
        $this->assertProblems(
            [$refused, ['check.argument', 'Line 1, column 8: Argument 1 of the function SECRET needs: number, found: text.']],
            'SECRET("a")',
            $context,
        );
        $this->assertStops($refused[0], $refused[1], fn () => Formula::parse('SECRET(1)')->evaluate($context));
    }

    public function test_the_depth_of_a_tree_is_limited(): void
    {
        $context = $this->context()->withLimits(new Limits(depth: 3));

        $this->assertProblems([], 'ABS(ABS(1))', $context);
        // Only the depth counts, not how many there are beside each other.
        $this->assertProblems([], 'SUM(ABS(1); ABS(2); ABS(3); ABS(4))', $context);
        $this->assertProblems(
            [['check.depth', 'Line 1, column 13: The formula is nested deeper than the allowed 3 levels.']],
            'ABS(ABS(ABS(1)))',
            $context,
        );
    }

    public function test_the_depth_allowed_by_default(): void
    {
        $this->assertProblems([], str_repeat('ABS(', 63).'1'.str_repeat(')', 63));

        $deep = new Literal(true);

        for ($level = 0; $level < 64; $level++) {
            $deep = new Condition(new Literal(true), $deep);
        }

        $this->assertProblems(
            [['check.depth', 'The formula is nested deeper than the allowed 64 levels.'], ['check.depth', 'The formula is nested deeper than the allowed 64 levels.']],
            Formula::fromNode($deep),
        );
    }

    private static function item(string $variable, string $name): Node
    {
        return new Member(new Variable($variable), $name);
    }

    public function test_a_loop_names_each_item_of_a_list(): void
    {
        $loop = new Each(new Field('Lines'), 'line', self::item('line', 'Price'), self::item('line', 'Title'));
        $check = Formula::fromNode(new Each(new Field('Lines'), 'line', self::item('line', 'Price')))->check($this->context());

        $this->assertSame([], $check->messages('en'));
        $this->assertSame('list of number', $check->type->describe('en'));
        $this->assertProblems(
            [['check.condition', 'A condition must be yes or no, found: text.']],
            Formula::fromNode($loop),
        );
        $this->assertProblems(
            [],
            Formula::fromNode(new Each(new Field('Lines'), 'line', new Variable('line'), new Literal(true))),
        );
    }

    /** @return iterable<string,array{Node}> */
    public static function unknownLists(): iterable
    {
        yield 'anything' => [new Field('x')];
        yield 'nothing' => [new Literal(null)];
    }

    #[DataProvider('unknownLists')]
    public function test_a_loop_may_go_over_what_is_only_known_while_running(Node $source): void
    {
        $check = Formula::fromNode(new Each($source, 'v', new Variable('v')))->check($this->open());

        $this->assertSame([], $check->messages('en'));
        $this->assertSame('list of any value', $check->type->describe('en'));
    }

    public function test_a_loop_needs_a_list(): void
    {
        $loop = new Each(new Field('amount', new Position(2, 3)), 'v', new Member(new Variable('v'), 'a'));

        $this->assertProblems(
            [['check.not_a_list', 'Line 2, column 3: A loop needs a list, found: number.']],
            Formula::fromNode($loop),
        );
    }

    public function test_the_name_of_an_item_ends_with_its_loop(): void
    {
        $this->assertProblems(
            [['check.unknown_variable', 'There is no item “v” here: no loop around this place has that name.']],
            Formula::fromNode(new Call('COUNT', [new Each(new Field('lines'), 'v', new Variable('v')), new Variable('v')])),
        );

        // A loop inside gives the name back what it meant outside.
        $inner = new Each(new Member(new Field('rows'), 'price'), 'v', new Variable('v'));
        $outer = new Each(new Field('lines'), 'v', new Call('COUNT', [$inner, self::item('v', 'title')]));

        $this->assertProblems([], Formula::fromNode($outer));
    }

    public function test_a_branch_may_be_empty(): void
    {
        $check = Formula::fromNode(new Condition(new Literal(true), new Literal(null), new Literal('a')))->check($this->context());

        $this->assertTrue($check->passes());
        $this->assertSame('text', $check->type->describe('en'));
    }

    public function test_a_member_of_nothing_and_of_lists_of_lists(): void
    {
        $this->assertProblems([], Formula::fromNode(new Member(new Literal(null), 'a')));

        $nested = $this->context([], $this->schema()->field('groups', Type::listOf(Type::listOf(Type::record($this->schema())))));
        $check = Formula::parse('Groups.Amount')->check($nested);

        $this->assertSame('list of list of number', $check->type->describe('en'));
    }

    public function test_evaluating_refuses_a_formula_with_all_its_problems(): void
    {
        $formula = Formula::parse('Nothing + Status');

        try {
            $formula->evaluate($this->context());
            $this->fail('Nothing stopped.');
        } catch (FormulaError $error) {
            $this->assertSame($formula->check($this->context())->messages('en'), array_map(
                static fn ($problem): string => $problem->message('en'),
                $error->problems,
            ));
            $this->assertCount(2, $error->problems);
            $this->assertSame($error->problems[0], $error->problem());
        }
    }

    public function test_problems_are_said_in_any_of_the_languages(): void
    {
        $check = Formula::parse('Статус * 2')->check($this->context());

        $this->assertSame(['Строка 1, позиция 1: Оператору «*» нужен тип: число, а получен: текст.'], $check->messages('ru'));

        $this->app->setLocale('ru');

        $this->assertSame($check->messages('ru'), $check->messages());
    }

    /** A loop inside a loop, this many times: a list of lists of numbers. */
    private static function lists(int $deep): Node
    {
        $node = new Literal(true);

        for ($level = 0; $level < $deep; $level++) {
            $node = new Each(new Field('lines'), 'v', $node);
        }

        return $node;
    }

    public function test_lists_inside_one_another_are_limited(): void
    {
        $this->assertProblems([], Formula::fromNode(self::lists(8)));

        $deeper = Formula::fromNode(self::lists(9));

        $this->assertProblems([['check.deep_list', 'Lists are inside one another deeper than 8 levels.']], $deeper);
        $this->assertSame('any value', $deeper->check($this->context())->type->describe('en'));
        $this->assertSame(8, Checker::LISTS);
    }

    public function test_only_so_many_problems_are_reported(): void
    {
        $unknown = static fn (int $count): string => 'CONCAT('.implode('; ', array_map(
            static fn (int $number): string => "x{$number}",
            range(1, $count),
        )).')';
        $names = fn (int $count): array => array_map(
            static fn ($problem): mixed => $problem->params['name'],
            Formula::parse($unknown($count))->check($this->context())->problems,
        );

        $this->assertSame(['x1', 'x2'], $names(2));
        $this->assertCount(100, $names(100));
        $this->assertSame('x100', $names(101)[99]);
        $this->assertCount(100, $names(101));
        $this->assertFalse(Formula::parse($unknown(101))->check($this->context())->passes());
        $this->assertSame(100, Checker::PROBLEMS);
    }
}
