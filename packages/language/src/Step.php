<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

use Atrium\Language\Ir\Node;

/** One part of a formula that was worked out: its value and the parts it was made of. */
final readonly class Step
{
    /** @param  list<Step>  $steps  in the order they were worked out; a branch not taken is not here */
    public function __construct(
        public Node $node,
        public mixed $value,
        public array $steps,
    ) {}
}
