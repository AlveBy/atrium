<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests\Fixtures;

use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Support\PropertySource;
use Illuminate\Database\Eloquent\Builder;

/**
 * Custom fields as the board sees them, kept in the ad's own "custom"
 * column: what the custom-fields module does through its typed index.
 */
final class FakeProperties implements PropertySource
{
    public bool $installed = true;

    /** @var array<string,array<string,mixed>> */
    public array $fields = [
        'brand' => ['code' => 'brand', 'label' => 'Brand', 'hint' => null, 'type' => 'select', 'kind' => 'select', 'options' => ['acme' => 'Acme', 'zenith' => 'Zenith'], 'required' => true, 'filterable' => true],
        'year' => ['code' => 'year', 'label' => 'Year', 'hint' => 'Of manufacture', 'type' => 'number', 'kind' => 'number', 'options' => [], 'required' => false, 'filterable' => true],
        'electric' => ['code' => 'electric', 'label' => 'Electric', 'hint' => null, 'type' => 'boolean', 'kind' => 'boolean', 'options' => [], 'required' => false, 'filterable' => true],
        'colours' => ['code' => 'colours', 'label' => 'Colours', 'hint' => null, 'type' => 'multiselect', 'kind' => 'multiselect', 'options' => ['red' => 'Red', 'blue' => 'Blue'], 'required' => false, 'filterable' => false],
        'note' => ['code' => 'note', 'label' => 'Note', 'hint' => null, 'type' => 'text', 'kind' => 'text', 'options' => [], 'required' => false, 'filterable' => false],
        'serial' => ['code' => 'serial', 'label' => 'Serial', 'hint' => null, 'type' => 'string', 'kind' => 'string', 'options' => [], 'required' => false, 'filterable' => false],
        'built' => ['code' => 'built', 'label' => 'Built', 'hint' => null, 'type' => 'date', 'kind' => 'date', 'options' => [], 'required' => false, 'filterable' => false],
    ];

    public function installed(): bool
    {
        return $this->installed;
    }

    public function options(): array
    {
        return array_map(static fn (array $field): string => $field['label'], $this->fields);
    }

    public function describe(array $codes): array
    {
        return array_values(array_filter(array_map(fn (string $code): ?array => $this->fields[$code] ?? null, $codes)));
    }

    public function filter(Builder $query, array $filters): void
    {
        foreach ($filters as $code => $value) {
            $column = 'custom->'.$code;

            if (is_array($value) && (isset($value['min']) || isset($value['max']))) {
                if (isset($value['min'])) {
                    $query->whereRaw('cast(json_extract(custom, ?) as real) >= ?', ['$.'.$code, (float) $value['min']]);
                }

                if (isset($value['max'])) {
                    $query->whereRaw('cast(json_extract(custom, ?) as real) <= ?', ['$.'.$code, (float) $value['max']]);
                }

                continue;
            }

            $query->whereIn($column, (array) $value);
        }
    }

    public function counts(Builder $query, array $filters, array $codes): array
    {
        $counts = [];

        foreach ($codes as $code) {
            foreach ((clone $query)->get(['id', 'custom']) as $ad) {
                $value = $ad->custom[$code] ?? null;

                if (is_scalar($value)) {
                    $counts[$code][(string) $value] = ($counts[$code][(string) $value] ?? 0) + 1;
                }
            }
        }

        return $counts;
    }

    public function write(Ad $ad, string $code, mixed $value): void
    {
        $custom = $ad->custom ?? [];
        $custom[$code] = $value;
        $ad->custom = $custom;
    }

    public function value(Ad $ad, string $code): mixed
    {
        return $ad->custom[$code] ?? null;
    }
}
