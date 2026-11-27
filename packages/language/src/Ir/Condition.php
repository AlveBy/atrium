<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\Position;

/** If, then, otherwise: only the branch taken is evaluated. Without `otherwise` the result is empty. */
final readonly class Condition extends Node
{
    public function __construct(public Node $when, public Node $then, public ?Node $otherwise = null, ?Position $at = null)
    {
        parent::__construct($at);
    }

    public function accept(Visitor $visitor): mixed
    {
        return $visitor->condition($this);
    }

    public function children(): array
    {
        $children = [$this->when, $this->then];

        if ($this->otherwise !== null) {
            $children[] = $this->otherwise;
        }

        return $children;
    }

    public function data(): array
    {
        $data = ['node' => 'condition', 'when' => $this->when->toArray(), 'then' => $this->then->toArray()];

        if ($this->otherwise !== null) {
            $data['otherwise'] = $this->otherwise->toArray();
        }

        return $data;
    }
}
