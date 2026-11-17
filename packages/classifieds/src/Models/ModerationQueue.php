<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Models;

use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Classifieds\Enums\AdStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * The moderators' queue: the same ads, only those waiting, oldest first,
 * with the stop words that held them and the complaints they collected.
 * Approving is picking "Active" in the status, rejecting is "Rejected"
 * with a reason the owner reads in the cabinet.
 */
class ModerationQueue extends Ad
{
    /** @var array<int,string> */
    protected $appends = ['status_label', 'flags_text'];

    /**
     * Custom fields and the index know the ad by the Ad class.
     */
    public function getMorphClass(): string
    {
        return Ad::class;
    }

    public function scopeAdminList(Builder $query): Builder
    {
        return $query->where('status', AdStatus::Moderation->value)->withCount('complaints');
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-classifieds::classifieds.modules.moderation');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'classified_moderation';
    }

    public static function getDefaultSortColumn(): string
    {
        return 'updated_at';
    }

    public static function getDefaultSortDirection(): string
    {
        return 'asc';
    }

    public static function isCreatableInAdmin(): bool
    {
        return false;
    }

    public function getFlagsTextAttribute(): string
    {
        return implode(', ', $this->flags ?? []);
    }

    public static function getAdministrationListColumns(): array
    {
        $field = static fn (string $key): string => __('atrium-classifieds::classifieds.fields.'.$key);

        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('title')->label($field('title'))->searchable(),
            TextColumn::make('category.name')->label($field('category')),
            TextColumn::make('flags_text')->label($field('flags')),
            TextColumn::make('complaints_count')->label($field('complaints')),
            DateColumn::make('updated_at')->label($field('waiting_since'))->sortable(),
        ];
    }
}
