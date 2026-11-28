<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\FormulaError;
use Atrium\Language\Position;
use Atrium\Language\Values\Date;
use Atrium\Language\Values\Numbers;

/**
 * Trees as stored data: `{"version": 1, "root": {"node": ...}}`. Reading is
 * strict — an unknown node, a missing or an extra key is refused — so that a
 * stored tree means one thing in every release that reads this version.
 */
final class Codec
{
    public const VERSION = 1;

    /** The most arrays and objects a stored tree may open: the largest tree opens fewer. */
    public const OPENINGS = 20000;

    /**
     * A key of the format with its colon, or a text that is not a key. Any
     * other quote is skipped with all that follows it and stays, so that the
     * text is looked over once whatever it holds.
     */
    private const STRINGS = '/"(?:version|root|node|at|type|value|name|of|operator|operands|function|arguments|when|then|otherwise|source|variable|body|where)"\s*+:'
        .'|"[^"\\\\]*+(?:\\\\.[^"\\\\]*+)*+(?:"(?!\s*+:)|"?+(*SKIP)(*FAIL))/s';

    /** The keys of each kind of node besides `node` and `at`: those it must have, those it may. */
    private const KEYS = [
        'literal' => [['type'], ['value']],
        'field' => [['name'], []],
        'variable' => [['name'], []],
        'member' => [['of', 'name'], []],
        'operation' => [['operator', 'operands'], []],
        'call' => [['function', 'arguments'], []],
        'condition' => [['when', 'then'], ['otherwise']],
        'each' => [['source', 'variable', 'body'], ['where']],
    ];

    /** @return array{version:int,root:array<string,mixed>} */
    public static function encode(Node $root): array
    {
        return ['version' => self::VERSION, 'root' => $root->toArray()];
    }

    /**
     * Stored text as plain data, or null when it is not JSON. Decoding is where
     * a hostile text could take memory or time — thousands of arrays inside one
     * another, keys chosen to collide — so it is first looked over: every key
     * must be one of the format's, and only so many arrays and objects open.
     *
     * @throws FormulaError
     */
    public static function read(string $json): mixed
    {
        // What is left holds a quote if there is a key the format does not have.
        $rest = preg_replace(self::STRINGS, '', $json);

        if ($rest === null || str_contains($rest, '"')) {
            throw self::invalid('formula');
        }

        if (substr_count($rest, '[') + substr_count($rest, '{') > self::OPENINGS) {
            throw FormulaError::of('formula.too_big', null, ['limit' => Shape::NODES]);
        }

        return json_decode($json, true);
    }

    public static function decode(mixed $data): Node
    {
        if (! is_array($data) || ! isset($data['version'])) {
            throw self::invalid('version');
        }

        if ($data['version'] !== self::VERSION) {
            throw FormulaError::of('ir.version', null, ['supported' => self::VERSION]);
        }

        self::keys($data, 'formula', ['version', 'root']);

        return self::node($data['root'], 'root', 1);
    }

    private static function node(mixed $data, string $path, int $depth): Node
    {
        if ($depth > Shape::DEPTH) {
            throw FormulaError::of('formula.too_deep', null, ['limit' => Shape::DEPTH]);
        }

        $kind = is_array($data) ? ($data['node'] ?? null) : null;

        if (! is_string($kind) || ! isset(self::KEYS[$kind])) {
            throw self::invalid($path);
        }

        [$required, $optional] = self::KEYS[$kind];
        self::keys($data, $path, ['node', ...$required], ['at', ...$optional]);

        $at = array_key_exists('at', $data) ? self::position($data['at'], "{$path}.at") : null;

        $child = static fn (string $key): Node => self::node($data[$key], "{$path}.{$key}", $depth + 1);
        $maybe = static fn (string $key): ?Node => array_key_exists($key, $data) ? $child($key) : null;
        $name = static fn (string $key): string => is_string($data[$key]) ? $data[$key] : throw self::invalid("{$path}.{$key}");

        return match ($kind) {
            'literal' => self::literal($data, $path, $at),
            'field' => new Field($name('name'), $at),
            'variable' => new Variable($name('name'), $at),
            'member' => new Member($child('of'), $name('name'), $at),
            'operation' => self::operation($name('operator'), self::nodes($data['operands'], "{$path}.operands", $depth), $path, $at),
            'call' => new Call($name('function'), self::nodes($data['arguments'], "{$path}.arguments", $depth), $at),
            'condition' => new Condition($child('when'), $child('then'), $maybe('otherwise'), $at),
            default => new Each($child('source'), $name('variable'), $child('body'), $maybe('where'), $at),
        };
    }

    /** @param  list<Node>  $operands */
    private static function operation(string $name, array $operands, string $path, ?Position $at): Operation
    {
        $operator = Operator::tryFrom($name) ?? throw self::invalid("{$path}.operator");

        if (! $operator->takes(count($operands))) {
            throw self::invalid("{$path}.operands");
        }

        return new Operation($operator, $operands, $at);
    }

    /** @param  array<array-key,mixed>  $data */
    private static function literal(array $data, string $path, ?Position $at): Literal
    {
        $type = $data['type'];

        if ($type === 'empty' && ! array_key_exists('value', $data)) {
            return new Literal(null, $at);
        }

        $value = $data['value'] ?? null;

        $read = match (true) {
            $type === 'boolean' && is_bool($value) => $value,
            $type === 'text' && is_string($value) => $value,
            $type === 'number' && is_string($value) => Numbers::parse($value),
            $type === 'date' && is_string($value) => Date::parse($value),
            default => null,
        };

        return new Literal($read ?? throw self::invalid("{$path}.value"), $at);
    }

    /** @return list<Node> */
    private static function nodes(mixed $data, string $path, int $depth): array
    {
        if (! is_array($data) || ! array_is_list($data)) {
            throw self::invalid($path);
        }

        $nodes = [];

        foreach ($data as $index => $node) {
            $nodes[] = self::node($node, "{$path}.{$index}", $depth + 1);
        }

        return $nodes;
    }

    private static function position(mixed $data, string $path): Position
    {
        if (is_array($data) && array_is_list($data) && count($data) === 2) {
            [$line, $column] = $data;

            if (is_int($line) && is_int($column) && $line >= 1 && $column >= 1) {
                return new Position($line, $column);
            }
        }

        throw self::invalid($path);
    }

    /**
     * @param  array<array-key,mixed>  $data
     * @param  list<string>  $required
     * @param  list<string>  $optional
     */
    private static function keys(array $data, string $path, array $required, array $optional = []): void
    {
        $given = array_keys($data);

        if (array_diff($required, $given) !== [] || array_diff($given, $required, $optional) !== []) {
            throw self::invalid($path);
        }
    }

    private static function invalid(string $path): FormulaError
    {
        return FormulaError::of('ir.invalid', null, ['path' => $path]);
    }
}
