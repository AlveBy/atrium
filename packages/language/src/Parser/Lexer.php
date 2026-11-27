<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Parser;

use Atrium\Language\FormulaError;
use Atrium\Language\Names;
use Atrium\Language\Position;

/** Cuts the text of a formula into tokens, each with its line and column. */
final class Lexer
{
    private const PATTERN = '~\G(?:'
        .'\s++(*MARK:space)'
        .'|(?:#[0-9]{4}-[0-9]{2}-[0-9]{2}#|[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{4})(*MARK:Date)'
        .'|[0-9]++(?:\.[0-9]++)?(*MARK:Number)'
        .'|"(?:[^"]++|"")*+"(*MARK:Text)'
        .'|\[(?:[^\]]++|\]\])*+\](*MARK:Name)'
        .'|[\p{L}_][\p{L}\p{M}\p{N}_]*+(*MARK:Word)'
        .'|(?:<>|<=|>=|[-+*/^&=<>();,.%])(*MARK:Symbol)'
        .')~u';

    /**
     * @param  string  $source  valid UTF-8
     * @return non-empty-list<Token> the last one marks the end
     */
    public static function tokens(string $source): array
    {
        $tokens = [];
        $offset = 0;
        $line = 1;
        $column = 1;

        while ($offset < strlen($source)) {
            $at = new Position($line, $column);

            if (preg_match(self::PATTERN, $source, $found, 0, $offset) !== 1) {
                throw self::unreadable(mb_substr(substr($source, $offset), 0, 1), $at);
            }

            $lexeme = $found[0];
            $kind = $found['MARK'];

            if ($kind !== 'space') {
                $tokens[] = self::token($kind, $lexeme, $at);
            }

            $offset += strlen($lexeme);
            $break = strrpos($lexeme, "\n");

            if ($break === false) {
                $column += mb_strlen($lexeme);
            } else {
                $line += substr_count($lexeme, "\n");
                $column = mb_strlen(substr($lexeme, $break + 1)) + 1;
            }
        }

        $tokens[] = new Token(TokenKind::End, '', new Position($line, $column));

        return $tokens;
    }

    private static function token(string $kind, string $lexeme, Position $at): Token
    {
        return match ($kind) {
            'Date' => new Token(TokenKind::Date, $lexeme, $at),
            'Number' => new Token(TokenKind::Number, $lexeme, $at),
            'Text' => new Token(TokenKind::Text, str_replace('""', '"', substr($lexeme, 1, -1)), $at),
            'Name' => self::name($lexeme, $at),
            'Word' => new Token(TokenKind::Word, $lexeme, $at),
            default => new Token(TokenKind::Symbol, $lexeme, $at),
        };
    }

    private static function name(string $lexeme, Position $at): Token
    {
        $name = Names::tidy(str_replace(']]', ']', substr($lexeme, 1, -1)));

        if ($name === '') {
            throw FormulaError::of('syntax.empty_name', $at);
        }

        return new Token(TokenKind::Name, $name, $at);
    }

    private static function unreadable(string $character, Position $at): FormulaError
    {
        return match ($character) {
            '"' => FormulaError::of('syntax.unclosed_string', $at),
            '[' => FormulaError::of('syntax.unclosed_name', $at),
            default => FormulaError::of('syntax.unknown_character', $at, ['character' => $character]),
        };
    }
}
