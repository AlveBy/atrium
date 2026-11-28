<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests;

use Atrium\Language\Context;
use Atrium\Language\Formula;
use Atrium\Language\FormulaError;
use Atrium\Language\Ir\Call;
use Atrium\Language\Ir\Condition;
use Atrium\Language\Ir\Each;
use Atrium\Language\Ir\Field;
use Atrium\Language\Ir\Literal;
use Atrium\Language\Ir\Member;
use Atrium\Language\Ir\Node;
use Atrium\Language\Ir\Operation;
use Atrium\Language\Ir\Variable;
use Atrium\Language\LanguageServiceProvider;
use Atrium\Language\Schema;
use Atrium\Language\Values\Date;
use Atrium\Language\Values\Record;
use Brick\Math\BigDecimal;
use Closure;
use DateTimeImmutable;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LanguageServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The deepest formulas the limits allow go further than Xdebug's
        // guard against endless recursion, which is on when it is developing.
        ini_set('xdebug.max_nesting_level', '4096');
    }

    /** An order with its lines: the fields most tests are about. */
    protected function schema(): Schema
    {
        $line = Schema::make()
            ->number('price', 'Price', 'Цена')
            ->number('qty', 'Qty', 'Количество')
            ->text('title', 'Title');

        return Schema::make()
            ->number('amount', 'Amount', 'Сумма')
            ->number('discount', 'Скидка')
            ->text('status', 'Статус')
            ->boolean('paid', 'Оплачен')
            ->date('due', 'Дата сдачи')
            ->records('lines', $line, 'Строки')
            ->records('rows', $line);
    }

    /** @param  array<string,mixed>  $values */
    protected function context(array $values = [], ?Schema $schema = null): Context
    {
        return Context::make($schema ?? $this->schema(), $values)
            ->withToday(new DateTimeImmutable('2026-11-28'));
    }

    /** @param  array<string,mixed>  $values */
    protected function value(string $source, array $values = [], ?Context $context = null): mixed
    {
        return self::plain(Formula::parse($source)->evaluate($context ?? $this->context($values))->value);
    }

    /** A value of the language as plain data, so that its type is asserted with it. */
    protected static function plain(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BigDecimal => ['number', (string) $value],
            $value instanceof Date => ['date', (string) $value],
            $value instanceof Record => ['record', array_map(self::plain(...), $value->values)],
            is_array($value) => array_map(self::plain(...), $value),
            default => $value,
        };
    }

    /** @return array{string,string} */
    protected static function num(string|int $number): array
    {
        return ['number', (string) $number];
    }

    /** @return array{string,string} */
    protected static function day(string $day): array
    {
        return ['date', $day];
    }

    /** Asserts that the closure stops with exactly this problem, said like this in English. */
    protected function assertStops(string $code, string $message, Closure $run): FormulaError
    {
        try {
            $run();
        } catch (FormulaError $error) {
            $this->assertSame(
                [[$code, $message]],
                array_map(static fn ($problem): array => [$problem->code, $problem->message('en')], $error->problems),
            );

            return $error;
        }

        $this->fail("Nothing stopped: expected {$code}.");
    }

    /**
     * Asserts what the check finds, in order.
     *
     * @param  list<array{string,string}>  $problems  code and English message
     */
    protected function assertProblems(array $problems, string|Formula $formula, ?Context $context = null): void
    {
        $formula = is_string($formula) ? Formula::parse($formula) : $formula;
        $check = $formula->check($context ?? $this->context());

        $this->assertSame($problems, array_map(
            static fn ($problem): array => [$problem->code, $problem->message('en')],
            $check->problems,
        ));
        $this->assertSame($problems === [], $check->passes());
    }

    /** The tree of a formula in one line, without positions. */
    protected static function tree(Node $node): string
    {
        $list = static fn (string $head, array $nodes): string => '('.implode(' ', [$head, ...array_map(self::tree(...), $nodes)]).')';

        return match (true) {
            $node instanceof Literal => match (true) {
                $node->value === null => 'empty',
                is_bool($node->value) => $node->value ? 'yes' : 'no',
                is_string($node->value) => "'{$node->value}'",
                $node->value instanceof Date => "date:{$node->value}",
                default => (string) $node->value,
            },
            $node instanceof Field => "[{$node->name}]",
            $node instanceof Variable => "\${$node->name}",
            $node instanceof Member => '(. '.self::tree($node->of)." {$node->name})",
            $node instanceof Operation => $list($node->operator->value, $node->operands),
            $node instanceof Call => $list("call:{$node->function}", $node->arguments),
            $node instanceof Condition => $list('if', $node->children()),
            $node instanceof Each => $list("each:{$node->variable}", $node->children()),
        };
    }
}
