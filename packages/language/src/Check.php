<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

use Atrium\Language\Functions\FormulaFunction;
use Atrium\Language\Types\Type;

/** What the check of a formula found. */
final readonly class Check
{
    /**
     * @param  list<Problem>  $problems
     * @param  Type  $type  the type of the result, meaningful when there are no problems
     * @param  array<string,FormulaFunction>  $functions  those the formula calls, by the name it calls them
     */
    public function __construct(
        public array $problems,
        public Type $type,
        public array $functions,
    ) {}

    public function passes(): bool
    {
        return $this->problems === [];
    }

    /** @return list<string> */
    public function messages(?string $locale = null): array
    {
        return array_map(static fn (Problem $problem): string => $problem->message($locale), $this->problems);
    }
}
