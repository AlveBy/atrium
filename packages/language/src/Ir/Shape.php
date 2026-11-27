<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\FormulaError;

/**
 * The bounds every tree keeps, wherever it came from, so that the code that
 * walks it can recurse without fear.
 */
final class Shape
{
    public const DEPTH = 200;

    public const NODES = 5000;

    public static function ensure(Node $root): void
    {
        $waiting = [[$root, 1]];
        $count = 0;

        while ($waiting !== []) {
            [$node, $depth] = array_pop($waiting);

            if (++$count > self::NODES) {
                throw FormulaError::of('formula.too_big', null, ['limit' => self::NODES]);
            }

            if ($depth > self::DEPTH) {
                throw FormulaError::of('formula.too_deep', $node->at, ['limit' => self::DEPTH]);
            }

            foreach ($node->children() as $child) {
                $waiting[] = [$child, $depth + 1];
            }
        }
    }
}
