<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Parser;

use Atrium\Language\Position;

final readonly class Token
{
    /**
     * @param  string  $value  what the token stands for: a text without its quotes, a name without its brackets
     */
    public function __construct(
        public TokenKind $kind,
        public string $value,
        public Position $at,
    ) {}

    public function is(string ...$symbols): bool
    {
        return $this->kind === TokenKind::Symbol && in_array($this->value, $symbols, true);
    }
}
