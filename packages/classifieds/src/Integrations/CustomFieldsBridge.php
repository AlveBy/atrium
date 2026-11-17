<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Integrations;

use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Support\PropertySource;
use Atrium\CustomFields\CustomFacets;
use Atrium\CustomFields\CustomFields;
use Atrium\CustomFields\FieldType;
use Atrium\CustomFields\Models\CustomField;
use Atrium\CustomFields\Query\CustomQuery;
use Illuminate\Database\Eloquent\Builder;

/**
 * The custom fields module (alveby/atrium-custom-fields) seen from the
 * board. Bound as the PropertySource only when that module is installed.
 */
final class CustomFieldsBridge implements PropertySource
{
    public function installed(): bool
    {
        return app(CustomFields::class)->isInstalled();
    }

    /**
     * @return array<string,string> code => label
     */
    public function options(): array
    {
        $options = [];

        foreach (app(CustomFields::class)->fields(Ad::class) as $field) {
            $options[$field->code] = $field->labelText();
        }

        return $options;
    }

    /**
     * @param  array<int,string>  $codes
     * @return array<int,array<string,mixed>>
     */
    public function describe(array $codes): array
    {
        $fields = [];

        foreach ($codes as $code) {
            $field = app(CustomFields::class)->field(Ad::class, $code);

            if (! $field instanceof CustomField) {
                continue;
            }

            $type = $field->type;

            $fields[] = [
                'code' => $field->code,
                'label' => $field->labelText(),
                'hint' => $field->hintText(),
                'type' => $type->value,
                'kind' => match ($type) {
                    FieldType::Number, FieldType::Decimal => 'number',
                    FieldType::Boolean => 'boolean',
                    FieldType::Date => 'date',
                    FieldType::Select => 'select',
                    FieldType::MultiSelect => 'multiselect',
                    FieldType::Text => 'text',
                    default => 'string',
                },
                'options' => $type->hasOptions() ? $field->optionList() : [],
                'required' => (bool) $field->required,
                'filterable' => $field->isIndexed() && (bool) $field->filterable,
            ];
        }

        return $fields;
    }

    /**
     * @param  Builder<Ad>  $query
     * @param  array<string,mixed>  $filters
     */
    public function filter(Builder $query, array $filters): void
    {
        app(CustomQuery::class)->filters($query, $filters);
    }

    /**
     * @param  Builder<Ad>  $query
     * @param  array<string,mixed>  $filters
     * @param  array<int,string>  $codes
     * @return array<string,array<string|int,int>>
     */
    public function counts(Builder $query, array $filters, array $codes): array
    {
        return $codes === [] ? [] : CustomFacets::for(Ad::class, $query)->filters($filters)->counts($codes);
    }

    public function write(Ad $ad, string $code, mixed $value): void
    {
        app(CustomFields::class)->set($ad, $code, $value);
    }

    public function value(Ad $ad, string $code): mixed
    {
        return app(CustomFields::class)->get($ad, $code);
    }
}
