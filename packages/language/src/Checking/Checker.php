<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Checking;

use Atrium\Language\Check;
use Atrium\Language\Context;
use Atrium\Language\Functions\FormulaFunction;
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
use Atrium\Language\Problem;
use Atrium\Language\Types\Kind;
use Atrium\Language\Types\Type;
use Atrium\Language\Values\Scalars;

/**
 * Looks over a tree before it runs: the names of fields and functions, the
 * permissions, the types and the depth. It goes on after a problem, so that
 * all of them are reported at once.
 *
 * @implements Visitor<Type>
 */
final class Checker implements Visitor
{
    /** How deep lists may be inside one another. */
    public const LISTS = 8;

    /** The most problems it reports: enough to work through, and quick to put into words. */
    public const PROBLEMS = 100;

    /** The kinds of values that `=` and `<>` compare. */
    private const COMPARABLE = [Kind::Number, Kind::Text, Kind::Boolean, Kind::Date, Kind::Any, Kind::Empty];

    /** The kinds of values that have an order. */
    private const ORDERED = [Kind::Number, Kind::Text, Kind::Date, Kind::Any, Kind::Empty];

    /** @var list<Problem> */
    private array $problems = [];

    /** @var array<string,FormulaFunction> */
    private array $functions = [];

    /** @var array<string,Type> the items of the loops around the node */
    private array $variables = [];

    private int $depth = 0;

    public function __construct(private readonly Context $context) {}

    public function check(Node $root): Check
    {
        $type = $this->type($root);

        return new Check($this->problems, $type, $this->functions);
    }

    public function literal(Literal $node): Type
    {
        return Type::of($node->value);
    }

    public function field(Field $node): Type
    {
        return $this->context->schema()->find($node->name)->type
            ?? $this->report('check.unknown_field', $node, ['name' => $node->name]);
    }

    public function variable(Variable $node): Type
    {
        return $this->variables[$node->name]
            ?? $this->report('check.unknown_variable', $node, ['name' => $node->name]);
    }

    public function member(Member $node): Type
    {
        return $this->memberOf($this->type($node->of), $node);
    }

    public function operation(Operation $node): Type
    {
        $types = array_map($this->type(...), $node->operands);

        return match ($node->operator) {
            Operator::And, Operator::Or, Operator::Not => $this->all($node, $types, Type::boolean()),
            Operator::Negate, Operator::Percent, Operator::Multiply, Operator::Divide, Operator::Power => $this->all($node, $types, Type::number()),
            Operator::Concat => $this->concat($node, $types),
            Operator::Add => $this->add($node, $types),
            Operator::Subtract => $this->subtract($node, $types[0], $types[1]),
            Operator::Equal, Operator::NotEqual => $this->comparison($node, $types[0], $types[1], self::COMPARABLE),
            default => $this->comparison($node, $types[0], $types[1], self::ORDERED),
        };
    }

    public function call(Call $node): Type
    {
        $types = array_map($this->type(...), $node->arguments);
        $function = $this->context->functions()->find($node->function);

        if ($function === null) {
            return $this->report('check.unknown_function', $node, ['name' => $node->function]);
        }

        $this->functions[$node->function] = $function;

        if ($function->permission !== null && ! in_array($function->permission, $this->context->permissions(), true)) {
            $this->report('check.permission', $node, ['function' => $node->function, 'permission' => $function->permission]);
        }

        $given = count($types);

        if ($given < $function->minimum()) {
            return $this->report('arguments.too_few', $node, ['function' => $node->function, 'min' => $function->minimum(), 'given' => $given]);
        }

        if ($function->maximum() !== null && $given > $function->maximum()) {
            return $this->report('arguments.too_many', $node, ['function' => $node->function, 'max' => $function->maximum(), 'given' => $given]);
        }

        foreach ($types as $position => $type) {
            $expected = $function->parameter($position)->type;

            if (! $expected->accepts($type)) {
                $this->report('check.argument', $node->arguments[$position], [
                    'function' => $node->function,
                    'argument' => $position + 1,
                    'expected' => $expected,
                    'actual' => $type,
                ]);
            }
        }

        return $function->returnType($types);
    }

    public function condition(Condition $node): Type
    {
        $this->boolean($node->when);
        $then = $this->type($node->then);
        $otherwise = $node->otherwise === null ? Type::empty() : $this->type($node->otherwise);

        return Type::unify($then, $otherwise)
            ?? $this->report('check.branches', $node, ['then' => $then, 'otherwise' => $otherwise]);
    }

    public function each(Each $node): Type
    {
        $source = $this->type($node->source);

        $item = match ($source->kind) {
            Kind::List => $source->of,
            Kind::Any, Kind::Empty => Type::any(),
            default => $this->report('check.not_a_list', $node->source, ['actual' => $source]),
        };

        $outer = $this->variables;
        $this->variables[$node->variable] = $item;

        if ($node->where !== null) {
            $this->boolean($node->where);
        }

        $body = $this->type($node->body);
        $this->variables = $outer;

        return Type::listOf($body);
    }

    private function type(Node $node): Type
    {
        $limit = $this->context->limits()->depth;

        if ($this->depth >= $limit) {
            return $this->report('check.depth', $node, ['limit' => $limit]);
        }

        $this->depth++;
        $type = $node->accept($this);
        $this->depth--;

        // Loops and fields of related records could build a type without end.
        if ($this->lists($type) > self::LISTS) {
            return $this->report('check.deep_list', $node, ['limit' => self::LISTS]);
        }

        return $type;
    }

    /** How many lists are inside one another in the type. */
    private function lists(Type $type): int
    {
        return $type->of === null ? 0 : 1 + $this->lists($type->of);
    }

    /**
     * Notes a problem. The type it returns, `any`, keeps the same mistake from
     * being reported again further up.
     *
     * @param  array<string,string|int|Type>  $params
     */
    private function report(string $code, Node $node, array $params): Type
    {
        if (count($this->problems) < self::PROBLEMS) {
            $this->problems[] = new Problem($code, $params, $node->at);
        }

        return Type::any();
    }

    private function memberOf(Type $of, Member $node): Type
    {
        return match ($of->kind) {
            Kind::List => Type::listOf($this->memberOf($of->of, $node)),
            Kind::Record => $of->fields->find($node->name)->type
                ?? $this->report('check.unknown_member', $node, ['name' => $node->name]),
            Kind::Any, Kind::Empty => Type::any(),
            default => $this->report('check.not_a_record', $node, ['name' => $node->name, 'actual' => $of]),
        };
    }

    private function expect(Operation $node, int $position, Type $actual, Type $expected): void
    {
        if (! $expected->accepts($actual)) {
            $this->report('check.operand', $node->operands[$position], [
                'operator' => $node->operator->symbol(),
                'expected' => $expected,
                'actual' => $actual,
            ]);
        }
    }

    /** @param  list<Type>  $types */
    private function all(Operation $node, array $types, Type $expected): Type
    {
        foreach ($types as $position => $type) {
            $this->expect($node, $position, $type, $expected);
        }

        return $expected;
    }

    /** @param  list<Type>  $types */
    private function concat(Operation $node, array $types): Type
    {
        $this->all($node, $types, Scalars::type());

        return Type::text();
    }

    /**
     * Numbers add up; a date and numbers of days make a date.
     *
     * @param  list<Type>  $types
     */
    private function add(Operation $node, array $types): Type
    {
        $date = false;
        $unknown = false;

        foreach ($types as $position => $type) {
            if ($type->kind === Kind::Date && ! $date) {
                $date = true;
            } elseif ($type->kind === Kind::Any) {
                $unknown = true;
            } else {
                $this->expect($node, $position, $type, Type::number());
            }
        }

        if ($date) {
            return Type::date();
        }

        return $unknown ? Type::any() : Type::number();
    }

    /** A date less days is a date; a date less a date is the days between them. */
    private function subtract(Operation $node, Type $left, Type $right): Type
    {
        if ($left->kind === Kind::Any) {
            $this->expect($node, 1, $right, Type::oneOf(Type::number(), Type::date()));

            return Type::any();
        }

        if ($left->kind === Kind::Date) {
            if ($right->kind === Kind::Date) {
                return Type::number();
            }

            if ($right->kind === Kind::Any) {
                return Type::any();
            }

            $this->expect($node, 1, $right, Type::number());

            return Type::date();
        }

        $this->expect($node, 0, $left, Type::number());
        $this->expect($node, 1, $right, Type::number());

        return Type::number();
    }

    /** @param  list<Kind>  $kinds */
    private function comparison(Operation $node, Type $left, Type $right, array $kinds): Type
    {
        $common = Type::unify($left, $right);

        if ($common === null || ! in_array($common->kind, $kinds, true)) {
            $this->report('check.compare', $node, ['operator' => $node->operator->symbol(), 'left' => $left, 'right' => $right]);
        }

        return Type::boolean();
    }

    private function boolean(Node $node): void
    {
        $type = $this->type($node);

        if (! Type::boolean()->accepts($type)) {
            $this->report('check.condition', $node, ['actual' => $type]);
        }
    }
}
