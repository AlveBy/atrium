<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Functions\Library;

use Atrium\Language\Functions\FormulaFunction;
use Atrium\Language\Functions\Invocation;
use Atrium\Language\Functions\Parameter;
use Atrium\Language\Types\Type;
use Atrium\Language\Values\Numbers;
use Atrium\Language\Values\Scalars;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Closure;

final class MathFunctions
{
    /** @return list<FormulaFunction> */
    public static function all(): array
    {
        $number = Type::number();
        $one = [Parameter::of($number)];
        // Totals take numbers and lists of them, and pass over empty ones.
        $many = [Parameter::of(Type::oneOf($number, Type::listOf($number)))->variadic()->orEmpty()];
        $anything = [Parameter::of(Type::oneOf(Scalars::type(), Type::listOf(Type::any())))->variadic()->orEmpty()];
        $rounding = [Parameter::of($number), Parameter::of($number)];

        return [
            new FormulaFunction(['SUM', 'СУММ'], $many, $number, static fn (Invocation $call): BigDecimal => self::total($call->values())),
            new FormulaFunction(['AVERAGE', 'СРЗНАЧ'], $many, $number, self::average(...)),
            new FormulaFunction(['MIN', 'МИН'], $many, $number, static fn (Invocation $call): BigDecimal => BigDecimal::min(...self::some($call))),
            new FormulaFunction(['MAX', 'МАКС'], $many, $number, static fn (Invocation $call): BigDecimal => BigDecimal::max(...self::some($call))),
            new FormulaFunction(['COUNT', 'СЧЁТ', 'СЧЕТ'], $anything, $number, static fn (Invocation $call): int => count($call->values())),
            new FormulaFunction(['ROUND', 'ОКРУГЛ'], $rounding, $number, self::rounded(RoundingMode::HalfUp)),
            new FormulaFunction(['ROUNDUP', 'ОКРУГЛВВЕРХ'], $rounding, $number, self::rounded(RoundingMode::Up)),
            new FormulaFunction(['ROUNDDOWN', 'ОКРУГЛВНИЗ'], $rounding, $number, self::rounded(RoundingMode::Down)),
            new FormulaFunction(['INT', 'ЦЕЛОЕ'], $one, $number, static fn (Invocation $call): BigDecimal => $call->argument(0)->toScale(0, RoundingMode::Floor)),
            new FormulaFunction(['ABS'], $one, $number, static fn (Invocation $call): BigDecimal => $call->argument(0)->abs()),
            new FormulaFunction(['MOD', 'ОСТАТ'], $rounding, $number, self::modulo(...)),
            new FormulaFunction(['SQRT', 'КОРЕНЬ'], $one, $number, static fn (Invocation $call): BigDecimal => $call->argument(0)->isNegative()
                ? $call->fail('value.negative_root')
                : $call->argument(0)->sqrt(Numbers::SCALE, RoundingMode::HalfUp)),
        ];
    }

    /** @param  list<BigDecimal>  $values */
    private static function total(array $values): BigDecimal
    {
        $total = BigDecimal::zero();

        foreach ($values as $value) {
            $total = $total->plus($value);
        }

        return $total;
    }

    private static function average(Invocation $call): BigDecimal
    {
        $values = self::some($call);

        return self::total($values)->dividedBy(count($values), Numbers::SCALE, RoundingMode::HalfUp);
    }

    /**
     * The values, stopping the run when there is none.
     *
     * @return non-empty-list<BigDecimal>
     */
    private static function some(Invocation $call): array
    {
        return $call->values() ?: $call->fail('value.no_values');
    }

    /** @return Closure(Invocation): BigDecimal */
    private static function rounded(RoundingMode $mode): Closure
    {
        return static function (Invocation $call) use ($mode): BigDecimal {
            $places = $call->whole(1, -Numbers::DIGITS, Numbers::SCALE);

            return $call->argument(0)->withPointMovedRight($places)->toScale(0, $mode)->withPointMovedLeft($places);
        };
    }

    /** As in Excel, the remainder has the sign of the divisor. */
    private static function modulo(Invocation $call): BigDecimal
    {
        [$number, $divisor] = $call->arguments;

        if ($divisor->isZero()) {
            $call->fail('value.division_by_zero');
        }

        $remainder = $number->remainder($divisor);

        return ! $remainder->isZero() && $remainder->isNegative() !== $divisor->isNegative()
            ? $remainder->plus($divisor)
            : $remainder;
    }
}
