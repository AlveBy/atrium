<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Models;

use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\ColorField;
use Atrium\Admin\Fields\DisplayField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Atrium\ImageMap\Outline;
use Atrium\ImageMap\Pattern;
use Atrium\ImageMap\Support\Contrast;
use Illuminate\Database\Eloquent\Model;

/**
 * The state of an area — free, booked, sold for lots; open, coming soon,
 * for lease for shops. Each status has a colour and also a fill pattern
 * and a border, so statuses differ for those who do not see the colour.
 *
 * @property int $id
 * @property int|null $site_id
 * @property string $code
 * @property string $color
 * @property string $pattern
 * @property string $outline
 * @property int $sort
 */
class Status extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    /** What the outline is checked against: the usual light plan. */
    public const PLAN_BACKGROUND = '#ffffff';

    protected $table = 'image_map_statuses';

    /** @var array<int,string> */
    protected array $translatable = ['name'];

    protected $fillable = ['site_id', 'name', 'code', 'color', 'pattern', 'outline', 'sort'];

    protected $attributes = ['color' => '#0072B2', 'pattern' => 'solid', 'outline' => 'solid', 'sort' => 500];

    /** @var array<int,string> */
    protected $appends = ['contrast'];

    protected function casts(): array
    {
        return ['sort' => 'integer'];
    }

    public function patternCase(): Pattern
    {
        return Pattern::tryFrom($this->pattern) ?? Pattern::Solid;
    }

    public function outlineCase(): Outline
    {
        return Outline::tryFrom($this->outline) ?? Outline::Solid;
    }

    /**
     * The colour as #rrggbb, whatever was typed.
     */
    public function hex(): string
    {
        return vsprintf('#%02x%02x%02x', Contrast::rgb($this->color));
    }

    public function contrastRatio(string $background = self::PLAN_BACKGROUND): float
    {
        return Contrast::ratio($this->hex(), $background);
    }

    /**
     * "4.5:1 ✓" in the list, so the number is seen, not guessed from a swatch.
     */
    public function getContrastAttribute(): string
    {
        $ratio = $this->contrastRatio();

        return __('atrium-image-map::image-map.contrast.'.($ratio >= Contrast::GRAPHICS ? 'enough' : 'low'), [
            'ratio' => number_format($ratio, 2),
        ]);
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-image-map::image-map.modules.statuses');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-image-map::image-map.create.status');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'image_map_statuses';
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
            TextField::make('name')->label(__('atrium-image-map::image-map.fields.status_name'))->required(),
            TextField::make('code')->label(__('atrium-image-map::image-map.fields.code'))
                ->required()->rules(['alpha_dash', 'max:50']),
            ColorField::make('color')->label(__('atrium-image-map::image-map.fields.color'))
                ->hint(__('atrium-image-map::image-map.hints.color'))
                ->required()->rules(['regex:/^#[0-9a-fA-F]{6}$/'])->default('#0072B2'),
            SelectField::make('pattern')->label(__('atrium-image-map::image-map.fields.pattern'))
                ->hint(__('atrium-image-map::image-map.hints.pattern'))
                ->options(Pattern::options())->required()->default(Pattern::Solid->value),
            SelectField::make('outline')->label(__('atrium-image-map::image-map.fields.outline'))
                ->options(Outline::options())->required()->default(Outline::Solid->value),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
            DisplayField::make('preview')->label(__('atrium-image-map::image-map.fields.preview'))
                ->html(fn (Status $status): string => view('atrium-image-map::admin.status-preview', ['status' => $status])->render()),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium-image-map::image-map.fields.status_name'))->sortable()->searchable(),
            TextColumn::make('code')->label(__('atrium-image-map::image-map.fields.code'))->sortable(),
            TextColumn::make('color')->label(__('atrium-image-map::image-map.fields.color')),
            TextColumn::make('pattern')->label(__('atrium-image-map::image-map.fields.pattern')),
            TextColumn::make('contrast')->label(__('atrium-image-map::image-map.fields.contrast')),
        ];
    }
}
