<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Functions;

use Atrium\Language\Types\Type;
use Closure;
use InvalidArgumentException;

/**
 * A function formulas may call: its names, what it takes and gives, the
 * permission it needs and the PHP that does it. The handler gets one
 * Invocation and returns a plain PHP value of the type it declares; the
 * run's limits cannot stop PHP code in the middle, so it asks the Invocation
 * before it loops or makes anything big.
 */
final readonly class FormulaFunction
{
    /**
     * @param  non-empty-list<string>  $names  the first is the one it is known by
     * @param  list<Parameter>  $parameters
     * @param  Type|Closure(list<Type>): Type  $returns  or how the result's type follows from the arguments'
     * @param  Closure(Invocation): mixed  $handler
     * @param  string|null  $permission  what the author of a formula must be allowed to call it
     */
    public function __construct(
        public array $names,
        public array $parameters,
        private Type|Closure $returns,
        private Closure $handler,
        public ?string $permission = null,
    ) {
        $optional = false;

        foreach ($parameters as $index => $parameter) {
            if ($optional && ! $parameter->optional) {
                throw new InvalidArgumentException("A required parameter of [{$names[0]}] follows an optional one.");
            }

            if ($parameter->variadic && $index !== count($parameters) - 1) {
                throw new InvalidArgumentException("Only the last parameter of [{$names[0]}] may be variadic.");
            }

            $optional = $parameter->optional;
        }
    }

    public function name(): string
    {
        return $this->names[0];
    }

    /** The parameter the argument at this position (from 0) goes to. */
    public function parameter(int $position): Parameter
    {
        return $this->parameters[min($position, count($this->parameters) - 1)];
    }

    public function minimum(): int
    {
        return count(array_filter($this->parameters, static fn (Parameter $parameter): bool => ! $parameter->optional));
    }

    /** The most arguments it takes, or null for any number. */
    public function maximum(): ?int
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->variadic) {
                return null;
            }
        }

        return count($this->parameters);
    }

    /** @param  list<Type>  $arguments  as many as the function takes */
    public function returnType(array $arguments): Type
    {
        return $this->returns instanceof Type ? $this->returns : ($this->returns)($arguments);
    }

    public function call(Invocation $invocation): mixed
    {
        return ($this->handler)($invocation);
    }
}
