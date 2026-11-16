<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Ci;

/**
 * The lines a change adds or rewrites, read from `git diff --unified=0`.
 */
final class Diff
{
    /**
     * @return array<string, list<int>> file => the line numbers in the new version
     */
    public static function addedLines(string $diff): array
    {
        $lines = [];
        $file = null;

        foreach (preg_split('/\R/', $diff) ?: [] as $line) {
            if (str_starts_with($line, '+++ ')) {
                $path = substr($line, 4);
                $file = $path === '/dev/null' ? null : (string) preg_replace('#^b/#', '', $path);

                continue;
            }

            if ($file !== null && preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $line, $match) === 1) {
                $start = (int) $match[1];
                $count = isset($match[2]) ? (int) $match[2] : 1;

                for ($number = $start; $number < $start + $count; $number++) {
                    $lines[$file][] = $number;
                }
            }
        }

        return $lines;
    }
}
