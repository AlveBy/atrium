<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Models;

use Atrium\Admin\Columns\ImageColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\BelongsToField;
use Atrium\Admin\Fields\DisplayField;
use Atrium\Admin\Fields\ImageField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Support\MediaUrl;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Atrium\ImageMap\Support\ImageSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A floor of a building, or the one plan of a village: a picture and the
 * areas drawn on it. The picture's size in pixels is kept, since the areas
 * are drawn in those pixels.
 *
 * @property int $id
 * @property int|null $site_id
 * @property int $map_id
 * @property string|null $image
 * @property int|null $width
 * @property int|null $height
 * @property int $sort
 * @property-read ImageMap|null $map
 */
class Layer extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'image_map_layers';

    /** @var array<int,string> */
    protected array $translatable = ['name'];

    protected $fillable = ['site_id', 'map_id', 'name', 'image', 'width', 'height', 'sort'];

    protected $attributes = ['sort' => 500];

    protected function casts(): array
    {
        return ['map_id' => 'integer', 'width' => 'integer', 'height' => 'integer', 'sort' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Layer $layer): void {
            if ($layer->site_id === null && $layer->map_id !== null) {
                $layer->site_id = ImageMap::query()->withoutGlobalScopes()->whereKey($layer->map_id)->value('site_id');
            }

            // A new picture brings its own size; the areas are scaled to it,
            // so a plan replaced by a sharper copy keeps its areas in place.
            if ($layer->isDirty('image') && ($size = ImageSize::of($layer->image)) !== null) {
                $oldWidth = (int) $layer->getOriginal('width');
                $oldHeight = (int) $layer->getOriginal('height');

                $layer->width = $size[0];
                $layer->height = $size[1];

                if ($layer->exists && $oldWidth > 0 && $oldHeight > 0 && ($oldWidth !== $size[0] || $oldHeight !== $size[1])) {
                    $layer->scaleAreas($size[0] / $oldWidth, $size[1] / $oldHeight);
                }
            }
        });

        static::deleting(function (Layer $layer): void {
            $layer->areas()->withoutGlobalScopes()->get()->each->delete();
        });
    }

    /**
     * @return BelongsTo<ImageMap,$this>
     */
    public function map(): BelongsTo
    {
        return $this->belongsTo(ImageMap::class, 'map_id');
    }

    /**
     * @return HasMany<Area,$this>
     */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class, 'layer_id')->orderBy('sort')->orderBy('id');
    }

    public function imageUrl(): ?string
    {
        return MediaUrl::to($this->image);
    }

    /**
     * "Mall — Floor 2", for the layer select of an area.
     */
    public function getOptionLabelAttribute(): string
    {
        $map = $this->map()->withoutGlobalScopes()->first();

        return trim(($map === null ? '' : $map->getTranslation('name', app()->getLocale()).' — ').$this->getTranslation('name', app()->getLocale()));
    }

    /**
     * Stretch every area of the layer, when the picture changes size.
     */
    public function scaleAreas(float $x, float $y): void
    {
        foreach ($this->areas()->withoutGlobalScopes()->get() as $area) {
            $area->geometry = $area->scaledGeometry($x, $y);
            $area->saveQuietly();
        }
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-image-map::image-map.modules.layers');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-image-map::image-map.create.layer');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'image_map_layers';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'sort';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public function scopeAdminList(Builder $query): Builder
    {
        return $query->with('map');
    }

    public static function getAdministrationFields(): array
    {
        return [
            BelongsToField::make('map_id')->label(__('atrium-image-map::image-map.fields.map'))->model(ImageMap::class)->required(),
            TextField::make('name')->label(__('atrium-image-map::image-map.fields.layer_name'))
                ->hint(__('atrium-image-map::image-map.hints.layer_name'))->required(),
            ImageField::make('image')->label(__('atrium-image-map::image-map.fields.image'))
                ->hint(__('atrium-image-map::image-map.hints.image'))
                ->directory('image-maps')->maxSize(10240)->required(),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
            DisplayField::make('editor')->label(__('atrium-image-map::image-map.fields.areas'))
                ->html(fn (Layer $layer): string => view('atrium-image-map::admin.editor-frame', ['layer' => $layer])->render()),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            ImageColumn::make('image')->label(__('atrium-image-map::image-map.fields.image')),
            TextColumn::make('name')->label(__('atrium-image-map::image-map.fields.layer_name'))->sortable()->searchable(),
            TextColumn::make('map.name')->label(__('atrium-image-map::image-map.fields.map')),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
        ];
    }
}
