<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Unit;

use Atrium\Language\Formula;
use Atrium\Language\FormulaError;
use Atrium\Language\Ir\Codec;
use Atrium\Language\Ir\Field;
use Atrium\Language\Ir\Shape;
use Atrium\Language\Tests\TestCase;
use Illuminate\Support\Facades\Lang;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;

final class CodecTest extends TestCase
{
    public function test_a_formula_is_stored_as_versioned_json_with_its_letters_kept(): void
    {
        $json = Formula::parse('Сумма * 2')->toJson();

        $this->assertSame('{"version":1,"root":{"node":"operation","operator":"multiply","operands":[{"node":"field","name":"Сумма","at":[1,1]},{"node":"literal","type":"number","value":"2","at":[1,9]}],"at":[1,7]}}', $json);
        $this->assertSame($json, Formula::fromJson($json)->toJson());
        $this->assertSame(1, Codec::VERSION);
    }

    public function test_a_parsed_formula_of_every_kind_is_read_back_the_same(): void
    {
        $formula = Formula::parse('IF(NOT a.b > -1,5%; F(1.1.2026; "x" & TRUE; [c d]) ^ 2)');

        $this->assertSame($formula->toJson(), Formula::fromJson($formula->toJson())->toJson());
    }

    public function test_a_name_that_is_not_utf8_cannot_be_stored(): void
    {
        $this->expectException(JsonException::class);

        Formula::fromNode(new Field("\xff"))->toJson();
    }

    /** @return iterable<string,array{mixed,string}> */
    public static function damaged(): iterable
    {
        $field = ['node' => 'field', 'name' => 'a'];
        $root = static fn (mixed $node): array => ['version' => 1, 'root' => $node];
        $literal = static fn (array $data): array => ['version' => 1, 'root' => ['node' => 'literal', ...$data]];

        yield 'not an array' => ['text', 'version'];
        yield 'nothing' => [null, 'version'];
        yield 'no version' => [['root' => $field], 'version'];
        yield 'a null version' => [['version' => null, 'root' => $field], 'version'];
        yield 'no root' => [['version' => 1], 'formula'];
        yield 'a key too many' => [['version' => 1, 'root' => $field, 'extra' => 1], 'formula'];
        yield 'a root that is not a node' => [$root('text'), 'root'];
        yield 'no kind' => [$root(['name' => 'a']), 'root'];
        yield 'a kind that is not a text' => [$root(['node' => 1, 'name' => 'a']), 'root'];
        yield 'an unknown kind' => [$root(['node' => 'loop']), 'root'];
        yield 'a missing key' => [$root(['node' => 'field']), 'root'];
        yield 'an extra key' => [$root([...$field, 'extra' => 1]), 'root'];
        yield 'a name that is not a text' => [$root(['node' => 'field', 'name' => 5]), 'root.name'];
        yield 'a variable name that is not a text' => [$root(['node' => 'variable', 'name' => []]), 'root.name'];
        yield 'a member name that is not a text' => [$root(['node' => 'member', 'of' => $field, 'name' => null]), 'root.name'];
        yield 'a damaged child' => [$root(['node' => 'member', 'of' => 'a', 'name' => 'b']), 'root.of'];
        yield 'an unknown operator' => [$root(['node' => 'operation', 'operator' => 'xor', 'operands' => [$field, $field]]), 'root.operator'];
        yield 'an operator that is not a text' => [$root(['node' => 'operation', 'operator' => 1, 'operands' => [$field]]), 'root.operator'];
        yield 'too few operands' => [$root(['node' => 'operation', 'operator' => 'divide', 'operands' => [$field]]), 'root.operands'];
        yield 'operands that are not a list' => [$root(['node' => 'operation', 'operator' => 'add', 'operands' => ['a' => $field]]), 'root.operands'];
        yield 'operands that are not an array' => [$root(['node' => 'operation', 'operator' => 'add', 'operands' => 'a']), 'root.operands'];
        yield 'a damaged operand' => [$root(['node' => 'operation', 'operator' => 'add', 'operands' => [$field, 'b']]), 'root.operands.1'];
        yield 'a function name that is not a text' => [$root(['node' => 'call', 'function' => 1, 'arguments' => []]), 'root.function'];
        yield 'a damaged argument' => [$root(['node' => 'call', 'function' => 'F', 'arguments' => [1]]), 'root.arguments.0'];
        yield 'a condition without then' => [$root(['node' => 'condition', 'when' => $field]), 'root'];
        yield 'a damaged otherwise' => [$root(['node' => 'condition', 'when' => $field, 'then' => $field, 'otherwise' => null]), 'root.otherwise'];
        yield 'a damaged then' => [$root(['node' => 'condition', 'when' => $field, 'then' => 1]), 'root.then'];
        yield 'a loop variable that is not a text' => [$root(['node' => 'each', 'source' => $field, 'variable' => 1, 'body' => $field]), 'root.variable'];
        yield 'a damaged filter' => [$root(['node' => 'each', 'source' => $field, 'variable' => 'v', 'body' => $field, 'where' => 1]), 'root.where'];
        yield 'a position that is not a pair' => [$root([...$field, 'at' => 1]), 'root.at'];
        yield 'a null position' => [$root([...$field, 'at' => null]), 'root.at'];
        yield 'a position of one' => [$root([...$field, 'at' => [1]]), 'root.at'];
        yield 'a position of three' => [$root([...$field, 'at' => [1, 1, 1]]), 'root.at'];
        yield 'a position that is not a list' => [$root([...$field, 'at' => ['line' => 1, 'column' => 1]]), 'root.at'];
        yield 'a line of zero' => [$root([...$field, 'at' => [0, 1]]), 'root.at'];
        yield 'a column of zero' => [$root([...$field, 'at' => [1, 0]]), 'root.at'];
        yield 'a line that is a text' => [$root([...$field, 'at' => ['1', 1]]), 'root.at'];
        yield 'a column that is a text' => [$root([...$field, 'at' => [1, '1']]), 'root.at'];
        yield 'a literal without a type' => [$literal([]), 'root'];
        yield 'a literal of an unknown type' => [$literal(['type' => 'list', 'value' => []]), 'root.value'];
        yield 'an empty literal with a value' => [$literal(['type' => 'empty', 'value' => 1]), 'root.value'];
        yield 'an empty literal with a null value' => [$literal(['type' => 'empty', 'value' => null]), 'root.value'];
        yield 'a text without a value' => [$literal(['type' => 'text']), 'root.value'];
        yield 'a text that is a number' => [$literal(['type' => 'text', 'value' => 1]), 'root.value'];
        yield 'a yes that is a text' => [$literal(['type' => 'boolean', 'value' => 'true']), 'root.value'];
        yield 'a number that is not written as a text' => [$literal(['type' => 'number', 'value' => 1]), 'root.value'];
        yield 'a number that is not one' => [$literal(['type' => 'number', 'value' => '1e5']), 'root.value'];
        yield 'a date that is not a text' => [$literal(['type' => 'date', 'value' => 20261128]), 'root.value'];
        yield 'a date that is not one' => [$literal(['type' => 'date', 'value' => '2026-02-30']), 'root.value'];
    }

    #[DataProvider('damaged')]
    public function test_it_refuses_damaged_data_and_says_where(mixed $data, string $path): void
    {
        $this->assertStops(
            'ir.invalid',
            "The stored formula is damaged at “{$path}”.",
            fn () => Codec::decode($data),
        );
    }

    /** @return iterable<string,array{mixed}> */
    public static function versions(): iterable
    {
        yield 'a later one' => [2];
        yield 'zero' => [0];
        yield 'a text' => ['1'];
    }

    #[DataProvider('versions')]
    public function test_it_reads_only_its_own_version(mixed $version): void
    {
        $this->assertStops(
            'ir.version',
            'The formula is stored in a version of the format that cannot be read here; the version read is 1.',
            fn () => Codec::decode(['version' => $version, 'anything' => true]),
        );
    }

    public function test_text_that_is_not_json_is_damaged_data(): void
    {
        $this->assertStops('ir.invalid', 'The stored formula is damaged at “version”.', fn () => Formula::fromJson('{'));
    }

    /** @return array<string,mixed> a tree this deep, kinds taking turns */
    private static function nested(int $depth): array
    {
        $node = ['node' => 'field', 'name' => 'a'];

        for ($level = 1; $level < $depth; $level++) {
            $node = $level % 2 === 0
                ? ['node' => 'member', 'of' => $node, 'name' => 'b']
                : ['node' => 'operation', 'operator' => 'add', 'operands' => [$node]];
        }

        return ['version' => 1, 'root' => $node];
    }

    public function test_it_stops_reading_past_the_greatest_depth(): void
    {
        $this->assertNotNull(Formula::fromJson(json_encode(self::nested(200))));
        $this->assertStops(
            'formula.too_deep',
            'The formula is nested deeper than 200 levels.',
            fn () => Codec::decode(self::nested(201)),
        );
    }

    /** @return iterable<string,array{\Closure(array<string,mixed>): array<string,mixed>}> */
    public static function wrappers(): iterable
    {
        yield 'member' => [static fn (array $node): array => ['node' => 'member', 'of' => $node, 'name' => 'b']];
        yield 'operand' => [static fn (array $node): array => ['node' => 'operation', 'operator' => 'add', 'operands' => [$node]]];
        yield 'argument' => [static fn (array $node): array => ['node' => 'call', 'function' => 'F', 'arguments' => [$node]]];
        yield 'when' => [static fn (array $node): array => ['node' => 'condition', 'when' => $node, 'then' => ['node' => 'field', 'name' => 'a']]];
        yield 'filter' => [static fn (array $node): array => ['node' => 'each', 'source' => ['node' => 'field', 'name' => 'a'], 'variable' => 'v', 'body' => ['node' => 'field', 'name' => 'a'], 'where' => $node]];
    }

    /** @param  \Closure(array<string,mixed>): array<string,mixed>  $wrap */
    #[DataProvider('wrappers')]
    public function test_every_way_down_counts_towards_the_depth(\Closure $wrap): void
    {
        $node = ['node' => 'field', 'name' => 'a'];

        for ($level = 1; $level < 200; $level++) {
            $node = $wrap($node);
        }

        $this->assertNotNull(Codec::decode(['version' => 1, 'root' => $node]));
        $this->assertStops(
            'formula.too_deep',
            'The formula is nested deeper than 200 levels.',
            fn () => Codec::decode(['version' => 1, 'root' => $wrap($node)]),
        );
    }

    public function test_stored_text_has_a_greatest_length_in_bytes(): void
    {
        $this->assertSame(1000000, Formula::JSON_BYTES);

        $json = Formula::parse('1')->toJson();
        $longest = $json.str_repeat(' ', Formula::JSON_BYTES - strlen($json));

        $this->assertSame($json, Formula::fromJson($longest)->toJson());
        $this->assertStops(
            'formula.too_long',
            'The stored formula is longer than 1000000 bytes.',
            fn () => Formula::fromJson($longest.' '),
        );
    }

    public function test_spaces_may_stand_around_the_keys(): void
    {
        $json = "{ \"version\" : 1 , \"root\"\n:{\"node\"\t:\"field\",\"name\" :\"a:b\"} }";

        $this->assertSame('a:b', Formula::fromJson($json)->root->name);
    }

    /** @return iterable<string,array{string}> */
    public static function strange(): iterable
    {
        yield 'a key the format does not have' => ['{"version":1,"root":{"node":"field","name":"a","x":1}}'];
        yield 'a key that is a number' => ['{"version":1,"root":{"node":"field","name":"a","1048576":1}}'];
        yield 'a key of the format written with a code' => ['{"version":1,"root":{"node":"field","na\\u006de":"a"}}'];
        yield 'a key that only ends like one of the format' => ['{"version":1,"root":{"node":"field","\\"name":"a"}}'];
        yield 'a text without an end' => ['{"version":1,"root":"'];
    }

    #[DataProvider('strange')]
    public function test_stored_text_with_other_keys_is_not_decoded(string $json): void
    {
        $this->assertStops('ir.invalid', 'The stored formula is damaged at “formula”.', fn () => Formula::fromJson($json));
    }

    /** @return iterable<string,array{string}> */
    public static function runs(): iterable
    {
        yield 'a text without an end' => ['"'.str_repeat('\\"', 499000)];
        yield 'a long key the format does not have' => ['"'.str_repeat('\\"', 499000).'":1'];
    }

    #[DataProvider('runs')]
    public function test_a_long_row_of_quotes_is_looked_over_once(string $json): void
    {
        $started = microtime(true);

        $this->assertStops('ir.invalid', 'The stored formula is damaged at “formula”.', fn () => Formula::fromJson($json));
        // Starting again at every quote takes minutes.
        $this->assertLessThan(5.0, microtime(true) - $started);
    }

    public function test_stored_text_that_cannot_be_looked_over_is_damaged_data(): void
    {
        $json = Formula::parse('1')->toJson();
        $jit = (string) ini_get('pcre.jit');
        $limit = (string) ini_get('pcre.backtrack_limit');
        $refused = null;
        // Putting the problem into words uses regular expressions too, on PHP 8.3.
        Lang::shouldReceive('get')->andReturn('damaged');

        try {
            ini_set('pcre.jit', '0');
            ini_set('pcre.backtrack_limit', '1');
            Codec::read($json);
        } catch (FormulaError $error) {
            $refused = $error;
        } finally {
            ini_set('pcre.jit', $jit);
            ini_set('pcre.backtrack_limit', $limit);
        }

        $this->assertSame(['ir.invalid', ['path' => 'formula']], [$refused?->problem()->code, $refused?->problem()->params]);
    }

    /** @return iterable<string,array{string}> */
    public static function crowds(): iterable
    {
        // The list around them is the first to open.
        yield 'arrays' => ['[]'];
        yield 'objects' => ['{}'];
    }

    #[DataProvider('crowds')]
    public function test_only_so_many_arrays_and_objects_are_decoded(string $empty): void
    {
        $json = static fn (int $count): string => '['.implode(',', array_fill(0, $count, $empty)).']';

        $this->assertSame(20000, Codec::OPENINGS);
        $this->assertCount(19999, Codec::read($json(19999)));
        $this->assertStops(
            'formula.too_big',
            'The formula has more than 5000 parts.',
            fn () => Formula::fromJson($json(20000)),
        );
    }

    public function test_arrays_and_objects_are_counted_together(): void
    {
        $json = '['.str_repeat('[],', 10000).str_repeat('{},', 9999).'{}]';

        $this->assertStops('formula.too_big', 'The formula has more than 5000 parts.', fn () => Codec::read($json));
    }

    public function test_the_largest_tree_is_read(): void
    {
        $calls = array_fill(0, Shape::NODES - 1, ['node' => 'call', 'function' => 'F', 'arguments' => [], 'at' => [1, 1]]);
        $tree = ['node' => 'call', 'function' => 'F', 'arguments' => $calls, 'at' => [1, 1]];

        $this->assertCount(Shape::NODES - 1, Formula::fromJson(json_encode(['version' => 1, 'root' => $tree]))->root->children());
    }

    public function test_brackets_inside_texts_are_not_counted(): void
    {
        $text = str_repeat('[{"x":', 30000).'\\';
        $json = json_encode(['version' => 1, 'root' => ['node' => 'literal', 'type' => 'text', 'value' => $text]]);

        $this->assertSame($text, Formula::fromJson($json)->root->value);
    }
}
