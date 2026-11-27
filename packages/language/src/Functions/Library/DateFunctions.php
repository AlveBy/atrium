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
use Atrium\Language\Values\Date;

final class DateFunctions
{
    /** The furthest a month or a day may roll over: more than all the years there are. */
    private const REACH = 4000000;

    /** @return list<FormulaFunction> */
    public static function all(): array
    {
        $date = Parameter::of(Type::date());
        $number = Parameter::of(Type::number());

        return [
            new FormulaFunction(['TODAY', 'СЕГОДНЯ'], [], Type::date(), static fn (Invocation $call): Date => $call->today),
            new FormulaFunction(['DATE', 'ДАТА'], [$number, $number, $number], Type::date(), static fn (Invocation $call): Date => self::within($call, Date::of(
                $call->whole(0, 1, 9999),
                $call->whole(1, -self::REACH, self::REACH),
                $call->whole(2, -self::REACH, self::REACH),
            ))),
            new FormulaFunction(['YEAR', 'ГОД'], [$date], Type::number(), static fn (Invocation $call): int => $call->argument(0)->year()),
            new FormulaFunction(['MONTH', 'МЕСЯЦ'], [$date], Type::number(), static fn (Invocation $call): int => $call->argument(0)->month()),
            new FormulaFunction(['DAY', 'ДЕНЬ'], [$date], Type::number(), static fn (Invocation $call): int => $call->argument(0)->day()),
            new FormulaFunction(['EDATE', 'ДАТАМЕС'], [$date, $number], Type::date(), static fn (Invocation $call): Date => self::within(
                $call,
                $call->argument(0)->addMonths($call->whole(1, -self::REACH, self::REACH)),
            )),
            new FormulaFunction(['EOMONTH', 'КОНМЕСЯЦА'], [$date, $number], Type::date(), static fn (Invocation $call): Date => self::within(
                $call,
                $call->argument(0)->endOfMonth($call->whole(1, -self::REACH, self::REACH)),
            )),
            new FormulaFunction(['DAYS', 'ДНИ'], [$date, $date], Type::number(), static fn (Invocation $call): int => $call->argument(1)->daysUntil($call->argument(0))),
            // 1 counts from Sunday, as Excel does when not told; 2 counts from Monday.
            new FormulaFunction(['WEEKDAY', 'ДЕНЬНЕД'], [$date, $number->optional()], Type::number(), static fn (Invocation $call): int => $call->whole(1, 1, 2, 1) === 1
                ? $call->argument(0)->weekday() % 7 + 1
                : $call->argument(0)->weekday()),
        ];
    }

    private static function within(Invocation $call, ?Date $date): Date
    {
        return $date ?? $call->fail('value.date_out_of_range');
    }
}
