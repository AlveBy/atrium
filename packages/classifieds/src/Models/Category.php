<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Contracts\Sitemapable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\MultiSelectField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Fields\TreeSelectField;
use Atrium\Admin\Support\ContentLanguages;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Atrium\Classifieds\Support\Properties;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A node of the category tree. Each node names the custom fields its ads
 * fill in (`fields`); a subcategory has its parent's fields and its own,
 * so "Transport → Cars" asks for the brand and the mileage, "Transport"
 * only for what all vehicles share.
 *
 * @property int $id
 * @property int|null $site_id
 * @property int|null $parent_id
 * @property string $code
 * @property array<int,string>|null $fields
 * @property bool $active
 * @property int $sort
 * @property-read Category|null $parent
 * @property-read Collection<int,Category> $children
 */
class Category extends Model implements Administrable, Sitemapable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'classified_categories';

    /** @var array<int,string> */
    protected array $translatable = ['name', 'description'];

    protected $fillable = ['site_id', 'parent_id', 'name', 'code', 'description', 'fields', 'active', 'sort'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort' => 'integer', 'parent_id' => 'integer', 'fields' => 'array'];
    }

    /**
     * @return BelongsTo<Category,$this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category,$this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort')->orderBy('id');
    }

    /**
     * @return HasMany<Ad,$this>
     */
    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }

    /**
     * The category and its parents, from the root down. A broken tree (a
     * parent pointing back down) stops at the first repeat.
     *
     * @return array<int,Category>
     */
    public function lineage(): array
    {
        $chain = [];
        $seen = [];
        $node = $this;

        while ($node !== null && ! isset($seen[$node->id])) {
            $seen[$node->id] = true;
            array_unshift($chain, $node);
            $node = $node->parent;
        }

        return $chain;
    }

    /**
     * The codes from the root, the category's address on the site.
     */
    public function path(): string
    {
        return implode('/', array_map(static fn (self $node): string => $node->code, $this->lineage()));
    }

    /**
     * Custom field codes of this category, its parents' first.
     *
     * @return array<int,string>
     */
    public function fieldCodes(): array
    {
        $codes = [];

        foreach ($this->lineage() as $node) {
            foreach ($node->fields ?? [] as $code) {
                $codes[] = (string) $code;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Ids of the category and every category below it, for "everything in
     * Transport".
     *
     * @return array<int,int>
     */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $level = [$this->id];

        while ($level !== []) {
            $level = self::query()->whereIn('parent_id', $level)->whereNotIn('id', $ids)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            $ids = [...$ids, ...$level];
        }

        return $ids;
    }

    /**
     * The category found by its address: every code must sit under the
     * previous one.
     */
    public static function findByPath(string $path): ?self
    {
        $node = null;

        foreach (array_filter(explode('/', trim($path, '/')), static fn (string $code): bool => $code !== '') as $code) {
            $node = self::query()->where('active', true)->where('code', $code)->where('parent_id', $node?->id)->first();

            if ($node === null) {
                return null;
            }
        }

        return $node;
    }

    public function url(?string $language = null): string
    {
        return app(ContentLanguages::class)->route('atrium.classifieds.category', ['path' => $this->path()], $language);
    }

    public static function sitemapQuery(): Builder
    {
        return static::query()->where('active', true);
    }

    public function sitemapUrl(string $language): ?string
    {
        return $this->url($language);
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-classifieds::classifieds.modules.categories');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-classifieds::classifieds.create.category');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'classified_categories';
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
        $properties = app(Properties::class);

        return array_values(array_filter([
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            TextField::make('name')->label(__('atrium-classifieds::classifieds.fields.name'))->required(),
            TextField::make('code')->label(__('atrium::fields.standard.code'))->required()->rules(['alpha_dash', 'max:100']),
            TreeSelectField::make('parent_id')->label(__('atrium::fields.standard.parent_section'))->model(self::class),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
            ContentField::make('description')->label(__('atrium::fields.standard.description')),
            // Without the custom fields module there is nothing to pick.
            $properties->available()
                ? MultiSelectField::make('fields')->label(__('atrium-classifieds::classifieds.fields.properties'))
                    ->options($properties->options())
                    ->hint(__('atrium-classifieds::classifieds.hints.properties'))
                : null,
        ]));
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium-classifieds::classifieds.fields.name'))->sortable()->searchable(),
            TextColumn::make('parent.name')->label(__('atrium::fields.standard.parent_section')),
            TextColumn::make('code')->label(__('atrium::fields.standard.code'))->searchable(),
            TextColumn::make('sort')->label(__('atrium::fields.standard.sort'))->sortable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
        ];
    }
}
