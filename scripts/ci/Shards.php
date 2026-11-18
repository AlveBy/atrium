<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Ci;

/**
 * Splits the files to mutate between the machines of a CI matrix, so each
 * mutates about the same number of lines. Every machine computes the split
 * itself from the same diff, so the split must not depend on the order the
 * files come in.
 */
final class Shards
{
    /**
     * @param  array<string, int>  $weights  file => lines to mutate in it
     * @return list<list<string>> the files of each shard, sorted
     */
    public static function split(array $weights, int $count): array
    {
        $shards = array_fill(0, $count, []);
        $loads = array_fill(0, $count, 0);
        $files = array_keys($weights);

        // The heaviest file first, each to the lightest shard so far.
        usort($files, static fn (string $a, string $b): int => [$weights[$b], $a] <=> [$weights[$a], $b]);

        foreach ($files as $file) {
            $lightest = array_keys($loads, min($loads), true)[0];
            $shards[$lightest][] = $file;
            $loads[$lightest] += $weights[$file];
        }

        foreach ($shards as &$shard) {
            sort($shard);
        }

        return $shards;
    }

    /**
     * "2/4" → [2, 4]: the second shard of four.
     *
     * @return array{int, int}
     */
    public static function parse(string $value): array
    {
        if (preg_match('#^([1-9]\d*)/([1-9]\d*)$#D', $value, $match) !== 1 || (int) $match[1] > (int) $match[2]) {
            throw new CiException("--shard takes I/N with 1 ≤ I ≤ N, got {$value}");
        }

        return [(int) $match[1], (int) $match[2]];
    }

    /**
     * The targets one shard measures the coverage of: the ones it mutates,
     * and every N-th of the rest, so the shards share the coverage gate.
     *
     * @param  list<string>  $targets
     * @param  list<string>  $mutated  the targets whose files this shard mutates
     * @return list<string>
     */
    public static function coverageTargets(array $targets, array $mutated, int $index, int $count): array
    {
        return array_values(array_filter(
            $targets,
            static fn (string $target, int $position): bool => in_array($target, $mutated, true) || $position % $count === $index - 1,
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /**
     * Infection's "source" for one target. Infection refuses an empty
     * "excludes", so there is none when this shard mutates every file.
     *
     * @param  list<string>  $excluded  files another shard mutates
     * @return array{directories: list<string>, excludes?: list<string>}
     */
    public static function source(string $root, string $source, array $excluded): array
    {
        $config = ['directories' => ["{$root}/{$source}"]];

        if ($excluded !== []) {
            $config['excludes'] = self::excludes($source, $excluded);
        }

        return $config;
    }

    /**
     * Infection's "excludes" for files another shard mutates: paths relative
     * to the source folder, matched whole.
     *
     * @param  list<string>  $files  paths relative to the repository root
     * @return list<string>
     */
    public static function excludes(string $source, array $files): array
    {
        return array_map(static fn (string $file): string => '#^'.preg_quote(substr($file, strlen($source) + 1), '#').'$#D', $files);
    }
}
