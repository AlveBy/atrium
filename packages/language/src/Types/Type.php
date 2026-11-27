<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Types;

use Atrium\Language\Schema;
use Atrium\Language\Values\Date;
use Atrium\Language\Values\Record;
use Brick\Math\BigDecimal;

/**
 * The type of a value of the language. `any` is a type that is only known
 * while running; `empty` is that of a missing value.
 */
final readonly class Type
{
    /**
     * @param  list<Type>  $options  the types of a union
     */
    private function __construct(
        public Kind $kind,
        public ?Type $of = null,
        public ?Schema $fields = null,
        public array $options = [],
    ) {}

    public static function number(): self
    {
        return new self(Kind::Number);
    }

    public static function text(): self
    {
        return new self(Kind::Text);
    }

    public static function boolean(): self
    {
        return new self(Kind::Boolean);
    }

    public static function date(): self
    {
        return new self(Kind::Date);
    }

    public static function empty(): self
    {
        return new self(Kind::Empty);
    }

    public static function any(): self
    {
        return new self(Kind::Any);
    }

    public static function listOf(Type $of): self
    {
        return new self(Kind::List, of: $of);
    }

    public static function record(Schema $fields): self
    {
        return new self(Kind::Record, fields: $fields);
    }

    /** One of several types, for the parameters of functions. */
    public static function oneOf(Type $first, Type ...$others): self
    {
        return new self(Kind::Union, options: [$first, ...$others]);
    }

    /** The type of a value the language holds. */
    public static function of(mixed $value): self
    {
        return match (true) {
            $value === null => self::empty(),
            is_bool($value) => self::boolean(),
            is_string($value) => self::text(),
            $value instanceof BigDecimal => self::number(),
            $value instanceof Date => self::date(),
            $value instanceof Record => self::record($value->fields),
            default => self::listOf(self::any()),
        };
    }

    /** Whether a value of the other type may stand where this one is expected. */
    public function accepts(Type $actual): bool
    {
        if ($this->kind === Kind::Any || $actual->kind === Kind::Any || $actual->kind === Kind::Empty) {
            return true;
        }

        if ($this->kind === Kind::Union) {
            foreach ($this->options as $option) {
                if ($option->accepts($actual)) {
                    return true;
                }
            }

            return false;
        }

        if ($this->kind !== $actual->kind) {
            return false;
        }

        return $this->of === null || $this->of->accepts($actual->of);
    }

    /** Like accepts(), for a value at hand: a list is checked item by item. */
    public function admits(mixed $value): bool
    {
        if ($this->kind === Kind::Union) {
            foreach ($this->options as $option) {
                if ($option->admits($value)) {
                    return true;
                }
            }

            return false;
        }

        if ($this->of === null) {
            return $this->accepts(self::of($value));
        }

        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if ($item !== null && ! $this->of->admits($item)) {
                return false;
            }
        }

        return true;
    }

    /** The type that both fit, or null when there is none. */
    public static function unify(Type $first, Type $second): ?self
    {
        if ($first->kind === Kind::Empty) {
            return $second;
        }

        if ($second->kind === Kind::Empty) {
            return $first;
        }

        if ($first->kind === Kind::Any || $second->kind === Kind::Any) {
            return self::any();
        }

        if ($first->kind !== $second->kind || $first->fields !== $second->fields) {
            return null;
        }

        if ($first->of === null) {
            return $first;
        }

        $of = self::unify($first->of, $second->of);

        return $of === null ? null : self::listOf($of);
    }

    /** The type's name for people. */
    public function describe(?string $locale = null): string
    {
        if ($this->kind === Kind::Union) {
            return implode(
                trans('atrium-language::errors.types.or', [], $locale),
                array_map(static fn (Type $option): string => $option->describe($locale), $this->options),
            );
        }

        $params = $this->of === null ? [] : ['of' => $this->of->describe($locale)];

        return trans('atrium-language::errors.types.'.$this->kind->value, $params, $locale);
    }
}
