<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Models;

use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\BelongsToField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A city an ad is in. Its code is part of the city's landing address,
 * /classifieds/city/{code}.
 *
 * @property int $id
 * @property int|null $site_id
 * @property int|null $region_id
 * @property string $code
 * @property float|null $lat
 * @property float|null $lng
 * @property int $sort
 * @property-read Region|null $region
 */
class City extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'classified_cities';

    /** @var array<int,string> */
    protected array $translatable = ['name'];

    protected $fillable = ['site_id', 'region_id', 'name', 'code', 'lat', 'lng', 'sort'];

    protected function casts(): array
    {
        return ['sort' => 'integer', 'region_id' => 'integer', 'lat' => 'float', 'lng' => 'float'];
    }

    /**
     * @return BelongsTo<Region,$this>
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-classifieds::classifieds.modules.cities');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-classifieds::classifieds.create.city');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'classified_cities';
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
            BelongsToField::make('region_id')->label(__('atrium-classifieds::classifieds.fields.region'))->model(Region::class),
            NumberField::make('lat')->label(__('atrium-classifieds::classifieds.fields.lat'))->min(-90)->max(90)->decimals(7),
            NumberField::make('lng')->label(__('atrium-classifieds::classifieds.fields.lng'))->min(-180)->max(180)->decimals(7),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium-classifieds::classifieds.fields.name'))->sortable()->searchable(),
            TextColumn::make('region.name')->label(__('atrium-classifieds::classifieds.fields.region')),
            TextColumn::make('code')->label(__('atrium::fields.standard.code'))->searchable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
        ];
    }
}
