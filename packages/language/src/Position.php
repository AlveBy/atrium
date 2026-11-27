<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

/** Where something stands in the source text; both count from 1, columns in characters. */
final readonly class Position
{
    public function __construct(
        public int $line,
        public int $column,
    ) {}
}
