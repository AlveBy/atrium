<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Models;

use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A region, oblast or state: cities are grouped by it in the filters.
 *
 * @property int $id
 * @property int|null $site_id
 * @property string $code
 * @property int $sort
 */
class Region extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'classified_regions';

    /** @var array<int,string> */
    protected array $translatable = ['name'];

    protected $fillable = ['site_id', 'name', 'code', 'sort'];

    protected function casts(): array
    {
        return ['sort' => 'integer'];
    }

    /**
     * @return HasMany<City,$this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-classifieds::classifieds.modules.regions');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-classifieds::classifieds.create.region');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'classified_regions';
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
            TextField::make('name')->label(__('atrium-classifieds::classifieds.fields.name'))->required(),
            TextField::make('code')->label(__('atrium::fields.standard.code'))->required()->rules(['alpha_dash', 'max:100']),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium-classifieds::classifieds.fields.name'))->sortable()->searchable(),
            TextColumn::make('code')->label(__('atrium::fields.standard.code'))->searchable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
        ];
    }
}
