<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Feature;

use Atrium\Language\Context;
use Atrium\Language\Formula;
use Atrium\Language\Tests\TestCase;
use Atrium\Language\Types\Type;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;

/** The functions that come with the language, by both of their names. */
final class FunctionsTest extends TestCase
{
    /** @param  array<string,mixed>  $values */
    private function got(string $source, array $values = []): mixed
    {
        return $this->value($source, context: $this->context($values, $this->schema()->field('x', Type::any())));
    }

    /** @return iterable<string,array{string,mixed}> */
    public static function results(): iterable
    {
        $numbers = [
            'SUM(1; 2; 3.5)' => '6.5', 'СУММ(Lines.Price)' => '3', 'SUM(IF(FALSE; 1))' => '0', 'SUM(Lines.Qty; 10; Amount)' => '12',
            'AVERAGE(1; 2; 6)' => '3', 'СРЗНАЧ(1; 2; 4)' => '2.33333333333333333333', 'AVERAGE(2; 2; 1)' => '1.66666666666666666667', 'AVERAGE(Lines.Price)' => '1.5',
            'MIN(3; 1; 2)' => '1', 'МИН(Lines.Price; 5)' => '1', 'MAX(3; 5; 2)' => '5', 'МАКС(Lines.Price)' => '2',
            'COUNT(1; "a"; TRUE; 1.1.2026; Amount; Lines.Price)' => '6', 'СЧЁТ(Status)' => '0', 'СЧЕТ("")' => '1', 'COUNT(Lines)' => '3',
            'ROUND(2.5; 0)' => '3', 'ROUND(-2.5; 0)' => '-3', 'ROUND(2.4; 0)' => '2', 'ОКРУГЛ(1.235; 2)' => '1.24', 'ROUND(1250; -2)' => '1300',
            'ROUNDUP(1.01; 1)' => '1.1', 'ОКРУГЛВВЕРХ(-1.01; 1)' => '-1.1', 'ROUNDUP(1; 1)' => '1',
            'ROUNDDOWN(1.09; 1)' => '1', 'ОКРУГЛВНИЗ(-1.09; 1)' => '-1',
            'ROUND(1.5; 20)' => '1.5', 'ROUND(5; -40)' => '0',
            'INT(2.7)' => '2', 'ЦЕЛОЕ(-2.1)' => '-3', 'INT(3)' => '3',
            'ABS(-2)' => '2', 'ABS(2)' => '2',
            'MOD(7; 3)' => '1', 'MOD(-7; 3)' => '2', 'MOD(7; -3)' => '-2', 'ОСТАТ(-7; -3)' => '-1', 'MOD(6; -3)' => '0', 'MOD(-6; 3)' => '0', 'MOD(7.5; 2)' => '1.5',
            'SQRT(9)' => '3', 'КОРЕНЬ(2)' => '1.4142135623730950488', 'SQRT(0)' => '0',
            'LEN("abc")' => '3', 'ДЛСТР("Привет")' => '6', 'LEN(Status)' => '0',
            'YEAR(28.11.2026)' => '2026', 'ГОД(1.2.2027)' => '2027', 'MONTH(28.11.2026)' => '11', 'МЕСЯЦ(1.2.2027)' => '2', 'DAY(28.11.2026)' => '28', 'ДЕНЬ(1.2.2027)' => '1',
            'DAYS(2.1.2026; 1.1.2026)' => '1', 'ДНИ(1.1.2026; 3.1.2026)' => '-2',
            // 28.11.2026 is a Saturday.
            'WEEKDAY(28.11.2026)' => '7', 'ДЕНЬНЕД(28.11.2026; 1)' => '7', 'WEEKDAY(28.11.2026; 2)' => '6', 'WEEKDAY(29.11.2026)' => '1', 'WEEKDAY(29.11.2026; 2)' => '7', 'WEEKDAY(30.11.2026)' => '2',
        ];

        foreach ($numbers as $source => $number) {
            yield $source => [$source, self::num($number)];
        }

        $dates = [
            'TODAY()' => '2026-11-28', 'СЕГОДНЯ()' => '2026-11-28',
            'DATE(2026; 11; 28)' => '2026-11-28', 'ДАТА(2026; 13; 1)' => '2027-01-01', 'DATE(2026; 1; 0)' => '2025-12-31', 'DATE(1; 1; 1)' => '0001-01-01', 'DATE(9999; 12; 31)' => '9999-12-31',
            'DATE(2026; -4000; 1)' => null,
            'EDATE(31.1.2026; 1)' => '2026-02-28', 'ДАТАМЕС(31.3.2026; -1)' => '2026-02-28',
            'EOMONTH(10.2.2026; 0)' => '2026-02-28', 'КОНМЕСЯЦА(10.2.2026; 1)' => '2026-03-31',
        ];

        foreach (array_filter($dates) as $source => $date) {
            yield $source => [$source, self::day($date)];
        }

        $texts = [
            'LEFT("abc")' => 'a', 'LEFT("abc"; 2)' => 'ab', 'LEFT("abc"; 0)' => '', 'LEFT("abc"; 5)' => 'abc', 'ЛЕВСИМВ("Привет"; 3)' => 'При', 'LEFT(Status)' => '',
            'RIGHT("abcd")' => 'd', 'RIGHT("abcd"; 2)' => 'cd', 'RIGHT("abcd"; 0)' => '', 'RIGHT("abcd"; 4)' => 'abcd', 'RIGHT("abcd"; 5)' => 'abcd', 'ПРАВСИМВ("Привет"; 2)' => 'ет',
            'MID("abcdef"; 2; 3)' => 'bcd', 'MID("abc"; 1; 0)' => '', 'MID("abc"; 4; 1)' => '', 'MID("abc"; 1; 10)' => 'abc', 'ПСТР("Привет"; 3; 2)' => 'ив',
            'UPPER("Straße ёж")' => mb_strtoupper('Straße ёж'), 'ПРОПИСН("ёж")' => 'ЁЖ', 'LOWER("ABC")' => 'abc', 'СТРОЧН("ЁЖ")' => 'ёж',
            "TRIM(\"  a \t\n b\u{00A0}\")" => 'a b', 'СЖПРОБЕЛЫ(" ё  ж ")' => 'ё ж', 'TRIM(Status)' => '',
            'CONCAT("a"; 1.50; TRUE; Lines.Title; Status; 1.1.2026)' => 'a1.5TRUExy2026-01-01', 'СЦЕПИТЬ("a"; "b")' => 'ab', 'СЦЕП(Status)' => '',
            'SUBSTITUTE("aAa"; "a"; "b")' => 'bAb', 'SUBSTITUTE("abc"; ""; "x")' => 'abc', 'SUBSTITUTE("abc"; "b"; "")' => 'ac', 'ПОДСТАВИТЬ("ёж ёж"; "ёж"; "кот")' => 'кот кот', 'SUBSTITUTE(Status; "a"; "b")' => '',
            'REPT("ab"; 3)' => 'ababab', 'ПОВТОР("ё"; 2)' => 'ёё', 'REPT("ab"; 0)' => '', 'REPT(""; 1000000000)' => '',
        ];

        foreach ($texts as $source => $text) {
            yield $source => [$source, $text];
        }

        $answers = [
            'CONTAINS("Hello"; "ELL")' => true, 'СОДЕРЖИТ("Ёжик"; "ёж")' => true, 'CONTAINS("ёжик"; "ЁЖ")' => true, 'CONTAINS("abc"; "d")' => false, 'CONTAINS("abc"; "")' => true, 'CONTAINS(Status; "a")' => false,
            'CONTAINS("'.str_repeat('ё', 255).'"; "'.str_repeat('ё', 255).'")' => true,
            'ISBLANK(Status)' => true, 'ЕПУСТО("")' => true, 'ISBLANK("a")' => false, 'ISBLANK(0)' => false, 'ISBLANK(FALSE)' => false,
        ];

        foreach ($answers as $source => $answer) {
            yield $source => [$source, $answer];
        }

        yield 'IFBLANK of nothing' => ['IFBLANK(Status; "x")', 'x'];
        yield 'IFBLANK of an empty text' => ['IFBLANK(""; "x")', 'x'];
        yield 'IFBLANK of a text' => ['ЕСЛИПУСТО("a"; "x")', 'a'];
        yield 'IFBLANK of a number' => ['IFBLANK(Amount; 5)', self::num(5)];
        yield 'IFBLANK of two nothings' => ['IFBLANK(Amount; Скидка)', null];
        yield 'IFBLANK of another kind' => ['IFBLANK(Amount; "none")', 'none'];
    }

    #[DataProvider('results')]
    public function test_a_function_gives_its_result(string $source, mixed $result): void
    {
        $this->assertSame($result, $this->got($source, ['lines' => [
            ['price' => 1, 'title' => 'x'],
            ['qty' => 2, 'title' => 'y'],
            ['price' => 2],
        ]]));
    }

    /** @return iterable<string,array{string,string,string}> */
    public static function failures(): iterable
    {
        $at = static fn (string $message, int $column = 1): string => "Line 1, column {$column}: {$message}";
        $range = static fn (string $function, int $argument, string $min, string $max): array => ['value.out_of_range', $at("Argument {$argument} of the function {$function} must be a whole number from {$min} to {$max}.")];
        $none = static fn (string $function): array => ['value.no_values', $at("The function {$function} has no values to work with.")];
        $date = ['value.date_out_of_range', $at('The date is outside the years 1 to 9999.')];
        $zero = ['value.division_by_zero', $at('Division by zero.')];
        $root = ['value.negative_root', $at('There is no square root of a negative number.')];
        $long = static fn (string $function): array => ['value.search_too_long', $at("The function {$function} can search for at most 255 characters.")];
        $max = (string) PHP_INT_MAX;
        $searched = str_repeat('a', 256);
        $large = ['value.too_large', $at('A number is too large: it has more than 40 digits before the decimal point.')];

        yield 'AVERAGE of nothing' => ['AVERAGE(Amount)', ...$none('AVERAGE')];
        yield 'MIN of nothing' => ['МИН(Amount; Скидка)', ...$none('МИН')];
        yield 'MAX of nothing' => ['MAX(Lines.Price)', ...$none('MAX')];
        yield 'too many places' => ['ROUND(1; 21)', ...$range('ROUND', 2, '-40', '20')];
        yield 'too few places' => ['ROUNDUP(1; -41)', ...$range('ROUNDUP', 2, '-40', '20')];
        yield 'half a place' => ['ROUNDDOWN(1; 0.5)', ...$range('ROUNDDOWN', 2, '-40', '20')];
        yield 'rounding up past the largest number' => ['ROUNDUP(1; -40)', ...$large];
        yield 'MOD by zero' => ['MOD(1; 0)', ...$zero];
        yield 'a root below zero' => ['SQRT(-1)', ...$root];
        yield 'LEFT less than none' => ['LEFT("a"; -1)', ...$range('LEFT', 2, '0', $max)];
        yield 'LEFT a part' => ['LEFT("a"; 1.5)', ...$range('LEFT', 2, '0', $max)];
        yield 'RIGHT less than none' => ['RIGHT("a"; -1)', ...$range('RIGHT', 2, '0', $max)];
        yield 'MID from before the start' => ['MID("a"; 0; 1)', ...$range('MID', 2, '1', $max)];
        yield 'MID of less than none' => ['MID("a"; 1; -1)', ...$range('MID', 3, '0', $max)];
        yield 'REPT less than none' => ['REPT("a"; -1)', ...$range('REPT', 2, '0', '1000000000')];
        yield 'REPT too many times' => ['REPT(""; 1000000001)', ...$range('REPT', 2, '0', '1000000000')];
        yield 'REPT past the memory' => ['REPT("a"; 1000000000)', 'limit.memory', $at('The formula was stopped: its values took more than 2097152 bytes.')];
        yield 'searching for too much' => ["CONTAINS(\"a\"; \"{$searched}\")", ...$long('CONTAINS')];
        yield 'replacing too much' => ["SUBSTITUTE(\"a\"; \"{$searched}\"; \"\")", ...$long('SUBSTITUTE')];
        yield 'a year before the first' => ['DATE(0; 1; 1)', ...$range('DATE', 1, '1', '9999')];
        yield 'a year after the last' => ['DATE(10000; 1; 1)', ...$range('DATE', 1, '1', '9999')];
        yield 'too many months' => ['DATE(2026; 4000001; 1)', ...$range('DATE', 2, '-4000000', '4000000')];
        yield 'too few months' => ['DATE(2026; -4000001; 1)', ...$range('DATE', 2, '-4000000', '4000000')];
        yield 'the most months' => ['DATE(2026; 4000000; 1)', ...$date];
        yield 'the fewest months' => ['DATE(2026; -4000000; 1)', ...$date];
        yield 'too many days' => ['DATE(2026; 1; 4000001)', ...$range('DATE', 3, '-4000000', '4000000')];
        yield 'too few days' => ['DATE(2026; 1; -4000001)', ...$range('DATE', 3, '-4000000', '4000000')];
        yield 'the most days' => ['DATE(2026; 1; 4000000)', ...$date];
        yield 'the fewest days' => ['DATE(2026; 1; -4000000)', ...$date];
        yield 'a day past the last' => ['DATE(9999; 12; 32)', ...$date];
        yield 'EDATE past the last day' => ['EDATE(31.12.9999; 1)', ...$date];
        yield 'EDATE too far' => ['EDATE(1.1.2026; 4000001)', ...$range('EDATE', 2, '-4000000', '4000000')];
        yield 'EDATE too far back' => ['EDATE(1.1.2026; -4000001)', ...$range('EDATE', 2, '-4000000', '4000000')];
        yield 'EDATE the furthest' => ['EDATE(1.1.2026; 4000000)', ...$date];
        yield 'EDATE the furthest back' => ['EDATE(1.1.2026; -4000000)', ...$date];
        yield 'EOMONTH before the first day' => ['EOMONTH(1.1.0001; -1)', ...$date];
        yield 'EOMONTH too far' => ['EOMONTH(1.1.2026; 4000001)', ...$range('EOMONTH', 2, '-4000000', '4000000')];
        yield 'EOMONTH too far back' => ['EOMONTH(1.1.2026; -4000001)', ...$range('EOMONTH', 2, '-4000000', '4000000')];
        yield 'EOMONTH the furthest' => ['EOMONTH(1.1.2026; 4000000)', ...$date];
        yield 'EOMONTH the furthest back' => ['EOMONTH(1.1.2026; -4000000)', ...$date];
        yield 'a third way to count weekdays' => ['WEEKDAY(1.1.2026; 3)', ...$range('WEEKDAY', 2, '1', '2')];
        yield 'no way to count weekdays' => ['WEEKDAY(1.1.2026; 0)', ...$range('WEEKDAY', 2, '1', '2')];

        yield 'an empty argument' => ['ROUND(Amount; 2)', 'value.empty_field', $at('The field “Amount” is empty, but the formula needs: number.', 7)];
        yield 'an empty optional argument' => ['LEFT("a"; Amount)', 'value.empty_field', $at('The field “Amount” is empty, but the formula needs: number.', 11)];
        yield 'an empty result as an argument' => ['ABS(IF(FALSE; 1))', 'value.empty', $at('A value is empty here, but the formula needs: number.', 5)];
        yield 'a wrong argument found while running' => ['ABS(X)', 'value.wrong_type', $at('The formula needs: number, found: text.', 5)];
        yield 'a wrong argument that may be empty' => ['LEN(X)', 'value.wrong_type', $at('The formula needs: text, found: number.', 5)];
    }

    #[DataProvider('failures')]
    public function test_a_function_stops_the_run(string $source, string $code, string $message): void
    {
        $values = ['x' => str_starts_with($source, 'LEN') ? 5 : 'a'];

        $this->assertStops($code, $message, fn () => $this->got($source, $values));
    }

    public function test_today_follows_the_application_when_not_given(): void
    {
        Carbon::setTestNow('2027-03-04 23:00:00');

        try {
            $context = Context::make($this->schema());

            $this->assertSame('2027-03-04', (string) Formula::parse('TODAY()')->evaluate($context)->value);
        } finally {
            Carbon::setTestNow();
        }
    }
}
