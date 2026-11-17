<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Models;

use Atrium\Admin\Actions\CreateFromRecord;
use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\DisplayField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A map on a picture: a mall, a business centre, a cottage village. Its
 * layers are the floors (or one plan); each has its own picture and areas.
 *
 * Shown on a page with `<x-atrium-image-map::map map="code" />`.
 *
 * @property int $id
 * @property int|null $site_id
 * @property string $code
 * @property bool $active
 * @property bool $show_search
 * @property bool $show_legend
 * @property bool $show_list
 * @property int $sort
 */
class ImageMap extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'image_maps';

    /** @var array<int,string> */
    protected array $translatable = ['name', 'description'];

    protected $fillable = ['site_id', 'name', 'code', 'description', 'active', 'show_search', 'show_legend', 'show_list', 'sort'];

    protected $attributes = ['active' => true, 'show_search' => true, 'show_legend' => true, 'show_list' => true, 'sort' => 500];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'show_search' => 'boolean',
            'show_legend' => 'boolean',
            'show_list' => 'boolean',
            'sort' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // The layers and areas go with the map; their own deleting events
        // run, so nothing is left behind.
        static::deleting(function (ImageMap $map): void {
            $map->layers()->withoutGlobalScopes()->get()->each->delete();
        });
    }

    /**
     * @return HasMany<Layer,$this>
     */
    public function layers(): HasMany
    {
        return $this->hasMany(Layer::class, 'map_id')->orderBy('sort')->orderBy('id');
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-image-map::image-map.modules.maps');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-image-map::image-map.create.map');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'image_maps';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public static function getAdministrationActions(): array
    {
        return [
            CreateFromRecord::make('layer')
                ->label(__('atrium-image-map::image-map.create.layer'))
                ->fill(['map_id' => 'id']),
        ];
    }

    public static function getAdministrationFields(): array
    {
        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            TextField::make('name')->label(__('atrium-image-map::image-map.fields.name'))->required(),
            TextField::make('code')->label(__('atrium-image-map::image-map.fields.code'))
                ->hint(__('atrium-image-map::image-map.hints.code'))
                ->required()->rules(['alpha_dash', 'max:100'])->unique('image_maps', 'code'),
            TextareaField::make('description')->label(__('atrium-image-map::image-map.fields.description')),
            CheckboxField::make('show_search')->label(__('atrium-image-map::image-map.fields.show_search'))->default(true),
            CheckboxField::make('show_legend')->label(__('atrium-image-map::image-map.fields.show_legend'))->default(true),
            CheckboxField::make('show_list')->label(__('atrium-image-map::image-map.fields.show_list'))
                ->hint(__('atrium-image-map::image-map.hints.show_list'))->default(true),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
            DisplayField::make('layers_overview')->label(__('atrium-image-map::image-map.fields.layers'))
                ->html(fn (ImageMap $map): string => view('atrium-image-map::admin.layers', [
                    'map' => $map,
                    'layers' => $map->layers()->withCount('areas')->get(),
                ])->render()),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium-image-map::image-map.fields.name'))->sortable()->searchable(),
            TextColumn::make('code')->label(__('atrium-image-map::image-map.fields.code'))->sortable()->searchable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }
}
