<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Interpreter;

use Atrium\Language\FormulaError;
use Atrium\Language\Limits;
use Atrium\Language\Position;
use Atrium\Language\Values\Record;
use Closure;

/**
 * Counts what a run uses and stops it at a limit. A run started while another
 * is open, from inside a function, spends the same limits: a formula cannot
 * get fresh ones by calling formulas.
 */
final class Meter
{
    /** What one value costs besides the bytes of its text. */
    public const VALUE_BYTES = 16;

    private static ?self $running = null;

    private int $steps = 0;

    private int $memory = 0;

    private int $open = 0;

    private readonly int $deadline;

    /** @param  Closure(): int  $clock */
    private function __construct(
        private readonly Limits $limits,
        private readonly Closure $clock,
    ) {
        $this->deadline = $clock() + $limits->milliseconds * 1000000;
    }

    /**
     * @template T
     *
     * @param  Closure(): int  $clock
     * @param  Closure(Meter): T  $work
     * @return T
     */
    public static function run(Limits $limits, Closure $clock, Closure $work): mixed
    {
        $outer = self::$running;
        $meter = $outer ?? new self($limits, $clock);

        if ($meter->open >= $meter->limits->recursion) {
            throw FormulaError::of('limit.recursion', null, ['limit' => $meter->limits->recursion]);
        }

        $meter->open++;
        self::$running = $meter;

        try {
            return $work($meter);
        } finally {
            $meter->open--;
            self::$running = $outer;
        }
    }

    public function step(?Position $at): void
    {
        if (++$this->steps > $this->limits->steps) {
            throw FormulaError::of('limit.steps', $at, ['limit' => $this->limits->steps]);
        }

        if (($this->clock)() > $this->deadline) {
            throw FormulaError::of('limit.time', $at, ['limit' => $this->limits->milliseconds]);
        }
    }

    /** Stops before a value too big to make is made. */
    public function mustFit(int $bytes, ?Position $at): void
    {
        if ($this->memory + $bytes > $this->limits->memory) {
            throw FormulaError::of('limit.memory', $at, ['limit' => $this->limits->memory]);
        }
    }

    /** Counts a value that was made. */
    public function charge(mixed $value, ?Position $at): void
    {
        $bytes = self::VALUE_BYTES + (is_string($value) ? strlen($value) : 0);
        $this->mustFit($bytes, $at);
        $this->memory += $bytes;
    }

    /**
     * Counts a value kept in a list or in the trace, with all that is in it:
     * one list put in many places is paid for in each of them.
     */
    public function keep(mixed $value, ?Position $at): void
    {
        $this->charge($value, $at);

        $inside = match (true) {
            is_array($value) => $value,
            $value instanceof Record => $value->values,
            default => [],
        };

        foreach ($inside as $item) {
            $this->keep($item, $at);
        }
    }
}
