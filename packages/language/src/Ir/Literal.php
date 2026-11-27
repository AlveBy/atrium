<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Ir;

use Atrium\Language\Position;
use Atrium\Language\Types\Type;
use Atrium\Language\Values\Date;
use Atrium\Language\Values\Numbers;
use Brick\Math\BigDecimal;

/** A value written in the formula itself; null is the empty value. */
final readonly class Literal extends Node
{
    public BigDecimal|Date|string|bool|null $value;

    public function __construct(BigDecimal|Date|string|bool|null $value, ?Position $at = null)
    {
        parent::__construct($at);

        $this->value = $value instanceof BigDecimal ? Numbers::bounded($value, $at) : $value;
    }

    public function accept(Visitor $visitor): mixed
    {
        return $visitor->literal($this);
    }

    public function children(): array
    {
        return [];
    }

    public function data(): array
    {
        $type = Type::of($this->value)->kind->value;

        return match ($this->value) {
            null => ['node' => 'literal', 'type' => $type],
            true, false => ['node' => 'literal', 'type' => $type, 'value' => $this->value],
            default => ['node' => 'literal', 'type' => $type, 'value' => (string) $this->value],
        };
    }
}
