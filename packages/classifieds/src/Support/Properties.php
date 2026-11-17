<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Category;
use Illuminate\Database\Eloquent\Builder;

/**
 * The properties of a category's ads: custom fields of the Ad module
 * (alveby/atrium-custom-fields) that the category and its parents name.
 * Filters go through that module's typed index, so a list never reads the
 * ads' JSON. Without the module a category simply has no properties.
 */
class Properties
{
    public const MODULE = 'alveby/atrium-custom-fields';

    private ?bool $available = null;

    public function available(): bool
    {
        return $this->available ??= app()->bound(PropertySource::class) && $this->bridge()->installed();
    }

    public function flush(): void
    {
        $this->available = null;
    }

    /**
     * Every field defined for ads, for the category form.
     *
     * @return array<string,string>
     */
    public function options(): array
    {
        return $this->available() ? $this->bridge()->options() : [];
    }

    /**
     * The fields of the category, each as
     * {code, label, hint, type, kind, options, required, filterable}.
     *
     * @return array<int,array<string,mixed>>
     */
    public function for(?Category $category): array
    {
        if ($category === null || ! $this->available()) {
            return [];
        }

        return $this->bridge()->describe($category->fieldCodes());
    }

    /**
     * The category's filterable fields among the values sent, cleaned:
     * a value, a list, or {min, max}.
     *
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     */
    public function filters(?Category $category, array $input): array
    {
        $filters = [];

        foreach ($this->for($category) as $field) {
            $code = (string) $field['code'];
            $value = $input[$code] ?? null;

            if (! $field['filterable'] || $value === null || $value === '' || $value === []) {
                continue;
            }

            if (is_array($value) && (array_key_exists('min', $value) || array_key_exists('max', $value))) {
                $range = array_filter([
                    'min' => is_numeric($value['min'] ?? null) ? $value['min'] : null,
                    'max' => is_numeric($value['max'] ?? null) ? $value['max'] : null,
                ], static fn ($bound): bool => $bound !== null);

                if ($range !== []) {
                    $filters[$code] = $range;
                }

                continue;
            }

            $values = array_values(array_filter(array_map(static fn ($item): string => is_scalar($item) ? (string) $item : '', (array) $value), static fn (string $item): bool => $item !== ''));

            if ($values !== []) {
                $filters[$code] = count($values) === 1 && ! is_array($value) ? $values[0] : $values;
            }
        }

        return $filters;
    }

    /**
     * @param  Builder<Ad>  $query
     * @param  array<string,mixed>  $filters  from filters()
     */
    public function apply(Builder $query, array $filters): void
    {
        if ($filters !== [] && $this->available()) {
            $this->bridge()->filter($query, $filters);
        }
    }

    /**
     * How many ads each option of the category's list fields has.
     *
     * @param  Builder<Ad>  $query
     * @param  array<string,mixed>  $filters
     * @return array<string,array<string|int,int>>
     */
    public function counts(?Category $category, Builder $query, array $filters): array
    {
        $codes = [];

        foreach ($this->for($category) as $field) {
            if ($field['filterable'] && in_array($field['kind'], ['select', 'multiselect', 'boolean'], true)) {
                $codes[] = (string) $field['code'];
            }
        }

        return $codes === [] ? [] : $this->bridge()->counts($query, $filters, $codes);
    }

    /**
     * Validation rules of the category's fields on the posting form.
     *
     * @return array<string,array<int,mixed>>
     */
    public function rules(?Category $category): array
    {
        $rules = [];

        foreach ($this->for($category) as $field) {
            $key = 'props.'.$field['code'];
            $base = [$field['required'] ? 'required' : 'nullable'];

            $rules[$key] = match ($field['kind']) {
                'number' => [...$base, 'numeric'],
                'boolean' => [...$base, 'boolean'],
                'date' => [...$base, 'date'],
                'select' => [...$base, 'in:'.implode(',', array_map('strval', array_keys($field['options'])))],
                'multiselect' => [...$base, 'array'],
                'text' => [...$base, 'string', 'max:5000'],
                default => [...$base, 'string', 'max:255'],
            };

            if ($field['kind'] === 'multiselect') {
                $rules[$key.'.*'] = ['in:'.implode(',', array_map('strval', array_keys($field['options'])))];
            }
        }

        return $rules;
    }

    /**
     * Put the sent values on the ad, unsaved. Fields of other categories are
     * left as they are, so moving an ad back keeps what was filled in.
     *
     * @param  array<string,mixed>  $values
     */
    public function write(Ad $ad, ?Category $category, array $values): void
    {
        foreach ($this->for($category) as $field) {
            $this->bridge()->write($ad, (string) $field['code'], $values[$field['code']] ?? null);
        }
    }

    /**
     * The stored values of the ad by code, for the posting form.
     *
     * @return array<string,mixed>
     */
    public function raw(Ad $ad, ?Category $category): array
    {
        $values = [];

        foreach ($this->for($category) as $field) {
            $values[(string) $field['code']] = $this->bridge()->value($ad, (string) $field['code']);
        }

        return $values;
    }

    /**
     * The filled-in values of the ad as label => text, for its page.
     *
     * @return array<string,string>
     */
    public function values(Ad $ad, ?Category $category): array
    {
        $values = [];

        foreach ($this->for($category) as $field) {
            $value = $this->bridge()->value($ad, (string) $field['code']);

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $options = $field['options'];
            $text = match ($field['kind']) {
                'boolean' => $value ? __('atrium-classifieds::classifieds.yes') : __('atrium-classifieds::classifieds.no'),
                'select' => (string) ($options[(string) $value] ?? $value),
                'multiselect' => implode(', ', array_map(static fn ($item): string => (string) ($options[(string) $item] ?? $item), (array) $value)),
                default => is_scalar($value) ? (string) $value : '',
            };

            if ($text !== '') {
                $values[(string) $field['label']] = $text;
            }
        }

        return $values;
    }

    protected function bridge(): PropertySource
    {
        return app(PropertySource::class);
    }
}
