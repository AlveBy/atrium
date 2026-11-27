<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\Position;

/** A field of a related record; of a list of records, the list of that field. */
final readonly class Member extends Node
{
    public function __construct(public Node $of, public string $name, ?Position $at = null)
    {
        parent::__construct($at);
    }

    public function accept(Visitor $visitor): mixed
    {
        return $visitor->member($this);
    }

    public function children(): array
    {
        return [$this->of];
    }

    public function data(): array
    {
        return ['node' => 'member', 'of' => $this->of->toArray(), 'name' => $this->name];
    }
}
