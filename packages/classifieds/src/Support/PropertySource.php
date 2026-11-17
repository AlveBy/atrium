<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Classifieds\Models\Ad;
use Illuminate\Database\Eloquent\Builder;

/**
 * Where the properties of ads are defined, kept and filtered. The custom
 * fields module provides it (Integrations\CustomFieldsBridge); the
 * provider binds it only when that module is installed.
 */
interface PropertySource
{
    public function installed(): bool;

    /**
     * @return array<string,string> code => label
     */
    public function options(): array;

    /**
     * The fields with these codes, in that order, unknown codes skipped:
     * {code, label, hint, type, kind, options, required, filterable}, where
     * kind is string, text, number, boolean, date, select or multiselect.
     *
     * @param  array<int,string>  $codes
     * @return array<int,array<string,mixed>>
     */
    public function describe(array $codes): array;

    /**
     * @param  Builder<Ad>  $query
     * @param  array<string,mixed>  $filters  code => value, list or {min, max}
     */
    public function filter(Builder $query, array $filters): void;

    /**
     * @param  Builder<Ad>  $query
     * @param  array<string,mixed>  $filters
     * @param  array<int,string>  $codes
     * @return array<string,array<string|int,int>>
     */
    public function counts(Builder $query, array $filters, array $codes): array;

    public function write(Ad $ad, string $code, mixed $value): void;

    public function value(Ad $ad, string $code): mixed;
}
