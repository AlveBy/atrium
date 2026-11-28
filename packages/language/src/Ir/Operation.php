<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\Position;
use InvalidArgumentException;

/** An operator and what it stands between. `+`, `*`, `&`, AND and OR take any number of operands. */
final readonly class Operation extends Node
{
    /** @param  list<Node>  $operands */
    public function __construct(public Operator $operator, public array $operands, ?Position $at = null)
    {
        parent::__construct($at);

        if (! $operator->takes(count($operands))) {
            throw new InvalidArgumentException("The operator [{$operator->value}] cannot take ".count($operands).' operands.');
        }
    }

    public function accept(Visitor $visitor): mixed
    {
        return $visitor->operation($this);
    }

    public function children(): array
    {
        return $this->operands;
    }

    public function data(): array
    {
        return [
            'node' => 'operation',
            'operator' => $this->operator->value,
            'operands' => array_map(static fn (Node $operand): array => $operand->toArray(), $this->operands),
        ];
    }
}
