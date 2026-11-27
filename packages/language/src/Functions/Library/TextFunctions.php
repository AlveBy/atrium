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
use Atrium\Language\Values\Scalars;

/** An empty value is an empty text here. Lengths and places count characters. */
final class TextFunctions
{
    /**
     * The longest text searched for. Searching takes the length of one text
     * times the other at worst, so one of them is kept short.
     */
    public const SEARCH = 255;

    /** @return list<FormulaFunction> */
    public static function all(): array
    {
        $text = Parameter::of(Type::text())->orEmpty();
        $number = Parameter::of(Type::number());
        $parts = [Parameter::of(Type::oneOf(Scalars::type(), Type::listOf(Scalars::type())))->variadic()->orEmpty()];

        return [
            new FormulaFunction(['LEN', 'ДЛСТР'], [$text], Type::number(), static fn (Invocation $call): int => mb_strlen($call->text(0))),
            new FormulaFunction(['LEFT', 'ЛЕВСИМВ'], [$text, $number->optional()], Type::text(), static fn (Invocation $call): string => mb_substr(
                $call->text(0),
                0,
                $call->whole(1, 0, PHP_INT_MAX, 1),
            )),
            new FormulaFunction(['RIGHT', 'ПРАВСИМВ'], [$text, $number->optional()], Type::text(), static fn (Invocation $call): string => mb_substr(
                $call->text(0),
                max(0, mb_strlen($call->text(0)) - $call->whole(1, 0, PHP_INT_MAX, 1)),
            )),
            new FormulaFunction(['MID', 'ПСТР'], [$text, $number, $number], Type::text(), static fn (Invocation $call): string => mb_substr(
                $call->text(0),
                $call->whole(1, 1, PHP_INT_MAX) - 1,
                $call->whole(2, 0, PHP_INT_MAX),
            )),
            new FormulaFunction(['UPPER', 'ПРОПИСН'], [$text], Type::text(), static fn (Invocation $call): string => mb_strtoupper($call->text(0))),
            new FormulaFunction(['LOWER', 'СТРОЧН'], [$text], Type::text(), static fn (Invocation $call): string => mb_strtolower($call->text(0))),
            new FormulaFunction(['TRIM', 'СЖПРОБЕЛЫ'], [$text], Type::text(), static fn (Invocation $call): string => trim(
                preg_replace('/\s+/u', ' ', $call->text(0)),
            )),
            new FormulaFunction(['CONCAT', 'СЦЕПИТЬ', 'СЦЕП'], $parts, Type::text(), self::concat(...)),
            new FormulaFunction(['SUBSTITUTE', 'ПОДСТАВИТЬ'], [$text, $text, $text], Type::text(), self::substitute(...)),
            new FormulaFunction(['REPT', 'ПОВТОР'], [$text, $number], Type::text(), static function (Invocation $call): string {
                $times = $call->whole(1, 0, 1000000000);
                $call->mustFit(strlen($call->text(0)) * $times);

                return str_repeat($call->text(0), $times);
            }),
            new FormulaFunction(['CONTAINS', 'СОДЕРЖИТ'], [$text, $text], Type::boolean(), static fn (Invocation $call): bool => str_contains(
                mb_strtolower($call->text(0)),
                mb_strtolower(self::searched($call, 1)),
            )),
        ];
    }

    private static function concat(Invocation $call): string
    {
        $parts = array_map(Scalars::text(...), $call->values());
        $call->mustFit(array_sum(array_map(strlen(...), $parts)));

        return implode('', $parts);
    }

    /** As in Excel, the case matters, and there is nothing to replace an empty text with. */
    private static function substitute(Invocation $call): string
    {
        $text = $call->text(0);
        $old = self::searched($call, 1);
        $new = $call->text(2);

        if ($old === '') {
            return $text;
        }

        $call->mustFit(strlen($text) + substr_count($text, $old) * (strlen($new) - strlen($old)));

        return str_replace($old, $new, $text);
    }

    private static function searched(Invocation $call, int $position): string
    {
        $text = $call->text($position);

        if (mb_strlen($text) > self::SEARCH) {
            $call->fail('value.search_too_long', ['limit' => self::SEARCH]);
        }

        return $text;
    }
}
