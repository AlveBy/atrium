<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\Position;

/** The item a loop around this node is at. */
final readonly class Variable extends Node
{
    public function __construct(public string $name, ?Position $at = null)
    {
        parent::__construct($at);
    }

    public function accept(Visitor $visitor): mixed
    {
        return $visitor->variable($this);
    }

    public function children(): array
    {
        return [];
    }

    public function data(): array
    {
        return ['node' => 'variable', 'name' => $this->name];
    }
}
