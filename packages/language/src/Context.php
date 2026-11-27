<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

use ArrayAccess;
use Atrium\Language\Functions\Functions;
use Atrium\Language\Values\Date;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * What a formula is worked out against: the fields it may name, their values,
 * what its author is allowed, and the limits of the run. Each method returns a
 * changed copy.
 */
final class Context
{
    /** @var list<string> */
    private array $permissions = [];

    private ?Limits $limits = null;

    private ?Date $today = null;

    private ?Closure $clock = null;

    private ?Functions $functions = null;

    /** @param  array<string,mixed>|ArrayAccess<string,mixed>  $values */
    private function __construct(
        private readonly Schema $schema,
        private readonly array|ArrayAccess $values,
    ) {}

    /** @param  array<string,mixed>|ArrayAccess<string,mixed>  $values  by field key; a missing one is empty */
    public static function make(Schema $schema, array|ArrayAccess $values = []): self
    {
        return new self($schema, $values);
    }

    /** The permissions of the formula's author, which functions may ask for. */
    public function allow(string ...$permissions): self
    {
        $copy = clone $this;
        $copy->permissions = [...$this->permissions, ...$permissions];

        return $copy;
    }

    public function withLimits(Limits $limits): self
    {
        $copy = clone $this;
        $copy->limits = $limits;

        return $copy;
    }

    /** The day TODAY() gives, instead of today in the application's time zone. */
    public function withToday(DateTimeInterface $today): self
    {
        $copy = clone $this;
        $copy->today = Date::from($today) ?? throw new InvalidArgumentException('The day is outside the years 1 to 9999.');

        return $copy;
    }

    /** @param  Closure(): int  $clock  nanoseconds that only grow, for the time limit */
    public function withClock(Closure $clock): self
    {
        $copy = clone $this;
        $copy->clock = $clock;

        return $copy;
    }

    /** Other functions than the application's registry. */
    public function withFunctions(Functions $functions): self
    {
        $copy = clone $this;
        $copy->functions = $functions;

        return $copy;
    }

    public function schema(): Schema
    {
        return $this->schema;
    }

    public function value(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return $this->permissions;
    }

    public function limits(): Limits
    {
        return $this->limits ?? new Limits;
    }

    public function today(): Date
    {
        return $this->today ?? Date::from(Carbon::now());
    }

    /** @return Closure(): int */
    public function clock(): Closure
    {
        return $this->clock ?? static fn (): int => hrtime(true);
    }

    public function functions(): Functions
    {
        return $this->functions ?? app(Functions::class);
    }
}
