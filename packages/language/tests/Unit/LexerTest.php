<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Unit;

use Atrium\Language\Parser\Lexer;
use Atrium\Language\Parser\Token;
use Atrium\Language\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class LexerTest extends TestCase
{
    /** @return list<array{string,string,int,int}> */
    private function tokens(string $source): array
    {
        return array_map(
            static fn (Token $token): array => [$token->kind->name, $token->value, $token->at->line, $token->at->column],
            Lexer::tokens($source),
        );
    }

    public function test_an_empty_text_is_only_the_end(): void
    {
        $this->assertSame([['End', '', 1, 1]], $this->tokens(''));
    }

    public function test_it_cuts_every_kind_of_token_and_counts_columns_in_characters(): void
    {
        $this->assertSame([
            ['Word', 'Цена', 1, 1],
            ['Symbol', '*', 1, 6],
            ['Number', '2.50', 1, 8],
            ['Symbol', '&', 1, 12],
            ['Text', 'шт', 1, 13],
            ['Symbol', '+', 1, 17],
            ['Name', 'Дата сдачи', 1, 18],
            ['Date', '28.11.2026', 1, 31],
            ['Date', '#2026-11-28#', 1, 42],
            ['End', '', 1, 54],
        ], $this->tokens('Цена * 2.50&"шт"+[Дата сдачи] 28.11.2026 #2026-11-28#'));
    }

    /** @return iterable<string,array{string}> */
    public static function symbols(): iterable
    {
        foreach (['<>', '<=', '>=', '-', '+', '*', '/', '^', '&', '=', '<', '>', '(', ')', ';', ',', '.', '%'] as $symbol) {
            yield $symbol => [$symbol];
        }
    }

    #[DataProvider('symbols')]
    public function test_every_symbol_is_one_token(string $symbol): void
    {
        $this->assertSame(['Symbol', $symbol, 1, 1], $this->tokens($symbol)[0]);
        $this->assertCount(2, $this->tokens($symbol));
    }

    public function test_lines_and_columns_go_on_after_line_breaks(): void
    {
        $this->assertSame([
            ['Word', 'a', 1, 1],
            ['Symbol', '+', 2, 3],
            ['Text', "x\ny", 2, 5],
            ['Word', 'b', 3, 4],
            ['Number', '1', 6, 2],
            ['End', '', 6, 3],
        ], $this->tokens("a\n  + \"x\ny\" b\n\n\n 1"));
    }

    public function test_the_column_after_a_text_over_two_lines_counts_characters(): void
    {
        $this->assertSame(['Word', 'z', 2, 5], $this->tokens("\"a\nбв\" z")[1]);
    }

    public function test_quotes_and_brackets_double_to_mean_themselves(): void
    {
        $this->assertSame(['Text', 'say "hi"', 1, 1], $this->tokens('"say ""hi"""')[0]);
        $this->assertSame(['Text', '', 1, 1], $this->tokens('""')[0]);
        $this->assertSame(['Name', 'a [b] c', 1, 1], $this->tokens('[a [b]] c]')[0]);
    }

    public function test_a_bracketed_name_is_tidied(): void
    {
        $this->assertSame(['Name', 'Дата сдачи', 1, 1], $this->tokens("[  Дата \n\t сдачи ]")[0]);
    }

    /** @return iterable<string,array{string,list<array{string,string}>}> */
    public static function neighbours(): iterable
    {
        yield 'letters, digits, underscore and marks make one word' => ["_a1\u{0301}b", [['Word', "_a1\u{0301}b"]]];
        yield 'a number then a word' => ['2x', [['Number', '2'], ['Word', 'x']]];
        yield 'a number with a dot and no digits' => ['2.', [['Number', '2'], ['Symbol', '.']]];
        yield 'a short date' => ['1.2.2026', [['Date', '1.2.2026']]];
        yield 'not a date: a short year' => ['1.2.26', [['Number', '1.2'], ['Symbol', '.'], ['Number', '26']]];
        yield 'not a date: three digits of a day' => ['111.2.2026', [['Number', '111.2'], ['Symbol', '.'], ['Number', '2026']]];
    }

    /** @param  list<array{string,string}>  $expected */
    #[DataProvider('neighbours')]
    public function test_it_tells_neighbours_apart(string $source, array $expected): void
    {
        $this->assertSame($expected, array_map(
            static fn (array $token): array => [$token[0], $token[1]],
            array_slice($this->tokens($source), 0, -1),
        ));
    }

    /** @return iterable<string,array{string,string,string}> */
    public static function unreadable(): iterable
    {
        yield 'a text left open' => ['1 + "abc', 'syntax.unclosed_string', 'Line 1, column 5: The text opened with a quote is not closed.'];
        yield 'a text ending in a doubled quote' => ['"abc""', 'syntax.unclosed_string', 'Line 1, column 1: The text opened with a quote is not closed.'];
        yield 'a name left open' => ["1 +\n[abc", 'syntax.unclosed_name', 'Line 2, column 1: The name opened with a square bracket is not closed.'];
        yield 'nothing in brackets' => ['a & []', 'syntax.empty_name', 'Line 1, column 5: There is no name between the square brackets.'];
        yield 'only spaces in brackets' => ['[   ]', 'syntax.empty_name', 'Line 1, column 1: There is no name between the square brackets.'];
        yield 'an unknown character' => ['Цена № 1', 'syntax.unknown_character', 'Line 1, column 6: Unknown character “№”.'];
        yield 'a closing bracket alone' => [']', 'syntax.unknown_character', 'Line 1, column 1: Unknown character “]”.'];
        yield 'a date sign alone' => ['#', 'syntax.unknown_character', 'Line 1, column 1: Unknown character “#”.'];
    }

    /** @return iterable<string,array{string,int,string}> */
    public static function foreign(): iterable
    {
        yield 'an Arabic digit' => ['١', 1, '١'];
        yield 'one after a sign' => ['1 + ٢', 5, '٢'];
        yield 'a wide digit' => ['１', 1, '１'];
        yield 'a digit after the point' => ['1.٥', 3, '٥'];
        yield 'a mathematical digit' => ['𝟏', 1, '𝟏'];
        yield 'a date in wide digits' => ['#２０２６-01-01#', 1, '#'];
        yield 'a date with a foreign month' => ['1.١.2026', 3, '١'];
    }

    #[DataProvider('foreign')]
    public function test_only_the_ten_digits_make_numbers_and_dates(string $source, int $column, string $character): void
    {
        $this->assertStops(
            'syntax.unknown_character',
            "Line 1, column {$column}: Unknown character “{$character}”.",
            fn () => Lexer::tokens($source),
        );
    }

    #[DataProvider('unreadable')]
    public function test_it_says_where_it_cannot_read(string $source, string $code, string $message): void
    {
        $this->assertStops($code, $message, fn () => Lexer::tokens($source));
    }
}
