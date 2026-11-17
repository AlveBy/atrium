<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Ci;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Finds the annotations that would take code out of the coverage or the
 * mutation gate. Every line is tested; there is no way around it.
 */
final class EscapeHatches
{
    private const PATTERN = '/@codeCoverageIgnore|@infection-ignore-all|@infection-ignore/';

    /**
     * @return list<string> "path:line" of every annotation in packages/<name>/src and app
     */
    public static function find(string $root): array
    {
        $found = [];

        foreach ([...glob($root.'/packages/*/src', GLOB_ONLYDIR) ?: [], $root.'/app'] as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            /** @var SplFileInfo $file */
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                foreach (file($file->getPathname()) ?: [] as $number => $line) {
                    if (preg_match(self::PATTERN, $line) === 1) {
                        $found[] = substr($file->getPathname(), strlen($root) + 1).':'.($number + 1);
                    }
                }
            }
        }

        sort($found);

        return $found;
    }
}
