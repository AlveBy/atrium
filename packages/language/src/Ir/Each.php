<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\Position;

/**
 * The only loop: over the items of a list, so it always ends. Each item that
 * passes `where` is named `variable`, and `body` is worked out for it; the
 * results make a new list.
 */
final readonly class Each extends Node
{
    public function __construct(
        public Node $source,
        public string $variable,
        public Node $body,
        public ?Node $where = null,
        ?Position $at = null,
    ) {
        parent::__construct($at);
    }

    public function accept(Visitor $visitor): mixed
    {
        return $visitor->each($this);
    }

    public function children(): array
    {
        $children = [$this->source, $this->body];

        if ($this->where !== null) {
            $children[] = $this->where;
        }

        return $children;
    }

    public function data(): array
    {
        $data = [
            'node' => 'each',
            'source' => $this->source->toArray(),
            'variable' => $this->variable,
            'body' => $this->body->toArray(),
        ];

        if ($this->where !== null) {
            $data['where'] = $this->where->toArray();
        }

        return $data;
    }
}
