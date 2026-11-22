<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace App\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SlugField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * The demo module. Its pages come from the theme: see `public_urls` in
 * config/atrium.php.
 */
class Article extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    /** @var array<int,string> */
    protected array $translatable = ['name', 'code', 'preview_text', 'detail_text'];

    protected $fillable = ['name', 'code', 'active', 'sort', 'preview_text', 'detail_text'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public static function getAdminLabel(): string
    {
        return __('articles.module');
    }

    public static function getAdministrationFields(): array
    {
        return [
            TextField::make('name')->label(__('atrium::fields.standard.name'))->required(),
            SlugField::make('code')->label(__('atrium::fields.standard.code'))->from('name')->unique('articles', 'code'),
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            NumberField::make('sort')->label(__('atrium::fields.standard.sort'))->default(500),
            ContentField::make('preview_text')->label(__('atrium::fields.standard.preview_text')),
            ContentField::make('detail_text')->label(__('atrium::fields.standard.detail_text')),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium::fields.standard.name'))->sortable()->searchable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
            DateColumn::make('updated_at')->label(__('atrium::fields.standard.updated_at'))->sortable(),
        ];
    }
}
