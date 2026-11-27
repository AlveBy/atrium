<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\Position;

/** A call of a function of the registry, by any of its names. */
final readonly class Call extends Node
{
    /** @param  list<Node>  $arguments */
    public function __construct(public string $function, public array $arguments = [], ?Position $at = null)
    {
        parent::__construct($at);
    }

    public function accept(Visitor $visitor): mixed
    {
        return $visitor->call($this);
    }

    public function children(): array
    {
        return $this->arguments;
    }

    public function data(): array
    {
        return [
            'node' => 'call',
            'function' => $this->function,
            'arguments' => array_map(static fn (Node $argument): array => $argument->toArray(), $this->arguments),
        ];
    }
}
