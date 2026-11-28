<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Functions;

use Atrium\Language\Types\Type;

/** One parameter of a function. Each method returns a changed copy. */
final readonly class Parameter
{
    private function __construct(
        public Type $type,
        public bool $optional = false,
        public bool $variadic = false,
        public bool $orEmpty = false,
    ) {}

    public static function of(Type $type): self
    {
        return new self($type);
    }

    /** May be left out; so must be every parameter after it. */
    public function optional(): self
    {
        return new self($this->type, true, $this->variadic, $this->orEmpty);
    }

    /** Takes any number of arguments; only the last parameter may. */
    public function variadic(): self
    {
        return new self($this->type, $this->optional, true, $this->orEmpty);
    }

    /** Accepts an empty value; otherwise the run stops, naming what is empty. */
    public function orEmpty(): self
    {
        return new self($this->type, $this->optional, $this->variadic, true);
    }
}
