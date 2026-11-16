<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Ci;

use RuntimeException;
use SimpleXMLElement;

/**
 * A Clover coverage report of PHPUnit: which executable lines of which file
 * the tests ran.
 */
final class Clover
{
    /**
     * @param  array<string, array<int, int>>  $files  path relative to the root => line => hits
     */
    public function __construct(public readonly array $files) {}

    public static function fromXml(string $xml, string $root): self
    {
        $document = @simplexml_load_string($xml);

        if (! $document instanceof SimpleXMLElement) {
            throw new RuntimeException('Not a Clover report.');
        }

        $files = [];
        $prefix = rtrim($root, '/').'/';

        foreach ($document->xpath('//file') ?: [] as $file) {
            $path = (string) $file['name'];
            $path = str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : $path;
            $files[$path] ??= [];

            foreach ($file->line as $line) {
                if ((string) $line['type'] === 'stmt') {
                    $files[$path][(int) $line['num']] = (int) $line['count'];
                }
            }
        }

        return new self($files);
    }

    /**
     * Covered and executable lines of every file, and their percentage.
     *
     * @return array{covered: int, total: int, percent: float}
     */
    public function totals(): array
    {
        $covered = 0;
        $total = 0;

        foreach ($this->files as $lines) {
            $total += count($lines);
            $covered += count(array_filter($lines, static fn (int $hits): bool => $hits > 0));
        }

        return ['covered' => $covered, 'total' => $total, 'percent' => self::percent($covered, $total)];
    }

    /**
     * The changed lines that are executable and that no test ran.
     *
     * @param  array<string, list<int>>  $changed  file => line numbers
     * @return array<string, list<int>>
     */
    public function uncovered(array $changed): array
    {
        $missed = [];

        foreach ($changed as $file => $numbers) {
            foreach ($numbers as $number) {
                if (isset($this->files[$file][$number]) && $this->files[$file][$number] === 0) {
                    $missed[$file][] = $number;
                }
            }
        }

        return $missed;
    }

    /**
     * How many of the changed lines are executable.
     *
     * @param  array<string, list<int>>  $changed
     */
    public function executable(array $changed): int
    {
        $count = 0;

        foreach ($changed as $file => $numbers) {
            foreach ($numbers as $number) {
                $count += isset($this->files[$file][$number]) ? 1 : 0;
            }
        }

        return $count;
    }

    /**
     * Two decimals, rounded down so a ratchet never rises by rounding: 100 only
     * when every line is covered, and 100 for a target with nothing to cover.
     */
    public static function percent(int $covered, int $total): float
    {
        return $total === 0 ? 100.0 : floor($covered * 10000 / $total) / 100;
    }
}
