<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A city or an office where the work is done. The name is per language;
 * the address goes into the JobPosting data search engines read.
 *
 * @property int $id
 * @property int|null $site_id
 * @property string|null $street
 * @property string|null $postal_code
 * @property string|null $region
 * @property string|null $country
 * @property bool $active
 * @property int $sort
 */
class Location extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'job_locations';

    /** @var array<int,string> */
    protected array $translatable = ['name'];

    protected $fillable = ['site_id', 'name', 'street', 'postal_code', 'region', 'country', 'active', 'sort'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort' => 'integer'];
    }

    /**
     * @return HasMany<Vacancy,$this>
     */
    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    /**
     * The country as an upper-case ISO 3166 code, the way the form takes it.
     */
    public function setCountryAttribute(?string $value): void
    {
        $value = strtoupper(trim((string) $value));
        $this->attributes['country'] = $value === '' ? null : $value;
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-jobs::jobs.modules.locations');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-jobs::jobs.create.location');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'job_locations';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public static function getAdministrationFields(): array
    {
        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            TextField::make('name')->label(__('atrium-jobs::jobs.fields.city'))->required()->hint(__('atrium-jobs::jobs.hints.city')),
            TextField::make('street')->label(__('atrium-jobs::jobs.fields.street'))->maxLength(255),
            TextField::make('postal_code')->label(__('atrium-jobs::jobs.fields.postal_code'))->maxLength(20),
            TextField::make('region')->label(__('atrium-jobs::jobs.fields.region'))->maxLength(255),
            TextField::make('country')->label(__('atrium-jobs::jobs.fields.country'))->rules(['regex:/^[A-Za-z]{2}$/'])->hint(__('atrium-jobs::jobs.hints.country')),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium-jobs::jobs.fields.city'))->sortable()->searchable(),
            TextColumn::make('country')->label(__('atrium-jobs::jobs.fields.country'))->sortable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }
}
