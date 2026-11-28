<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language;

use Atrium\Language\Types\Type;
use InvalidArgumentException;

/**
 * The fields a formula may name: each has a key, under which the host gives
 * its value, a type, and any number of other names (labels in several
 * languages, say). Names match whatever their case.
 */
final class Schema
{
    /** @var array<string,SchemaField> by normalised name */
    private array $names = [];

    /** @var array<string,SchemaField> by key */
    private array $fields = [];

    public static function make(): self
    {
        return new self;
    }

    public function field(string $key, Type $type, string ...$names): self
    {
        $field = new SchemaField($key, $type);

        foreach ([$key, ...$names] as $name) {
            $normal = Names::normalise($name);

            if (($this->names[$normal] ?? $field) !== $field) {
                throw new InvalidArgumentException("Two fields are called [{$name}].");
            }

            $this->names[$normal] = $field;
        }

        $this->fields[$key] = $field;

        return $this;
    }

    public function number(string $key, string ...$names): self
    {
        return $this->field($key, Type::number(), ...$names);
    }

    public function text(string $key, string ...$names): self
    {
        return $this->field($key, Type::text(), ...$names);
    }

    public function boolean(string $key, string ...$names): self
    {
        return $this->field($key, Type::boolean(), ...$names);
    }

    public function date(string $key, string ...$names): self
    {
        return $this->field($key, Type::date(), ...$names);
    }

    /** A list of related records, such as the lines of a document. */
    public function records(string $key, Schema $of, string ...$names): self
    {
        return $this->field($key, Type::listOf(Type::record($of)), ...$names);
    }

    public function find(string $name): ?SchemaField
    {
        return $this->names[Names::normalise($name)] ?? null;
    }

    /**
     * @return array<string,SchemaField> by key
     */
    public function fields(): array
    {
        return $this->fields;
    }
}
