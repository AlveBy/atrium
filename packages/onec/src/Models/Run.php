<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Models;

use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Filters\SelectFilter;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One file 1C sent (import.xml or offers.xml) and what came of it: how many
 * sections, products and offers were made or changed, how many errors. The
 * exchange makes these rows; the admin only reads and deletes them.
 *
 * @property int $id
 * @property string $file
 * @property string $status
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property int $sections_created
 * @property int $sections_updated
 * @property int $products_created
 * @property int $products_updated
 * @property int $offers_updated
 * @property int $skipped
 * @property int $errors
 * @property string|null $error
 * @property-read string $status_label
 */
class Run extends Model implements Administrable
{
    use InteractsWithAdmin;

    public const RUNNING = 'running';

    public const DONE = 'done';

    public const FAILED = 'failed';

    protected $table = 'onec_runs';

    protected $guarded = ['id'];

    /** @var array<string,int> */
    protected $attributes = [
        'sections_created' => 0, 'sections_updated' => 0, 'products_created' => 0, 'products_updated' => 0,
        'offers_updated' => 0, 'skipped' => 0, 'errors' => 0,
    ];

    /** @var list<string> */
    protected $appends = ['status_label'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'sections_created' => 'integer',
            'sections_updated' => 'integer',
            'products_created' => 'integer',
            'products_updated' => 'integer',
            'offers_updated' => 'integer',
            'skipped' => 'integer',
            'errors' => 'integer',
        ];
    }

    /** @return Attribute<string,never> */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => __('atrium-1c::onec.status.'.$this->status));
    }

    public static function isCreatableInAdmin(): bool
    {
        return false;
    }

    public static function isEditableInAdmin(): bool
    {
        return false;
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-1c::onec.title_runs');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'onec_runs';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'id';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'desc';
    }

    public static function getAdministrationFields(): array
    {
        $fields = [];

        foreach (['started_at', 'finished_at', 'file', 'status_label', 'sections_created', 'sections_updated', 'products_created', 'products_updated', 'offers_updated', 'skipped', 'errors'] as $name) {
            $fields[] = TextField::make($name)->label(__('atrium-1c::onec.fields.'.$name))->readonly()->hideOnCreate();
        }

        $fields[] = TextareaField::make('error')->label(__('atrium-1c::onec.fields.error'))->rows(6)->readonly()->hideOnCreate();

        return $fields;
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            DateColumn::make('started_at')->label(__('atrium-1c::onec.fields.started_at'))->sortable(),
            TextColumn::make('file')->label(__('atrium-1c::onec.fields.file'))->searchable(),
            TextColumn::make('status_label')->label(__('atrium-1c::onec.fields.status_label')),
            TextColumn::make('products_created')->label(__('atrium-1c::onec.fields.products_created')),
            TextColumn::make('products_updated')->label(__('atrium-1c::onec.fields.products_updated')),
            TextColumn::make('offers_updated')->label(__('atrium-1c::onec.fields.offers_updated')),
            TextColumn::make('errors')->label(__('atrium-1c::onec.fields.errors')),
        ];
    }

    public static function getAdministrationFilters(): array
    {
        return [
            SelectFilter::make('status')->label(__('atrium-1c::onec.fields.status_label'))->options([
                self::RUNNING => __('atrium-1c::onec.status.running'),
                self::DONE => __('atrium-1c::onec.status.done'),
                self::FAILED => __('atrium-1c::onec.status.failed'),
            ]),
        ];
    }
}
