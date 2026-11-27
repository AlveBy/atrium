<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Interpreter;

use ArrayAccess;
use Atrium\Language\FormulaError;
use Atrium\Language\Position;
use Atrium\Language\Types\Kind;
use Atrium\Language\Types\Type;
use Atrium\Language\Values\Date;
use Atrium\Language\Values\Numbers;
use Atrium\Language\Values\Record;
use Brick\Math\BigDecimal;
use DateTimeInterface;
use Stringable;
use Traversable;

/**
 * Takes what PHP gives — a field's value, a function's result — into the
 * language, as the type it should be. Lists are read item by item under the
 * run's limits, so even one that never ends is stopped.
 */
final readonly class Conversion
{
    /** How deep lists and records may be inside one another. */
    public const NESTING = 64;

    /**
     * @param  string  $code  the problem of a value that does not fit
     * @param  array<string,string|Type>  $params
     */
    public function __construct(
        private Meter $meter,
        private string $code,
        private array $params,
        private ?Position $at,
    ) {}

    /** @param  int  $level  how many lists and records the value is inside */
    public function convert(mixed $raw, Type $type, int $level = 0): mixed
    {
        // Before it is walked into: a value may be nested without end.
        if ($level > self::NESTING) {
            throw $this->unfit();
        }

        // A form sends an empty input as an empty text.
        if ($raw === null || ($raw === '' && in_array($type->kind, [Kind::Number, Kind::Boolean, Kind::Date], true))) {
            // An empty value takes its place in a list or a record like any other.
            $this->meter->charge(null, $this->at);

            return null;
        }

        if (in_array($type->kind, [Kind::Any, Kind::Union, Kind::Empty], true)) {
            return $this->open($raw, $type, $level);
        }

        $value = match ($type->kind) {
            Kind::Number => $this->number($raw),
            Kind::Text => $this->text($raw),
            Kind::Boolean => is_bool($raw) ? $raw : null,
            Kind::Date => $this->date($raw),
            Kind::List => is_iterable($raw) ? $this->list($raw, $type->of, $level) : null,
            default => $this->record($raw, $type, $level),
        } ?? throw $this->unfit();

        $this->meter->charge($value, $this->at);

        return $value;
    }

    private function number(mixed $raw): ?BigDecimal
    {
        $number = match (true) {
            is_int($raw) => BigDecimal::of($raw),
            // var_export writes the shortest text that reads back as the same float.
            is_float($raw) => is_finite($raw) ? BigDecimal::of(var_export($raw, true)) : null,
            is_string($raw) => Numbers::parse($raw),
            $raw instanceof BigDecimal => $raw,
            default => null,
        };

        return $number === null ? null : Numbers::bounded($number, $this->at);
    }

    private function text(mixed $raw): ?string
    {
        if (! is_string($raw) && ! $raw instanceof Stringable) {
            return null;
        }

        $text = (string) $raw;

        return mb_check_encoding($text, 'UTF-8') ? $text : null;
    }

    private function date(mixed $raw): ?Date
    {
        return match (true) {
            $raw instanceof Date => $raw,
            $raw instanceof DateTimeInterface => Date::from($raw),
            is_string($raw) => Date::parse($raw),
            default => null,
        };
    }

    /**
     * @param  iterable<mixed>  $raw
     * @return list<mixed>
     */
    private function list(iterable $raw, Type $of, int $level): array
    {
        $items = [];

        foreach ($raw as $item) {
            $this->meter->step($this->at);
            $items[] = $this->convert($item, $of, $level + 1);
        }

        return $items;
    }

    private function record(mixed $raw, Type $type, int $level): ?Record
    {
        if ($raw instanceof Record) {
            return $raw;
        }

        if (! is_array($raw) && ! $raw instanceof ArrayAccess) {
            return null;
        }

        $values = [];

        foreach ($type->fields->fields() as $key => $field) {
            $values[$key] = $this->convert($raw[$key] ?? null, $field->type, $level + 1);
        }

        return new Record($type->fields, $values);
    }

    /** A value whose type is not said beforehand is taken as what it is. */
    private function open(mixed $raw, Type $type, int $level): mixed
    {
        $found = match (true) {
            is_bool($raw) => Type::boolean(),
            is_int($raw), is_float($raw), $raw instanceof BigDecimal => Type::number(),
            is_string($raw) => Type::text(),
            $raw instanceof Date, $raw instanceof DateTimeInterface => Type::date(),
            is_array($raw) && array_is_list($raw), $raw instanceof Traversable => Type::listOf(Type::any()),
            default => null,
        };

        if ($raw instanceof Record) {
            $this->meter->charge($raw, $this->at);
        }

        $value = match (true) {
            $raw instanceof Record => $raw,
            $found !== null => $this->convert($raw, $found, $level),
            default => null,
        };

        if ($value === null || ! $type->admits($value)) {
            throw $this->unfit();
        }

        return $value;
    }

    private function unfit(): FormulaError
    {
        return FormulaError::of($this->code, $this->at, $this->params);
    }
}
