<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Unit;

use Atrium\Language\Formula;
use Atrium\Language\Ir\Call;
use Atrium\Language\Ir\Codec;
use Atrium\Language\Ir\Condition;
use Atrium\Language\Ir\Each;
use Atrium\Language\Ir\Field;
use Atrium\Language\Ir\Literal;
use Atrium\Language\Ir\Member;
use Atrium\Language\Ir\Node;
use Atrium\Language\Ir\Operation;
use Atrium\Language\Ir\Operator;
use Atrium\Language\Ir\Shape;
use Atrium\Language\Ir\Variable;
use Atrium\Language\Position;
use Atrium\Language\Tests\TestCase;
use Atrium\Language\Values\Date;
use Brick\Math\BigDecimal;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

final class TreeTest extends TestCase
{
    /** @return iterable<string,array{Node,list<string>,array<string,mixed>}> */
    public static function nodes(): iterable
    {
        $a = new Field('a');
        $b = new Field('b');
        $c = new Field('c');
        $field = static fn (string $name): array => ['node' => 'field', 'name' => $name];

        yield 'an empty literal' => [new Literal(null), [], ['node' => 'literal', 'type' => 'empty']];
        yield 'yes' => [new Literal(true), [], ['node' => 'literal', 'type' => 'boolean', 'value' => true]];
        yield 'no' => [new Literal(false), [], ['node' => 'literal', 'type' => 'boolean', 'value' => false]];
        yield 'a text' => [new Literal('x'), [], ['node' => 'literal', 'type' => 'text', 'value' => 'x']];
        yield 'an empty text' => [new Literal(''), [], ['node' => 'literal', 'type' => 'text', 'value' => '']];
        yield 'a number' => [new Literal(BigDecimal::of('-1.50')), [], ['node' => 'literal', 'type' => 'number', 'value' => '-1.5']];
        yield 'a date' => [new Literal(Date::of(2026, 11, 28)), [], ['node' => 'literal', 'type' => 'date', 'value' => '2026-11-28']];
        yield 'a field' => [$a, [], $field('a')];
        yield 'a variable' => [new Variable('v'), [], ['node' => 'variable', 'name' => 'v']];
        yield 'a member' => [new Member($a, 'x'), ['a'], ['node' => 'member', 'of' => $field('a'), 'name' => 'x']];
        yield 'an operation' => [
            new Operation(Operator::Add, [$a, $b]),
            ['a', 'b'],
            ['node' => 'operation', 'operator' => 'add', 'operands' => [$field('a'), $field('b')]],
        ];
        yield 'a call' => [
            new Call('F', [$a, $b]),
            ['a', 'b'],
            ['node' => 'call', 'function' => 'F', 'arguments' => [$field('a'), $field('b')]],
        ];
        yield 'a call of nothing' => [new Call('F'), [], ['node' => 'call', 'function' => 'F', 'arguments' => []]];
        yield 'a condition' => [new Condition($a, $b), ['a', 'b'], ['node' => 'condition', 'when' => $field('a'), 'then' => $field('b')]];
        yield 'a condition with otherwise' => [
            new Condition($a, $b, $c),
            ['a', 'b', 'c'],
            ['node' => 'condition', 'when' => $field('a'), 'then' => $field('b'), 'otherwise' => $field('c')],
        ];
        yield 'a loop' => [
            new Each($a, 'v', $b),
            ['a', 'b'],
            ['node' => 'each', 'source' => $field('a'), 'variable' => 'v', 'body' => $field('b')],
        ];
        yield 'a loop with a filter' => [
            new Each($a, 'v', $b, $c),
            ['a', 'b', 'c'],
            ['node' => 'each', 'source' => $field('a'), 'variable' => 'v', 'body' => $field('b'), 'where' => $field('c')],
        ];
    }

    /**
     * @param  list<string>  $children
     * @param  array<string,mixed>  $data
     */
    #[DataProvider('nodes')]
    public function test_a_node_gives_its_children_and_its_data(Node $node, array $children, array $data): void
    {
        $this->assertSame($children, array_map(static fn (Field $child): string => $child->name, $node->children()));
        $this->assertSame($data, $node->data());
        $this->assertSame($data, $node->toArray());
        $this->assertNull($node->at);
    }

    /** @return iterable<string,array{Node}> */
    public static function each(): iterable
    {
        foreach (self::nodes() as $name => [$node]) {
            yield $name => [$node];
        }
    }

    #[DataProvider('each')]
    public function test_a_node_is_stored_and_read_back_the_same(Node $node): void
    {
        $read = Formula::fromJson(Formula::fromNode($node)->toJson())->root;

        $this->assertSame($node->toArray(), $read->toArray());
        $this->assertSame($node::class, $read::class);
    }

    #[DataProvider('each')]
    public function test_a_position_is_stored_with_the_node(Node $node): void
    {
        $arguments = get_object_vars($node);
        $arguments['at'] = new Position(3, 14);
        $placed = new ($node::class)(...$arguments);

        $this->assertSame([...$node->data(), 'at' => [3, 14]], $placed->toArray());
        $this->assertEquals(new Position(3, 14), Codec::decode(Codec::encode($placed))->at);
    }

    public function test_a_literal_keeps_numbers_in_their_bounds(): void
    {
        $this->assertSame('0.33333333333333333333', (string) (new Literal(BigDecimal::of('0.333333333333333333333')))->value);
        $this->assertStops(
            'value.too_large',
            'Line 2, column 5: A number is too large: it has more than 40 digits before the decimal point.',
            fn () => new Literal(BigDecimal::of('1e40'), new Position(2, 5)),
        );
    }

    /** @return iterable<string,array{Operator,string,list<int>,list<int>}> */
    public static function operators(): iterable
    {
        $one = [[1], [0, 2]];
        $many = [[1, 2, 3, 40], [0]];
        $two = [[2], [0, 1, 3]];

        yield 'add' => [Operator::Add, '+', ...$many];
        yield 'subtract' => [Operator::Subtract, '-', ...$two];
        yield 'multiply' => [Operator::Multiply, '*', ...$many];
        yield 'divide' => [Operator::Divide, '/', ...$two];
        yield 'power' => [Operator::Power, '^', ...$two];
        yield 'negate' => [Operator::Negate, '-', ...$one];
        yield 'percent' => [Operator::Percent, '%', ...$one];
        yield 'concat' => [Operator::Concat, '&', ...$many];
        yield 'equal' => [Operator::Equal, '=', ...$two];
        yield 'not equal' => [Operator::NotEqual, '<>', ...$two];
        yield 'less' => [Operator::Less, '<', ...$two];
        yield 'less or equal' => [Operator::LessOrEqual, '<=', ...$two];
        yield 'greater' => [Operator::Greater, '>', ...$two];
        yield 'greater or equal' => [Operator::GreaterOrEqual, '>=', ...$two];
        yield 'and' => [Operator::And, 'AND', ...$many];
        yield 'or' => [Operator::Or, 'OR', ...$many];
        yield 'not' => [Operator::Not, 'NOT', ...$one];
    }

    /**
     * @param  list<int>  $takes
     * @param  list<int>  $refuses
     */
    #[DataProvider('operators')]
    public function test_an_operator_has_a_sign_and_a_number_of_operands(Operator $operator, string $symbol, array $takes, array $refuses): void
    {
        $this->assertSame($symbol, $operator->symbol());

        foreach ($takes as $count) {
            $this->assertTrue($operator->takes($count), "{$count} operands");
        }

        foreach ($refuses as $count) {
            $this->assertFalse($operator->takes($count), "{$count} operands");
        }
    }

    public function test_an_operation_cannot_be_built_with_a_wrong_number_of_operands(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The operator [divide] cannot take 1 operands.');

        new Operation(Operator::Divide, [new Field('a')]);
    }

    private static function chain(int $depth): Node
    {
        $node = new Literal(true, new Position(1, 1));

        for ($level = 2; $level <= $depth; $level++) {
            $node = new Operation(Operator::Not, [$node], new Position($level, 1));
        }

        return $node;
    }

    public function test_a_tree_has_a_greatest_depth(): void
    {
        $this->assertSame(200, Shape::DEPTH);
        Formula::fromNode(self::chain(200));

        // The deepest node is the first one written.
        $this->assertStops(
            'formula.too_deep',
            'Line 1, column 1: The formula is nested deeper than 200 levels.',
            fn () => Formula::fromNode(self::chain(201)),
        );
    }

    public function test_a_tree_has_a_greatest_number_of_nodes(): void
    {
        $this->assertSame(5000, Shape::NODES);
        $arguments = array_fill(0, 4999, new Literal(null));

        Formula::fromNode(new Call('F', $arguments));

        $this->assertStops(
            'formula.too_big',
            'The formula has more than 5000 parts.',
            fn () => Formula::fromNode(new Call('F', [...$arguments, new Literal(null)])),
        );
    }

    public function test_a_node_used_many_times_counts_each_time(): void
    {
        $node = new Literal(null);

        for ($level = 0; $level < 13; $level++) {
            $node = new Operation(Operator::Add, [$node, $node]);
        }

        $this->assertStops('formula.too_big', 'The formula has more than 5000 parts.', fn () => Formula::fromNode($node));
    }
}
