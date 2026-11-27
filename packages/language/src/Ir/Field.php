<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\Position;

/** The value of a field of the record the formula is about, by any of its names. */
final readonly class Field extends Node
{
    public function __construct(public string $name, ?Position $at = null)
    {
        parent::__construct($at);
    }

    public function accept(Visitor $visitor): mixed
    {
        return $visitor->field($this);
    }

    public function children(): array
    {
        return [];
    }

    public function data(): array
    {
        return ['node' => 'field', 'name' => $this->name];
    }
}
