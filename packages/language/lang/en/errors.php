<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'at' => 'Line :line, column :column: :message',
    'types' => [
        'number' => 'number',
        'text' => 'text',
        'boolean' => 'yes/no',
        'date' => 'date',
        'empty' => 'nothing',
        'any' => 'any value',
        'list' => 'list of :of',
        'record' => 'record',
        'or' => ' or ',
    ],
    'syntax' => [
        'encoding' => 'The formula is not valid UTF-8 text.',
        'too_long' => 'The formula is longer than :limit characters.',
        'too_deep' => 'Brackets and functions are nested deeper than :limit levels.',
        'unknown_character' => 'Unknown character “:character”.',
        'unclosed_string' => 'The text opened with a quote is not closed.',
        'unclosed_name' => 'The name opened with a square bracket is not closed.',
        'empty_name' => 'There is no name between the square brackets.',
        'bad_date' => 'There is no such date: :date.',
        'unexpected' => '“:token” was not expected here.',
        'unexpected_end' => 'The formula ends too early.',
        'operator_case' => '“:word” was not expected here. Operators are written in capital letters: :operator.',
        'chained_comparison' => 'Only two values can be compared at once. Join comparisons with AND or OR.',
    ],
    'arguments' => [
        'too_few' => 'The function :function needs more arguments: at least :min, given: :given.',
        'too_many' => 'The function :function takes fewer arguments: at most :max, given: :given.',
    ],
    'formula' => [
        'too_big' => 'The formula has more than :limit parts.',
        'too_deep' => 'The formula is nested deeper than :limit levels.',
        'too_long' => 'The stored formula is longer than :limit bytes.',
    ],
    'ir' => [
        'invalid' => 'The stored formula is damaged at “:path”.',
        'version' => 'The formula is stored in a version of the format that cannot be read here; the version read is :supported.',
    ],
    'check' => [
        'unknown_field' => 'There is no field “:name”.',
        'unknown_variable' => 'There is no item “:name” here: no loop around this place has that name.',
        'unknown_member' => 'The related record has no field “:name”.',
        'not_a_record' => '“.:name” needs a related record before the dot, found: :actual.',
        'unknown_function' => 'There is no function “:name”.',
        'permission' => 'The function :function needs the permission “:permission”.',
        'operand' => 'The operator “:operator” needs: :expected, found: :actual.',
        'argument' => 'Argument :argument of the function :function needs: :expected, found: :actual.',
        'compare' => '“:operator” cannot compare these: :left and :right.',
        'condition' => 'A condition must be yes or no, found: :actual.',
        'branches' => 'Both results of a condition must be of one type, found: :then and :otherwise.',
        'not_a_list' => 'A loop needs a list, found: :actual.',
        'depth' => 'The formula is nested deeper than the allowed :limit levels.',
        'deep_list' => 'Lists are inside one another deeper than :limit levels.',
    ],
    'value' => [
        'empty' => 'A value is empty here, but the formula needs: :expected.',
        'empty_field' => 'The field “:field” is empty, but the formula needs: :expected.',
        'wrong_type' => 'The formula needs: :expected, found: :actual.',
        'bad_field' => 'The field “:field” holds a value that does not fit its type: :expected.',
        'bad_return' => 'The function :function returned a value that does not fit its type: :expected.',
        'compare' => 'These cannot be compared: :left and :right.',
        'too_large' => 'A number is too large: it has more than :digits digits before the decimal point.',
        'division_by_zero' => 'Division by zero.',
        'exponent' => 'The power must be a whole number between -:limit and :limit.',
        'whole_days' => 'Only whole days can be added to a date or taken from it.',
        'out_of_range' => 'Argument :argument of the function :function must be a whole number from :min to :max.',
        'negative_root' => 'There is no square root of a negative number.',
        'date_out_of_range' => 'The date is outside the years 1 to 9999.',
        'no_values' => 'The function :function has no values to work with.',
        'search_too_long' => 'The function :function can search for at most :limit characters.',
    ],
    'limit' => [
        'steps' => 'The formula was stopped: it took more than :limit steps.',
        'time' => 'The formula was stopped: it ran longer than :limit ms.',
        'memory' => 'The formula was stopped: its values took more than :limit bytes.',
        'recursion' => 'The formula was stopped: more than :limit formulas were running inside one another.',
    ],
];
