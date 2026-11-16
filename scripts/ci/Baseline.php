<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Ci;

/**
 * The coverage each target may not lose, one file per target in .ci/coverage
 * so that two pushes raising different packages never conflict. A file reads
 * "87.50% of lines, 120 not covered".
 *
 * What may not grow is the number of lines no test runs: the percentage also
 * falls when covered code is deleted, which loses nothing. bin/ci writes the
 * file again when the tests cover more, and the commit carries it.
 */
final class Baseline
{
    private const FORMAT = '%f%% of lines, %d not covered';

    public function __construct(private readonly string $directory) {}

    /**
     * @return array{percent: float, uncovered: int}|null
     */
    public function get(string $target): ?array
    {
        $file = $this->file($target);

        if (! is_file($file)) {
            return null;
        }

        $values = sscanf(trim((string) file_get_contents($file)), self::FORMAT);

        if (! is_array($values) || ! is_float($values[0]) || ! is_int($values[1])) {
            throw new CiException("{$file} does not read \"<percent>% of lines, <count> not covered\"");
        }

        return ['percent' => $values[0], 'uncovered' => $values[1]];
    }

    public function set(string $target, float $percent, int $uncovered): void
    {
        if (! is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }

        file_put_contents($this->file($target), number_format($percent, 2, '.', '')."% of lines, {$uncovered} not covered\n");
    }

    /**
     * Compares the measured coverage with the stored one: it fell when more
     * lines are left without a test, and rose when fewer are or a larger share
     * is covered.
     *
     * @return 'fell'|'rose'|'same'|'new'
     */
    public function compare(string $target, float $percent, int $uncovered): string
    {
        $stored = $this->get($target);

        return match (true) {
            $stored === null => 'new',
            $uncovered > $stored['uncovered'] => 'fell',
            $uncovered < $stored['uncovered'], $percent > $stored['percent'] => 'rose',
            default => 'same',
        };
    }

    private function file(string $target): string
    {
        return $this->directory.'/'.$target;
    }
}
