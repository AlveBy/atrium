<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Functions;

use Atrium\Language\FormulaError;
use Atrium\Language\Interpreter\Meter;
use Atrium\Language\Position;
use Atrium\Language\Values\Date;
use Atrium\Language\Values\Numbers;

/**
 * One call of a function, as its handler sees it. The arguments are already
 * checked against the parameters: numbers are BigDecimal, dates are Date,
 * lists are arrays, and nothing is empty unless its parameter allows it.
 */
final readonly class Invocation
{
    /** @param  list<mixed>  $arguments */
    public function __construct(
        private string $function,
        public array $arguments,
        public Date $today,
        private Meter $meter,
        private ?Position $at,
    ) {}

    /** The argument at this position (from 0), or null when it is empty or left out. */
    public function argument(int $position): mixed
    {
        return $this->arguments[$position] ?? null;
    }

    /** A text argument; an empty one is an empty text. */
    public function text(int $position): string
    {
        return $this->arguments[$position] ?? '';
    }

    /** A whole number within a range, or the default when the argument is left out. */
    public function whole(int $position, int $min, int $max, int $default = 0): int
    {
        if (! array_key_exists($position, $this->arguments)) {
            return $default;
        }

        return Numbers::whole($this->arguments[$position], $min, $max) ?? $this->fail('value.out_of_range', [
            'argument' => $position + 1,
            'min' => $min,
            'max' => $max,
        ]);
    }

    /**
     * The values of every argument, lists opened, empty ones skipped: what a
     * total is made of. Each counts as a step.
     *
     * @return list<mixed>
     */
    public function values(): array
    {
        $values = [];

        foreach ($this->arguments as $argument) {
            foreach (is_array($argument) ? $argument : [$argument] as $value) {
                $this->step();

                if ($value !== null) {
                    $values[] = $value;
                }
            }
        }

        return $values;
    }

    /** Counts one turn of a loop of the function against the run's limits. */
    public function step(): void
    {
        $this->meter->step($this->at);
    }

    /** Stops the run before a result of this many bytes is made, if it would not fit. */
    public function mustFit(int $bytes): void
    {
        $this->meter->mustFit($bytes, $this->at);
    }

    /**
     * Stops the run with a problem of the language, placed at this call.
     *
     * @param  string  $code  a key of the `errors` translations; :function is filled in
     * @param  array<string,string|int>  $params
     */
    public function fail(string $code, array $params = []): never
    {
        throw FormulaError::of($code, $this->at, ['function' => $this->function, ...$params]);
    }
}
