<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Models;

use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Filters\SelectFilter;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Integrations\Enums\Direction;
use Atrium\Integrations\Enums\Status;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One line of the exchange log: when a connector did something, what, to
 * what, how it ended and, if it did not, why. Written by the connectors
 * through Atrium\Integrations\Exchange; the admin only reads and deletes.
 *
 * @property int $id
 * @property string $integration
 * @property string $direction
 * @property string $action
 * @property string $status
 * @property string|null $subject
 * @property string|null $message
 * @property string|null $error
 * @property int $attempt
 * @property int|null $duration_ms
 * @property Carbon|null $created_at
 * @property-read string $status_label
 * @property-read string $direction_label
 */
class ExchangeLog extends Model implements Administrable
{
    use InteractsWithAdmin;

    public const UPDATED_AT = null;

    protected $table = 'integration_logs';

    protected $guarded = ['id'];

    /** @var list<string> */
    protected $appends = ['status_label', 'direction_label'];

    protected function casts(): array
    {
        return [
            'attempt' => 'integer',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /** @return Attribute<string,never> */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn (): string => Status::tryFrom($this->status)?->label() ?? $this->status);
    }

    /** @return Attribute<string,never> */
    protected function directionLabel(): Attribute
    {
        return Attribute::get(fn (): string => Direction::tryFrom($this->direction)?->label() ?? $this->direction);
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
        return __('atrium-integrations::integrations.title_log');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'integration_logs';
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
        return [
            TextField::make('created_at')->label(__('atrium-integrations::integrations.fields.when'))->readonly()->hideOnCreate(),
            TextField::make('integration')->label(__('atrium-integrations::integrations.fields.integration'))->readonly()->hideOnCreate(),
            TextField::make('direction_label')->label(__('atrium-integrations::integrations.fields.direction'))->readonly()->hideOnCreate(),
            TextField::make('action')->label(__('atrium-integrations::integrations.fields.action'))->readonly()->hideOnCreate(),
            TextField::make('subject')->label(__('atrium-integrations::integrations.fields.subject'))->readonly()->hideOnCreate(),
            TextField::make('status_label')->label(__('atrium-integrations::integrations.fields.status'))->readonly()->hideOnCreate(),
            TextareaField::make('message')->label(__('atrium-integrations::integrations.fields.message'))->rows(6)->readonly()->hideOnCreate(),
            TextareaField::make('error')->label(__('atrium-integrations::integrations.fields.error'))->rows(6)->readonly()->hideOnCreate(),
            TextField::make('attempt')->label(__('atrium-integrations::integrations.fields.attempt'))->readonly()->hideOnCreate(),
            TextField::make('duration_ms')->label(__('atrium-integrations::integrations.fields.duration'))->readonly()->hideOnCreate(),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            DateColumn::make('created_at')->label(__('atrium-integrations::integrations.fields.when'))->sortable(),
            TextColumn::make('integration')->label(__('atrium-integrations::integrations.fields.integration'))->sortable()->searchable(),
            TextColumn::make('action')->label(__('atrium-integrations::integrations.fields.action'))->searchable(),
            TextColumn::make('subject')->label(__('atrium-integrations::integrations.fields.subject'))->searchable(),
            TextColumn::make('status_label')->label(__('atrium-integrations::integrations.fields.status')),
            TextColumn::make('error')->label(__('atrium-integrations::integrations.fields.error'))->limit(80),
        ];
    }

    public static function getAdministrationFilters(): array
    {
        return [
            SelectFilter::make('status')->label(__('atrium-integrations::integrations.fields.status'))
                ->options(array_combine(
                    array_column(Status::cases(), 'value'),
                    array_map(static fn (Status $status): string => $status->label(), Status::cases()),
                )),
            SelectFilter::make('integration')->label(__('atrium-integrations::integrations.fields.integration'))
                ->options(fn (): array => self::query()->distinct()->orderBy('integration')->pluck('integration', 'integration')->all()),
        ];
    }
}
