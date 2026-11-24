<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks\Models;

use Atrium\Admin\Columns\BooleanColumn;
use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\ContentField;
use Atrium\Admin\Fields\SlugField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Admin\Traits\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * A piece of text or HTML written once and shown wherever it is put: the
 * phone number in the header and in the footer, a line about delivery, a
 * banner. The text is written per content language; the name is for the
 * editors only. A block belongs to a site.
 *
 * @property int $id
 * @property int|null $site_id
 * @property string $name
 * @property string $code
 * @property bool $active
 */
class Block extends Model implements Administrable
{
    use BelongsToSite, InteractsWithAdmin, Translatable;

    protected $table = 'blocks';

    /** @var array<int,string> */
    protected array $translatable = ['content'];

    protected $fillable = ['site_id', 'name', 'code', 'content', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-blocks::blocks.modules.blocks');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-blocks::blocks.create.block');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'blocks';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'name';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public static function getAdministrationFields(): array
    {
        return [
            CheckboxField::make('active')->label(__('atrium::fields.standard.active'))->default(true),
            TextField::make('name')->label(__('atrium-blocks::blocks.fields.name'))->required(),
            SlugField::make('code')
                ->label(__('atrium::fields.standard.code'))
                ->from('name')
                ->hint(__('atrium-blocks::blocks.fields.code_hint'))
                ->unique('blocks', 'code'),
            ContentField::make('content')->label(__('atrium-blocks::blocks.fields.content')),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('name')->label(__('atrium-blocks::blocks.fields.name'))->sortable()->searchable(),
            TextColumn::make('code')->label(__('atrium::fields.standard.code'))->sortable()->searchable(),
            BooleanColumn::make('active')->label(__('atrium::fields.standard.active')),
            DateColumn::make('updated_at')->label(__('atrium-blocks::blocks.fields.updated'))->sortable(),
        ];
    }
}
