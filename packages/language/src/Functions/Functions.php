<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Functions;

use Atrium\Language\Functions\Library\DateFunctions;
use Atrium\Language\Functions\Library\LogicFunctions;
use Atrium\Language\Functions\Library\MathFunctions;
use Atrium\Language\Functions\Library\TextFunctions;
use Closure;
use InvalidArgumentException;

/**
 * The registry of the functions formulas may call. Names match whatever their
 * case. Its own functions come first, so a module cannot change what SUM does.
 */
final class Functions
{
    /** The hook registry modules add their FormulaFunction objects to. */
    public const REGISTRY = 'atrium.language.functions';

    /** Words of the language itself, which no function may be called. */
    private const RESERVED = ['if', 'если', 'and', 'и', 'or', 'или', 'not', 'не', 'true', 'истина', 'false', 'ложь'];

    /** @var array<string,FormulaFunction> by name in lower case */
    private array $own = [];

    /** @param  (Closure(): array<mixed>)|null  $contributed  more items, asked for at each lookup */
    public function __construct(private readonly ?Closure $contributed = null) {}

    /**
     * A registry with the functions that come with the language.
     *
     * @param  (Closure(): array<mixed>)|null  $contributed
     */
    public static function standard(?Closure $contributed = null): self
    {
        return (new self($contributed))->add(
            ...MathFunctions::all(),
            ...TextFunctions::all(),
            ...LogicFunctions::all(),
            ...DateFunctions::all(),
        );
    }

    public function add(FormulaFunction ...$functions): self
    {
        foreach ($functions as $function) {
            foreach ($function->names as $name) {
                $lower = mb_strtolower($name);

                if (preg_match('/^[\p{L}_][\p{L}\p{M}\p{N}_]*\z/u', $name) !== 1 || in_array($lower, self::RESERVED, true)) {
                    throw new InvalidArgumentException("A function cannot be called [{$name}].");
                }

                if (isset($this->own[$lower])) {
                    throw new InvalidArgumentException("Two functions are called [{$name}].");
                }

                $this->own[$lower] = $function;
            }
        }

        return $this;
    }

    public function find(string $name): ?FormulaFunction
    {
        $lower = mb_strtolower($name);

        if (isset($this->own[$lower])) {
            return $this->own[$lower];
        }

        foreach ($this->contributed === null ? [] : ($this->contributed)() as $item) {
            if ($item instanceof FormulaFunction && in_array($lower, array_map(mb_strtolower(...), $item->names), true)) {
                return $item;
            }
        }

        return null;
    }
}
