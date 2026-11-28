<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Parser;

use Atrium\Language\FormulaError;
use Atrium\Language\Ir\Call;
use Atrium\Language\Ir\Condition;
use Atrium\Language\Ir\Field;
use Atrium\Language\Ir\Literal;
use Atrium\Language\Ir\Member;
use Atrium\Language\Ir\Node;
use Atrium\Language\Ir\Operation;
use Atrium\Language\Ir\Operator;
use Atrium\Language\Ir\Shape;
use Atrium\Language\Position;
use Atrium\Language\Values\Date;
use Brick\Math\BigDecimal;
use Closure;
use WeakMap;

/**
 * Turns the text of a formula, written as in Excel, into the tree.
 *
 * From the loosest to the tightest: OR; AND; NOT; = <> < <= > >=; &; + -;
 * * /; ^; %; the minus before a value (so, as in Excel, -2^2 is 4); the dot
 * before a field of a related record.
 */
final class Parser
{
    /** The longest formula, in characters. */
    public const LENGTH = 10000;

    /** How deep brackets, calls and signs may go into each other. */
    public const DEPTH = 64;

    /** The operators written as words; only capitals make them operators, so that names may hold the same words. */
    private const WORDS = ['AND' => 'and', 'И' => 'and', 'OR' => 'or', 'ИЛИ' => 'or', 'NOT' => 'not', 'НЕ' => 'not'];

    private const BOOLEANS = ['true' => true, 'false' => false, 'истина' => true, 'ложь' => false];

    private const COMPARISONS = [
        '=' => Operator::Equal,
        '<>' => Operator::NotEqual,
        '<' => Operator::Less,
        '<=' => Operator::LessOrEqual,
        '>' => Operator::Greater,
        '>=' => Operator::GreaterOrEqual,
    ];

    private int $next = 0;

    private int $depth = 0;

    /** @var WeakMap<Node,int> the levels of each node built on others */
    private WeakMap $levels;

    /** @param  non-empty-list<Token>  $tokens */
    private function __construct(private readonly array $tokens)
    {
        $this->levels = new WeakMap;
    }

    public static function parse(string $source): Node
    {
        if (! mb_check_encoding($source, 'UTF-8')) {
            throw FormulaError::of('syntax.encoding');
        }

        if (mb_strlen($source) > self::LENGTH) {
            throw FormulaError::of('syntax.too_long', null, ['limit' => self::LENGTH]);
        }

        $parser = new self(Lexer::tokens($source));
        $root = $parser->expression();

        if ($parser->token()->kind !== TokenKind::End) {
            throw $parser->unexpected();
        }

        return $root;
    }

    private function expression(): Node
    {
        return $this->nested(fn (): Node => $this->disjunction());
    }

    private function disjunction(): Node
    {
        $node = $this->conjunction();

        while ($this->word($this->token()) === 'or') {
            $at = $this->take()->at;
            $node = $this->join(Operator::Or, $node, $this->conjunction(), $at);
        }

        return $node;
    }

    private function conjunction(): Node
    {
        $node = $this->negation();

        while ($this->word($this->token()) === 'and') {
            $at = $this->take()->at;
            $node = $this->join(Operator::And, $node, $this->negation(), $at);
        }

        return $node;
    }

    private function negation(): Node
    {
        // NOT( is the function of Excel and binds as tightly as any call.
        if ($this->word($this->token()) !== 'not' || $this->after()->is('(')) {
            return $this->comparison();
        }

        $at = $this->take()->at;

        return $this->built(new Operation(Operator::Not, [$this->nested(fn (): Node => $this->negation())], $at));
    }

    private function comparison(): Node
    {
        $node = $this->concatenation();

        if (! $this->token()->is(...array_keys(self::COMPARISONS))) {
            return $node;
        }

        $sign = $this->take();
        $node = $this->built(new Operation(self::COMPARISONS[$sign->value], [$node, $this->concatenation()], $sign->at));

        if ($this->token()->is(...array_keys(self::COMPARISONS))) {
            throw FormulaError::of('syntax.chained_comparison', $this->token()->at);
        }

        return $node;
    }

    private function concatenation(): Node
    {
        $node = $this->sum();

        while ($this->token()->is('&')) {
            $at = $this->take()->at;
            $node = $this->join(Operator::Concat, $node, $this->sum(), $at);
        }

        return $node;
    }

    private function sum(): Node
    {
        $node = $this->product();

        while ($this->token()->is('+', '-')) {
            $sign = $this->take();
            $node = $sign->value === '+'
                ? $this->join(Operator::Add, $node, $this->product(), $sign->at)
                : $this->built(new Operation(Operator::Subtract, [$node, $this->product()], $sign->at));
        }

        return $node;
    }

    private function product(): Node
    {
        $node = $this->power();

        while ($this->token()->is('*', '/')) {
            $sign = $this->take();
            $node = $sign->value === '*'
                ? $this->join(Operator::Multiply, $node, $this->power(), $sign->at)
                : $this->built(new Operation(Operator::Divide, [$node, $this->power()], $sign->at));
        }

        return $node;
    }

    private function power(): Node
    {
        $node = $this->percent();

        while ($this->token()->is('^')) {
            $at = $this->take()->at;
            $node = $this->built(new Operation(Operator::Power, [$node, $this->percent()], $at));
        }

        return $node;
    }

    private function percent(): Node
    {
        $node = $this->signed();

        while ($this->token()->is('%')) {
            $node = $this->built(new Operation(Operator::Percent, [$node], $this->take()->at));
        }

        return $node;
    }

    private function signed(): Node
    {
        if (! $this->token()->is('-')) {
            return $this->members($this->primary());
        }

        $at = $this->take()->at;

        return $this->built(new Operation(Operator::Negate, [$this->nested(fn (): Node => $this->signed())], $at));
    }

    private function members(Node $node): Node
    {
        while ($this->token()->is('.')) {
            $this->take();
            $name = $this->token();

            $node = $this->built(match ($name->kind) {
                TokenKind::Name => new Member($node, $this->take()->value, $name->at),
                TokenKind::Word => new Member($node, $this->words(), $name->at),
                default => throw $this->unexpected(),
            });
        }

        return $node;
    }

    private function primary(): Node
    {
        $token = $this->token();

        if ($token->is('(')) {
            $this->take();
            $node = $this->expression();
            $this->expect(')');

            return $node;
        }

        return match ($token->kind) {
            TokenKind::Number => new Literal(BigDecimal::of($this->take()->value), $token->at),
            TokenKind::Text => new Literal($this->take()->value, $token->at),
            TokenKind::Date => new Literal($this->date($this->take()), $token->at),
            TokenKind::Name => new Field($this->take()->value, $token->at),
            TokenKind::Word => $this->named(),
            default => throw $this->unexpected(),
        };
    }

    private function date(Token $token): Date
    {
        $iso = str_contains($token->value, '#')
            ? trim($token->value, '#')
            : vsprintf('%3$04d-%2$02d-%1$02d', explode('.', $token->value));

        return Date::parse($iso) ?? throw FormulaError::of('syntax.bad_date', $token->at, ['date' => $token->value]);
    }

    /** A word in the place of a value: a call, TRUE or FALSE, or the name of a field. */
    private function named(): Node
    {
        $first = $this->token();

        // An operator may begin a value only as the name of its function.
        if ($this->word($first) !== null && ! $this->after()->is('(')) {
            throw $this->unexpected();
        }

        $name = $this->words();

        if ($this->token()->is('(')) {
            return $this->call($name, $first->at);
        }

        $boolean = self::BOOLEANS[mb_strtolower($name)] ?? null;

        return $boolean === null ? new Field($name, $first->at) : new Literal($boolean, $first->at);
    }

    /** Words in a row are one name, until a sign or an operator: `Дата отгрузки`, `Stage 2`. */
    private function words(): string
    {
        $words = [$this->take()->value];

        while (true) {
            $token = $this->token();
            $word = $token->kind === TokenKind::Word && $this->word($token) === null;
            $number = $token->kind === TokenKind::Number && ctype_digit($token->value);

            if (! $word && ! $number) {
                return implode(' ', $words);
            }

            $words[] = $this->take()->value;
        }
    }

    private function call(string $name, Position $at): Node
    {
        $this->expect('(');
        $arguments = [];

        if (! $this->token()->is(')')) {
            $arguments[] = $this->expression();

            while ($this->token()->is(';', ',')) {
                $this->take();
                $arguments[] = $this->expression();
            }
        }

        $this->expect(')');

        return $this->built(match (mb_strtoupper($name)) {
            'IF', 'ЕСЛИ' => $this->condition($name, $arguments, $at),
            'AND', 'И' => $this->special(Operator::And, $name, $arguments, 1, null, $at),
            'OR', 'ИЛИ' => $this->special(Operator::Or, $name, $arguments, 1, null, $at),
            'NOT', 'НЕ' => $this->special(Operator::Not, $name, $arguments, 1, 1, $at),
            default => new Call($name, $arguments, $at),
        });
    }

    /** @param  list<Node>  $arguments */
    private function condition(string $name, array $arguments, Position $at): Condition
    {
        $this->arity($name, count($arguments), 2, 3, $at);

        return new Condition($arguments[0], $arguments[1], $arguments[2] ?? null, $at);
    }

    /** @param  list<Node>  $arguments */
    private function special(Operator $operator, string $name, array $arguments, int $min, ?int $max, Position $at): Operation
    {
        $this->arity($name, count($arguments), $min, $max, $at);

        return new Operation($operator, $arguments, $at);
    }

    private function arity(string $name, int $given, int $min, ?int $max, Position $at): void
    {
        if ($given < $min) {
            throw FormulaError::of('arguments.too_few', $at, ['function' => $name, 'min' => $min, 'given' => $given]);
        }

        if ($max !== null && $given > $max) {
            throw FormulaError::of('arguments.too_many', $at, ['function' => $name, 'max' => $max, 'given' => $given]);
        }
    }

    /** One more operand of an operator that takes any number of them. */
    private function join(Operator $operator, Node $left, Node $right, Position $at): Node
    {
        if (! $left instanceof Operation || $left->operator !== $operator) {
            return $this->built(new Operation($operator, [$left, $right], $at));
        }

        $joined = new Operation($operator, [...$left->operands, $right], $left->at);

        // The other operands were counted when they were joined.
        return $this->tall($joined, max($this->levels($left), $this->levels($right) + 1));
    }

    private function built(Node $node): Node
    {
        return $this->tall($node, 1 + max([0, ...array_map($this->levels(...), $node->children())]));
    }

    /**
     * Stops before the tree is taller than any may be: even throwing a very
     * tall one away can overflow a small stack.
     */
    private function tall(Node $node, int $levels): Node
    {
        if ($levels > Shape::DEPTH) {
            throw FormulaError::of('formula.too_deep', $node->at, ['limit' => Shape::DEPTH]);
        }

        $this->levels[$node] = $levels;

        return $node;
    }

    private function levels(Node $node): int
    {
        return $this->levels[$node] ?? 1;
    }

    /** @param  Closure(): Node  $parse */
    private function nested(Closure $parse): Node
    {
        if (++$this->depth > self::DEPTH) {
            throw FormulaError::of('syntax.too_deep', $this->token()->at, ['limit' => self::DEPTH]);
        }

        $node = $parse();
        $this->depth--;

        return $node;
    }

    /** The operator a word stands for, or null. */
    private function word(Token $token): ?string
    {
        return $token->kind === TokenKind::Word ? (self::WORDS[$token->value] ?? null) : null;
    }

    private function token(): Token
    {
        return $this->tokens[$this->next];
    }

    /** The token after the current one, which is never the end. */
    private function after(): Token
    {
        return $this->tokens[$this->next + 1];
    }

    /** @phpstan-impure it moves on to the next token */
    private function take(): Token
    {
        return $this->tokens[$this->next++];
    }

    private function expect(string $symbol): void
    {
        if (! $this->token()->is($symbol)) {
            throw $this->unexpected();
        }

        $this->take();
    }

    private function unexpected(): FormulaError
    {
        $token = $this->token();

        if ($token->kind === TokenKind::End) {
            return FormulaError::of('syntax.unexpected_end', $token->at);
        }

        $capitals = mb_strtoupper($token->value);

        if ($token->kind === TokenKind::Word && $capitals !== $token->value && isset(self::WORDS[$capitals])) {
            return FormulaError::of('syntax.operator_case', $token->at, ['word' => $token->value, 'operator' => $capitals]);
        }

        return FormulaError::of('syntax.unexpected', $token->at, ['token' => $token->value]);
    }
}
