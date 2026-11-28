<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Unit;

use Atrium\Language\Formula;
use Atrium\Language\FormulaError;
use Atrium\Language\Parser\Parser;
use Atrium\Language\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ParserTest extends TestCase
{
    /** @return iterable<string,array{string,string}> */
    public static function trees(): iterable
    {
        // Values and names.
        yield 'a number' => ['007.50', '7.5'];
        yield 'a text' => ['"a ""b"""', "'a \"b\"'"];
        yield 'a date, the day first' => ['5.1.2026', 'date:2026-01-05'];
        yield 'a date, the year first' => ['#2026-01-05#', 'date:2026-01-05'];
        yield 'yes' => ['TRUE', 'yes'];
        yield 'yes in Russian, in any case' => ['Истина', 'yes'];
        yield 'no' => ['false', 'no'];
        yield 'no in Russian' => ['ЛОЖЬ', 'no'];
        yield 'a name' => ['Amount', '[Amount]'];
        yield 'a name of several words' => ["Дата   \n отгрузки", '[Дата отгрузки]'];
        yield 'a name ending in a number' => ['Stage 2', '[Stage 2]'];
        yield 'a name with a number inside' => ['Stage 2 done', '[Stage 2 done]'];
        yield 'lowercase operator words are part of a name' => ['Terms and conditions', '[Terms and conditions]'];
        yield 'a boolean word inside a name' => ['True story', '[True story]'];
        yield 'a name in brackets' => ['[Total (net), €]', '[Total (net), €]'];
        yield 'TRUE in brackets is a name' => ['[TRUE]', '[TRUE]'];

        // Fields of related records.
        yield 'a field of a record' => ['Client.Name', '(. [Client] Name)'];
        yield 'fields of fields' => ['a.b.c', '(. (. [a] b) c)'];
        yield 'bracketed and spaced members' => ['[a b].[c d].e f 2', '(. (. [a b] c d) e f 2)'];
        yield 'a field of what a call gives' => ['F(1).x', '(. (call:F 1) x)'];

        // Calls.
        yield 'a call without arguments' => ['TODAY()', '(call:TODAY)'];
        yield 'a call keeps the name as written' => ['сУмм(a; b, c)', '(call:сУмм [a] [b] [c])'];
        yield 'a function of several words' => ['my total(1)', '(call:my total 1)'];
        yield 'IF with two parts' => ['IF(a; 1)', '(if [a] 1)'];
        yield 'IF with three parts' => ['если(a, 1, 2)', '(if [a] 1 2)'];
        yield 'AND as a function' => ['AND(a; b; c)', '(and [a] [b] [c])'];
        yield 'AND of one, in Russian' => ['и(a)', '(and [a])'];
        yield 'OR as a function' => ['Or(a; b)', '(or [a] [b])'];
        yield 'OR as a function in Russian' => ['ИЛИ(a)', '(or [a])'];
        yield 'NOT as a function' => ['not(a)', '(not [a])'];
        yield 'NOT as a function in Russian' => ['НЕ(a)', '(not [a])'];
        yield 'NOT( binds as a call' => ['NOT(a) = b', '(equal (not [a]) [b])'];
        yield 'an operator may begin a call' => ['1 + AND(a)', '(add 1 (and [a]))'];

        // Operators.
        foreach (['=' => 'equal', '<>' => 'not_equal', '<' => 'less', '<=' => 'less_or_equal', '>' => 'greater', '>=' => 'greater_or_equal', '-' => 'subtract', '/' => 'divide', '^' => 'power', '+' => 'add', '*' => 'multiply', '&' => 'concat'] as $sign => $operator) {
            yield "the operator {$sign}" => ["1 {$sign} 2", "({$operator} 1 2)"];
        }

        yield 'AND' => ['a AND b', '(and [a] [b])'];
        yield 'AND in Russian' => ['a И b', '(and [a] [b])'];
        yield 'OR' => ['a OR b', '(or [a] [b])'];
        yield 'OR in Russian' => ['a ИЛИ b', '(or [a] [b])'];
        yield 'NOT' => ['NOT a', '(not [a])'];
        yield 'NOT in Russian' => ['НЕ a', '(not [a])'];
        yield 'NOT twice' => ['NOT NOT a', '(not (not [a]))'];
        yield 'percent' => ['50%', '(percent 50)'];
        yield 'percent twice' => ['5%%', '(percent (percent 5))'];
        yield 'a minus' => ['-a', '(negate [a])'];
        yield 'two minuses' => ['--1', '(negate (negate 1))'];

        // Many operands of one operator make one node.
        yield 'a long sum' => ['1+2+3+4', '(add 1 2 3 4)'];
        yield 'a long product' => ['1*2*3', '(multiply 1 2 3)'];
        yield 'a long text' => ['a&b&c', '(concat [a] [b] [c])'];
        yield 'a long AND' => ['a AND b AND c', '(and [a] [b] [c])'];
        yield 'a long OR' => ['a OR b OR c', '(or [a] [b] [c])'];
        yield 'brackets of the same operator join too' => ['(1+2)+3', '(add 1 2 3)'];
        yield 'brackets on the right stay' => ['1+(2+3)', '(add 1 (add 2 3))'];
        yield 'subtraction goes from the left' => ['1-2-3', '(subtract (subtract 1 2) 3)'];
        yield 'division goes from the left' => ['8/4/2', '(divide (divide 8 4) 2)'];
        yield 'powers go from the left, as in Excel' => ['2^3^2', '(power (power 2 3) 2)'];
        yield 'a sum after a difference' => ['1-2+3', '(add (subtract 1 2) 3)'];
        yield 'a product after a quotient' => ['1/2*3', '(multiply (divide 1 2) 3)'];

        // What binds tighter.
        yield 'AND before OR' => ['a OR b AND c', '(or [a] (and [b] [c]))'];
        yield 'AND before OR, the other way' => ['a AND b OR c', '(or (and [a] [b]) [c])'];
        yield 'NOT before AND' => ['NOT a AND b', '(and (not [a]) [b])'];
        yield 'NOT after AND' => ['a AND NOT b', '(and [a] (not [b]))'];
        yield 'comparison before NOT' => ['NOT a = b', '(not (equal [a] [b]))'];
        yield 'concatenation before comparison' => ['a & b = c & d', '(equal (concat [a] [b]) (concat [c] [d]))'];
        yield 'a sum before concatenation' => ['1+2 & 3-4', '(concat (add 1 2) (subtract 3 4))'];
        yield 'a product before a sum' => ['1+2*3-4/5', '(subtract (add 1 (multiply 2 3)) (divide 4 5))'];
        yield 'a power before a product' => ['2*3^2/4^5', '(divide (multiply 2 (power 3 2)) (power 4 5))'];
        yield 'percent before a power' => ['2^50%', '(power 2 (percent 50))'];
        yield 'a minus before percent' => ['-5%', '(percent (negate 5))'];
        yield 'a minus before a power, as in Excel' => ['-2^2', '(power (negate 2) 2)'];
        yield 'a minus after a power' => ['2^-2', '(power 2 (negate 2))'];
        yield 'a member before a minus' => ['-a.b', '(negate (. [a] b))'];
        yield 'brackets first' => ['(1+2)*3', '(multiply (add 1 2) 3)'];
    }

    #[DataProvider('trees')]
    public function test_it_reads(string $source, string $tree): void
    {
        $this->assertSame($tree, self::tree(Parser::parse($source)));
    }

    public function test_every_node_remembers_where_it_was_written(): void
    {
        $tree = Formula::parse("IF(a.b > -1;\n  F(\"x\") & 50% ; NOT c)")->root->toArray();

        $this->assertSame([
            'node' => 'condition',
            'when' => [
                'node' => 'operation',
                'operator' => 'greater',
                'operands' => [
                    ['node' => 'member', 'of' => ['node' => 'field', 'name' => 'a', 'at' => [1, 4]], 'name' => 'b', 'at' => [1, 6]],
                    ['node' => 'operation', 'operator' => 'negate', 'operands' => [
                        ['node' => 'literal', 'type' => 'number', 'value' => '1', 'at' => [1, 11]],
                    ], 'at' => [1, 10]],
                ],
                'at' => [1, 8],
            ],
            'then' => [
                'node' => 'operation',
                'operator' => 'concat',
                'operands' => [
                    ['node' => 'call', 'function' => 'F', 'arguments' => [
                        ['node' => 'literal', 'type' => 'text', 'value' => 'x', 'at' => [2, 5]],
                    ], 'at' => [2, 3]],
                    ['node' => 'operation', 'operator' => 'percent', 'operands' => [
                        ['node' => 'literal', 'type' => 'number', 'value' => '50', 'at' => [2, 12]],
                    ], 'at' => [2, 14]],
                ],
                'at' => [2, 10],
            ],
            'otherwise' => [
                'node' => 'operation',
                'operator' => 'not',
                'operands' => [['node' => 'field', 'name' => 'c', 'at' => [2, 22]]],
                'at' => [2, 18],
            ],
            'at' => [1, 1],
        ], $tree);
    }

    public function test_joined_operators_and_other_nodes_keep_their_places(): void
    {
        $places = static fn (string $source): array => Formula::parse($source)->root->toArray()['at'];

        // The first sign of a chain stands for the whole of it.
        $this->assertSame([1, 3], $places('1 + 2 + 3'));
        $this->assertSame([1, 3], $places('1 * 2 * 3'));
        $this->assertSame([1, 3], $places('a & b & c'));
        $this->assertSame([1, 3], $places('a OR b OR c'));
        $this->assertSame([1, 3], $places('a AND b AND c'));
        $this->assertSame([1, 3], $places('1 - 2'));
        $this->assertSame([1, 3], $places('1 / 2'));
        $this->assertSame([1, 3], $places('1 ^ 2'));
        $this->assertSame([1, 2], $places(' AND(a)'));
        $this->assertSame([1, 2], $places(' TRUE'));
        $this->assertSame([1, 2], $places(' two words'));
        $this->assertSame([1, 2], $places(' [a]'));
        $this->assertSame([1, 2], $places(' 1.1.2026'));
        $this->assertSame([1, 4], $places('ab.[c]'));
    }

    /** @return iterable<string,array{string,string,string}> */
    public static function mistakes(): iterable
    {
        $unexpected = static fn (int $column, string $token): array => ['syntax.unexpected', "Line 1, column {$column}: “{$token}” was not expected here."];
        $ends = static fn (int $column): array => ['syntax.unexpected_end', "Line 1, column {$column}: The formula ends too early."];

        yield 'nothing' => ['', ...$ends(1)];
        yield 'an operator at the end' => ['1 +', ...$ends(4)];
        yield 'a bracket left open' => ['(1', ...$ends(3)];
        yield 'a call left open' => ['F(1', ...$ends(4)];
        yield 'NOT with nothing' => ['NOT', ...$ends(4)];
        yield 'a dot at the end' => ['a.', ...$ends(3)];
        yield 'two values' => ['1 2', ...$unexpected(3, '2')];
        yield 'a fraction after a name' => ['Total 1.5', ...$unexpected(7, '1.5')];
        yield 'a text of digits after a name' => ['Total "5"', ...$unexpected(7, '5')];
        yield 'a bracket too many' => ['(1))', ...$unexpected(4, ')')];
        yield 'two signs' => ['1 * / 2', ...$unexpected(5, '/')];
        yield 'a plus before a value' => ['+1', ...$unexpected(1, '+')];
        yield 'an empty argument' => ['F(1;;2)', ...$unexpected(5, ';')];
        yield 'a separator with nothing after' => ['F(1;)', ...$unexpected(5, ')')];
        yield 'a wrong sign closes a bracket' => ['(1;', ...$unexpected(3, ';')];
        yield 'a number after a dot' => ['a.1', ...$unexpected(3, '1')];
        yield 'a text after a dot' => ['a."b"', ...$unexpected(3, 'b')];
        yield 'an operator where a value belongs' => ['1 = AND', ...$unexpected(5, 'AND')];
        yield 'NOT after a comparison' => ['1 = NOT a', ...$unexpected(5, 'NOT')];
        yield 'an operator in capitals begins' => ['OR a', ...$unexpected(1, 'OR')];
        yield 'a lowercase word after a bracket' => ['(a) b', ...$unexpected(5, 'b')];
        yield 'a lowercase and' => ['(a) and b', 'syntax.operator_case', 'Line 1, column 5: “and” was not expected here. Operators are written in capital letters: AND.'];
        yield 'a lowercase or in Russian' => ['1 > 0 или 2 > 1', 'syntax.operator_case', 'Line 1, column 7: “или” was not expected here. Operators are written in capital letters: ИЛИ.'];
        yield 'a text that reads like an operator' => ['1 "and"', ...$unexpected(3, 'and')];
        yield 'two comparisons' => ['1 < 2 < 3', 'syntax.chained_comparison', 'Line 1, column 7: Only two values can be compared at once. Join comparisons with AND or OR.'];
        yield 'no such day' => ['1 + 31.2.2026', 'syntax.bad_date', 'Line 1, column 5: There is no such date: 31.2.2026.'];
        yield 'no such month' => ['#2026-13-01#', 'syntax.bad_date', 'Line 1, column 1: There is no such date: #2026-13-01#.'];
        yield 'IF with one part' => ['IF(a)', 'arguments.too_few', 'Line 1, column 1: The function IF needs more arguments: at least 2, given: 1.'];
        yield 'IF with four parts' => ['Если(a;1;2;3)', 'arguments.too_many', 'Line 1, column 1: The function Если takes fewer arguments: at most 3, given: 4.'];
        yield 'AND of nothing' => ['AND()', 'arguments.too_few', 'Line 1, column 1: The function AND needs more arguments: at least 1, given: 0.'];
        yield 'OR of nothing' => ['OR()', 'arguments.too_few', 'Line 1, column 1: The function OR needs more arguments: at least 1, given: 0.'];
        yield 'NOT of nothing' => ['NOT()', 'arguments.too_few', 'Line 1, column 1: The function NOT needs more arguments: at least 1, given: 0.'];
        yield 'NOT of two' => ['NOT(a; b)', 'arguments.too_many', 'Line 1, column 1: The function NOT takes fewer arguments: at most 1, given: 2.'];
        yield 'a number too large' => ['1 + 1'.str_repeat('0', 40), 'value.too_large', 'Line 1, column 5: A number is too large: it has more than 40 digits before the decimal point.'];
    }

    #[DataProvider('mistakes')]
    public function test_it_says_what_is_wrong_and_where(string $source, string $code, string $message): void
    {
        $this->assertStops($code, $message, fn () => Parser::parse($source));
    }

    public function test_it_refuses_text_that_is_not_utf8(): void
    {
        $this->assertStops('syntax.encoding', 'The formula is not valid UTF-8 text.', fn () => Parser::parse("\"\xff\""));
    }

    public function test_a_formula_has_a_longest_length_in_characters(): void
    {
        $longest = '"'.str_repeat('я', Parser::LENGTH - 2).'"';

        $this->assertSame(10000, mb_strlen($longest));
        $this->assertSame("'".str_repeat('я', 9998)."'", self::tree(Parser::parse($longest)));
        $this->assertStops('syntax.too_long', 'The formula is longer than 10000 characters.', fn () => Parser::parse($longest.' '));
    }

    /** @return iterable<string,array{Closure,int}> */
    public static function nestings(): iterable
    {
        // The whole formula is the first level.
        yield 'brackets' => [static fn (int $times): string => str_repeat('(', $times).'1'.str_repeat(')', $times), 65];
        yield 'calls' => [static fn (int $times): string => str_repeat('F(', $times).'1'.str_repeat(')', $times), 129];
        yield 'minuses' => [static fn (int $times): string => str_repeat('-', $times).'1', 65];
        yield 'NOTs' => [static fn (int $times): string => str_repeat('NOT ', $times).'a', 257];
    }

    #[DataProvider('nestings')]
    public function test_nesting_has_a_limit(\Closure $source, int $column): void
    {
        $this->assertNotNull(Parser::parse($source(63)));
        $this->assertStops(
            'syntax.too_deep',
            "Line 1, column {$column}: Brackets and functions are nested deeper than 64 levels.",
            fn () => Parser::parse($source(64)),
        );
    }

    public function test_the_depth_is_counted_back_after_each_part(): void
    {
        $wide = implode('+', array_fill(0, 100, '(-1)')).' + F('.implode(';', array_fill(0, 100, 'NOT a')).')';

        $this->assertStringStartsWith('(add (negate 1) (negate 1)', self::tree(Parser::parse($wide)));
    }

    /** @return iterable<string,array{string,string,int,int}> */
    public static function chains(): iterable
    {
        // The part before the chain, one link of it, how many fit, and the column of the next sign.
        yield 'percents' => ['1', '%', 199, 201];
        yield 'powers' => ['1', '^1', 199, 400];
        yield 'subtractions' => ['1', '-1', 199, 400];
        yield 'divisions' => ['1', '/1', 199, 400];
        yield 'members' => ['a', '.b', 199, 401];
        yield 'after a call without arguments' => ['F()', '%', 199, 203];
    }

    #[DataProvider('chains')]
    public function test_a_chain_may_be_as_tall_as_a_tree_and_no_taller(string $start, string $link, int $fits, int $column): void
    {
        $this->assertNotNull(Formula::parse($start.str_repeat($link, $fits)));
        $this->assertStops(
            'formula.too_deep',
            "Line 1, column {$column}: The formula is nested deeper than 200 levels.",
            fn () => Parser::parse($start.str_repeat($link, $fits + 1)),
        );
    }

    /** @return iterable<string,array{string}> every way to put a part on top of another */
    public static function tops(): iterable
    {
        yield 'a sum, which gathers its parts' => ['1 + %s'];
        yield 'a sum that goes on' => ['1 + 1 + %s'];
        yield 'a sum begun by the tall part' => ['%s + 1 + 1'];
        yield 'a comparison' => ['%s = 1'];
        yield 'a call' => ['F(%s)'];
        yield 'a condition' => ['IF(%s; 1)'];
        yield 'every one of' => ['AND(1; %s)'];
        yield 'a minus' => ['-(%s)'];
        yield 'a NOT' => ['NOT (%s) = 1'];
        yield 'a member' => ['(%s).b'];
    }

    #[DataProvider('tops')]
    public function test_every_part_counts_towards_the_height(string $top): void
    {
        // The comparison takes two levels where NOT goes over it.
        $under = str_starts_with($top, 'NOT') ? 197 : 198;
        $tall = static fn (int $percents): string => sprintf($top, '1'.str_repeat('%', $percents));

        $this->assertNotNull(Formula::parse($tall($under)));

        $error = null;

        try {
            Parser::parse($tall($under + 1));
        } catch (FormulaError $caught) {
            $error = $caught;
        }

        $this->assertSame('formula.too_deep', $error?->problem()->code);
    }

    public function test_a_very_long_chain_is_refused_as_soon_as_it_is_too_tall(): void
    {
        $this->assertStops(
            'formula.too_deep',
            'Line 1, column 201: The formula is nested deeper than 200 levels.',
            fn () => Formula::parse('1'.str_repeat('%', Parser::LENGTH - 1)),
        );
    }
}
