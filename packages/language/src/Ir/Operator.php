<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

enum Operator: string
{
    case Add = 'add';
    case Subtract = 'subtract';
    case Multiply = 'multiply';
    case Divide = 'divide';
    case Power = 'power';
    case Negate = 'negate';
    case Percent = 'percent';
    case Concat = 'concat';
    case Equal = 'equal';
    case NotEqual = 'not_equal';
    case Less = 'less';
    case LessOrEqual = 'less_or_equal';
    case Greater = 'greater';
    case GreaterOrEqual = 'greater_or_equal';
    case And = 'and';
    case Or = 'or';
    case Not = 'not';

    /** Whether the operator can stand between this many operands. */
    public function takes(int $operands): bool
    {
        return match ($this) {
            self::Negate, self::Percent, self::Not => $operands === 1,
            self::Add, self::Multiply, self::Concat, self::And, self::Or => $operands >= 1,
            default => $operands === 2,
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Add => '+',
            self::Subtract, self::Negate => '-',
            self::Multiply => '*',
            self::Divide => '/',
            self::Power => '^',
            self::Percent => '%',
            self::Concat => '&',
            self::Equal => '=',
            self::NotEqual => '<>',
            self::Less => '<',
            self::LessOrEqual => '<=',
            self::Greater => '>',
            self::GreaterOrEqual => '>=',
            self::And => 'AND',
            self::Or => 'OR',
            self::Not => 'NOT',
        };
    }
}
