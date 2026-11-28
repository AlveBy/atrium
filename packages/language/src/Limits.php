<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

/** What one run of a formula may use. Runs started from inside it share the same. */
final readonly class Limits
{
    /**
     * @param  int  $steps  nodes worked out and list items passed over
     * @param  int  $milliseconds  of wall-clock time, looked at before every step
     * @param  int  $memory  the bytes of the values made, added up over the run
     * @param  int  $depth  how deep the tree may be
     * @param  int  $recursion  how many runs may be open inside each other
     */
    public function __construct(
        public int $steps = 200000,
        public int $milliseconds = 1000,
        public int $memory = 2097152,
        public int $depth = 64,
        public int $recursion = 8,
    ) {}
}
