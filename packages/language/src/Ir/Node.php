<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\Position;

/**
 * A node of the intermediate representation: the one tree that formulas,
 * rules of blocks and plugins are turned into, and that the checker, the
 * interpreter and, later, the compilers read.
 */
abstract readonly class Node
{
    /** @param  Position|null  $at  where it was written, when it came from a text */
    public function __construct(public ?Position $at) {}

    /**
     * @template T
     *
     * @param  Visitor<T>  $visitor
     * @return T
     */
    abstract public function accept(Visitor $visitor): mixed;

    /** @return list<Node> */
    abstract public function children(): array;

    /**
     * The node as the data it is stored as, without its position.
     *
     * @return array<string,mixed>
     */
    abstract public function data(): array;

    /** @return array<string,mixed> the node and all below it, as plain data */
    public function toArray(): array
    {
        $data = $this->data();

        if ($this->at !== null) {
            $data['at'] = [$this->at->line, $this->at->column];
        }

        return $data;
    }
}
