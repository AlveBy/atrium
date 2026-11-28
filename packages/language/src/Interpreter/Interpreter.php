<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Interpreter;

use Atrium\Language\Context;
use Atrium\Language\FormulaError;
use Atrium\Language\Functions\FormulaFunction;
use Atrium\Language\Functions\Invocation;
use Atrium\Language\Ir\Call;
use Atrium\Language\Ir\Condition;
use Atrium\Language\Ir\Each;
use Atrium\Language\Ir\Field;
use Atrium\Language\Ir\Literal;
use Atrium\Language\Ir\Member;
use Atrium\Language\Ir\Node;
use Atrium\Language\Ir\Operation;
use Atrium\Language\Ir\Operator;
use Atrium\Language\Ir\Variable;
use Atrium\Language\Ir\Visitor;
use Atrium\Language\Result;
use Atrium\Language\Schema;
use Atrium\Language\Step;
use Atrium\Language\Types\Type;
use Atrium\Language\Values\Date;
use Atrium\Language\Values\Numbers;
use Atrium\Language\Values\Record;
use Atrium\Language\Values\Scalars;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Works out a tree that has passed the check, counting every step against the
 * run's limits. What the check cannot know — empty values, types only known
 * while running — is looked at here.
 *
 * @implements Visitor<mixed>
 */
final class Interpreter implements Visitor
{
    /** The largest power, either way. */
    public const POWER = 1000;

    /** More days than lie between the first date and the last. */
    private const DAYS = 4000000;

    /** @var array<string,mixed> the items of the loops around the node */
    private array $variables = [];

    /** @var array<string,mixed> the fields read so far, by key */
    private array $fields = [];

    /** @var list<Step> the steps of the part being worked out */
    private array $trace = [];

    private readonly Date $today;

    /** @param  array<string,FormulaFunction>  $functions  as the check found them */
    public function __construct(
        private readonly Context $context,
        private readonly array $functions,
        private readonly Meter $meter,
        private readonly bool $explain,
    ) {
        $this->today = $context->today();
    }

    public function run(Node $root): Result
    {
        $value = $this->value($root);

        return new Result($value, $this->fields, $this->trace[0] ?? null);
    }

    public function literal(Literal $node): mixed
    {
        return $node->value;
    }

    public function field(Field $node): mixed
    {
        $field = $this->context->schema()->find($node->name);

        if (! array_key_exists($field->key, $this->fields)) {
            $conversion = new Conversion($this->meter, 'value.bad_field', ['field' => $node->name, 'expected' => $field->type], $node->at);
            $this->fields[$field->key] = $conversion->convert($this->context->value($field->key), $field->type);
        }

        return $this->fields[$field->key];
    }

    public function variable(Variable $node): mixed
    {
        return $this->variables[$node->name];
    }

    public function member(Member $node): mixed
    {
        return $this->memberOf($this->value($node->of), $node);
    }

    public function operation(Operation $node): mixed
    {
        $result = match ($node->operator) {
            Operator::And => $this->every($node),
            Operator::Or => $this->some($node),
            Operator::Not => ! $this->boolean($node->operands[0]),
            Operator::Negate => $this->number($node->operands[0])->negated(),
            Operator::Percent => $this->number($node->operands[0])->withPointMovedLeft(2),
            Operator::Add => $this->add($node),
            Operator::Subtract => $this->subtract($node),
            Operator::Multiply => $this->multiply($node),
            Operator::Divide => $this->divide($node),
            Operator::Power => $this->power($node),
            Operator::Concat => $this->concat($node),
            Operator::Equal => $this->equal($node),
            Operator::NotEqual => ! $this->equal($node),
            Operator::Less => $this->order($node) < 0,
            Operator::LessOrEqual => $this->order($node) <= 0,
            Operator::Greater => $this->order($node) > 0,
            Operator::GreaterOrEqual => $this->order($node) >= 0,
        };

        if ($result instanceof BigDecimal) {
            $result = Numbers::bounded($result, $node->at);
        }

        $this->meter->charge($result, $node->at);

        return $result;
    }

    public function call(Call $node): mixed
    {
        $function = $this->functions[$node->function];
        $arguments = [];

        foreach ($node->arguments as $position => $argument) {
            $parameter = $function->parameter($position);
            $value = $this->value($argument);

            if ($value !== null || ! $parameter->orEmpty) {
                $this->fit($value, $argument, $parameter->type);
            }

            $arguments[] = $value;
        }

        $result = $function->call(new Invocation($node->function, $arguments, $this->today, $this->meter, $node->at));
        $type = $function->returnType(array_map(Type::of(...), $arguments));
        $conversion = new Conversion($this->meter, 'value.bad_return', ['function' => $node->function, 'expected' => $type], $node->at);

        return $conversion->convert($result, $type);
    }

    public function condition(Condition $node): mixed
    {
        if ($this->boolean($node->when)) {
            return $this->value($node->then);
        }

        return $node->otherwise === null ? null : $this->value($node->otherwise);
    }

    /** @return list<mixed> */
    public function each(Each $node): array
    {
        $source = $this->value($node->source) ?? [];
        $this->fit($source, $node->source, Type::listOf(Type::any()));

        $outer = $this->variables;
        $results = [];

        foreach ($source as $item) {
            $this->meter->step($node->at);
            $this->variables[$node->variable] = $item;

            if ($node->where === null || $this->boolean($node->where)) {
                $result = $this->value($node->body);
                $this->meter->keep($result, $node->at);
                $results[] = $result;
            }
        }

        $this->variables = $outer;

        return $results;
    }

    private function value(Node $node): mixed
    {
        $this->meter->step($node->at);

        if (! $this->explain) {
            return $node->accept($this);
        }

        $outer = $this->trace;
        $this->trace = [];
        $value = $node->accept($this);
        $this->meter->keep($value, $node->at);
        $outer[] = new Step($node, $value, $this->trace);
        $this->trace = $outer;

        return $value;
    }

    /** Stops the run unless the value, which this node gave, is of the type. */
    private function fit(mixed $value, Node $node, Type $expected): void
    {
        if ($value === null) {
            throw $node instanceof Field || $node instanceof Member
                ? FormulaError::of('value.empty_field', $node->at, ['field' => $node->name, 'expected' => $expected])
                : FormulaError::of('value.empty', $node->at, ['expected' => $expected]);
        }

        if (! $expected->admits($value)) {
            throw FormulaError::of('value.wrong_type', $node->at, ['expected' => $expected, 'actual' => Type::of($value)]);
        }
    }

    private function need(Node $node, Type $expected): mixed
    {
        $value = $this->value($node);
        $this->fit($value, $node, $expected);

        return $value;
    }

    private function boolean(Node $node): bool
    {
        return $this->need($node, Type::boolean());
    }

    private function number(Node $node): BigDecimal
    {
        return $this->need($node, Type::number());
    }

    private function numberOrDate(Node $node): BigDecimal|Date
    {
        return $this->need($node, Type::oneOf(Type::number(), Type::date()));
    }

    private function memberOf(mixed $of, Member $node): mixed
    {
        if ($of === null) {
            return null;
        }

        if (is_array($of)) {
            $values = [];

            foreach ($of as $item) {
                $this->meter->step($node->at);
                $value = $this->memberOf($item, $node);
                $this->meter->keep($value, $node->at);
                $values[] = $value;
            }

            return $values;
        }

        if (! $of instanceof Record) {
            throw FormulaError::of('value.wrong_type', $node->of->at, ['expected' => Type::record(Schema::make()), 'actual' => Type::of($of)]);
        }

        $field = $of->fields->find($node->name)
            ?? throw FormulaError::of('check.unknown_member', $node->at, ['name' => $node->name]);

        return $of->values[$field->key] ?? null;
    }

    /** AND stops at the first false operand. */
    private function every(Operation $node): bool
    {
        foreach ($node->operands as $operand) {
            if (! $this->boolean($operand)) {
                return false;
            }
        }

        return true;
    }

    /** OR stops at the first true operand. */
    private function some(Operation $node): bool
    {
        foreach ($node->operands as $operand) {
            if ($this->boolean($operand)) {
                return true;
            }
        }

        return false;
    }

    private function add(Operation $node): BigDecimal|Date
    {
        $total = $this->numberOrDate($node->operands[0]);

        foreach (array_slice($node->operands, 1) as $operand) {
            $next = $this->numberOrDate($operand);

            if ($total instanceof Date) {
                $this->fit($next, $operand, Type::number());
                $total = $this->shift($total, $next, $node);
            } else {
                $total = $next instanceof Date ? $this->shift($next, $total, $node) : $total->plus($next);
            }
        }

        return $total;
    }

    private function subtract(Operation $node): BigDecimal|Date
    {
        [$first, $second] = $node->operands;
        $left = $this->numberOrDate($first);
        $right = $this->numberOrDate($second);

        if ($left instanceof Date) {
            return $right instanceof Date
                ? BigDecimal::of($right->daysUntil($left))
                : $this->shift($left, $right->negated(), $node);
        }

        $this->fit($right, $second, Type::number());

        return $left->minus($right);
    }

    private function shift(Date $date, BigDecimal $days, Operation $node): Date
    {
        if ($days->strippedOfTrailingZeros()->getScale() > 0) {
            throw FormulaError::of('value.whole_days', $node->at);
        }

        $whole = Numbers::whole($days, -self::DAYS, self::DAYS);

        return ($whole === null ? null : $date->addDays($whole))
            ?? throw FormulaError::of('value.date_out_of_range', $node->at);
    }

    private function multiply(Operation $node): BigDecimal
    {
        $product = $this->number($node->operands[0]);

        foreach (array_slice($node->operands, 1) as $operand) {
            // Rounded as it goes: each factor could add its own decimal places.
            $product = Numbers::bounded($product->multipliedBy($this->number($operand)), $node->at);
        }

        return $product;
    }

    private function divide(Operation $node): BigDecimal
    {
        $dividend = $this->number($node->operands[0]);
        $divisor = $this->number($node->operands[1]);

        if ($divisor->isZero()) {
            throw FormulaError::of('value.division_by_zero', $node->at);
        }

        return $dividend->dividedBy($divisor, Numbers::SCALE, RoundingMode::HalfUp);
    }

    /** By squaring, bounding every product, so that no power grows out of hand. */
    private function power(Operation $node): BigDecimal
    {
        $base = $this->number($node->operands[0]);
        $exponent = Numbers::whole($this->number($node->operands[1]), -self::POWER, self::POWER)
            ?? throw FormulaError::of('value.exponent', $node->at, ['limit' => self::POWER]);

        if ($exponent < 0) {
            if ($base->isZero()) {
                throw FormulaError::of('value.division_by_zero', $node->at);
            }

            $base = BigDecimal::one()->dividedBy($base, Numbers::SCALE, RoundingMode::HalfUp);
            $exponent = -$exponent;
        }

        $result = BigDecimal::one();

        while ($exponent > 0) {
            if ($exponent % 2 === 1) {
                $result = Numbers::bounded($result->multipliedBy($base), $node->at);
            }

            $exponent = intdiv($exponent, 2);

            if ($exponent > 0) {
                $base = Numbers::bounded($base->multipliedBy($base), $node->at);
            }
        }

        return $result;
    }

    private function concat(Operation $node): string
    {
        $parts = [];

        foreach ($node->operands as $operand) {
            $value = $this->value($operand);

            if ($value !== null) {
                $this->fit($value, $operand, Scalars::type());
            }

            $parts[] = Scalars::text($value);
        }

        $this->meter->mustFit(array_sum(array_map(strlen(...), $parts)), $node->at);

        return implode('', $parts);
    }

    /** An empty value equals an empty value or an empty text, and nothing else. */
    private function equal(Operation $node): bool
    {
        $left = $this->value($node->operands[0]);
        $right = $this->value($node->operands[1]);

        if (Scalars::blank($left) || Scalars::blank($right)) {
            return Scalars::blank($left) && Scalars::blank($right);
        }

        return $this->compare($left, $right, $node) === 0;
    }

    private function order(Operation $node): int
    {
        $ordered = Type::oneOf(Type::number(), Type::text(), Type::date());

        return $this->compare(
            $this->need($node->operands[0], $ordered),
            $this->need($node->operands[1], $ordered),
            $node,
        );
    }

    /** Below zero, zero or above, as the left value is to the right. Texts compare whatever their case. */
    private function compare(mixed $left, mixed $right, Operation $node): int
    {
        $kind = Type::of($left);

        if (! Scalars::type()->accepts($kind) || $kind->kind !== Type::of($right)->kind) {
            throw FormulaError::of('value.compare', $node->at, ['left' => $kind, 'right' => Type::of($right)]);
        }

        return match (true) {
            $left instanceof BigDecimal => $left->compareTo($right),
            is_string($left) => strcmp(mb_strtolower($left), mb_strtolower($right)),
            // Dates, and yes or no, compare as they are.
            default => $left <=> $right,
        };
    }
}
