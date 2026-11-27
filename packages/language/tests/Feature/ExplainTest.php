<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Tests\Feature;

use Atrium\Language\Formula;
use Atrium\Language\Step;
use Atrium\Language\Tests\TestCase;

final class ExplainTest extends TestCase
{
    /** @return array{string,mixed,list<mixed>} the step as plain data */
    private function steps(Step $step): array
    {
        return [self::tree($step->node), self::plain($step->value), array_map($this->steps(...), $step->steps)];
    }

    public function test_it_shows_what_every_part_of_the_result_was(): void
    {
        $formula = Formula::parse('Amount * 2 + 1');
        $result = $formula->explain($this->context(['amount' => 3]));

        $this->assertSame(self::num(7), self::plain($result->value));
        $this->assertSame(['amount'], array_keys($result->fields));
        $this->assertSame($formula->root, $result->trace->node);
        $this->assertSame([
            '(add (multiply [Amount] 2) 1)', self::num(7), [
                ['(multiply [Amount] 2)', self::num(6), [
                    ['[Amount]', self::num(3), []],
                    ['2', self::num(2), []],
                ]],
                ['1', self::num(1), []],
            ],
        ], $this->steps($result->trace));
    }

    public function test_a_part_that_was_not_worked_out_is_not_shown(): void
    {
        $result = Formula::parse('IF(Paid AND Amount > 1; "a"; "b")')->explain($this->context(['paid' => false]));

        $this->assertSame([
            "(if (and [Paid] (greater [Amount] 1)) 'a' 'b')", 'b', [
                ['(and [Paid] (greater [Amount] 1))', false, [['[Paid]', false, []]]],
                ["'b'", 'b', []],
            ],
        ], $this->steps($result->trace));
    }

    public function test_the_arguments_of_a_function_are_shown(): void
    {
        $result = Formula::parse('SUM(1; Lines.Price)')->explain($this->context(['lines' => [['price' => 2]]]));

        $this->assertSame([
            '(call:SUM 1 (. [Lines] Price))', self::num(3), [
                ['1', self::num(1), []],
                ['(. [Lines] Price)', [self::num(2)], [
                    ['[Lines]', [['record', ['price' => self::num(2), 'qty' => null, 'title' => null]]], []],
                ]],
            ],
        ], $this->steps($result->trace));
    }

    public function test_evaluating_keeps_no_steps(): void
    {
        $this->assertNull(Formula::parse('1 + 1')->evaluate($this->context())->trace);
    }
}
