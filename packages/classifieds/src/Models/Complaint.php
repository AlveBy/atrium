<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Models;

use Atrium\Admin\Columns\DateColumn;
use Atrium\Admin\Columns\TextColumn;
use Atrium\Admin\Contracts\Administrable;
use Atrium\Admin\Fields\DisplayField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Classifieds\Enums\ComplaintReason;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A visitor's complaint about an ad. Enough of them from different people
 * send the ad back to the moderation queue; the moderator then closes them.
 *
 * @property int $id
 * @property int $ad_id
 * @property int|null $user_id
 * @property string $fingerprint
 * @property ComplaintReason $reason
 * @property string|null $text
 * @property string $status
 * @property-read Ad|null $ad
 */
class Complaint extends Model implements Administrable
{
    use InteractsWithAdmin;

    public const STATUSES = ['new', 'resolved', 'dismissed'];

    protected $table = 'classified_complaints';

    protected $fillable = ['ad_id', 'user_id', 'fingerprint', 'reason', 'text', 'status'];

    /** @var array<int,string> */
    protected $appends = ['reason_label', 'status_label', 'ad_title'];

    /** @var array<string,mixed> */
    protected $attributes = ['status' => 'new'];

    protected function casts(): array
    {
        return ['ad_id' => 'integer', 'user_id' => 'integer', 'reason' => ComplaintReason::class];
    }

    /**
     * @return BelongsTo<Ad,$this>
     */
    public function ad(): BelongsTo
    {
        // The ad of another site is still the ad complained about.
        return $this->belongsTo(Ad::class)->withoutGlobalScopes();
    }

    /**
     * Complaints about ads of the site picked in the admin header.
     */
    public function scopeAdminList(Builder $query): Builder
    {
        return $query->whereIn('ad_id', Ad::query()->select('id'));
    }

    public function getReasonLabelAttribute(): string
    {
        return $this->reason->label();
    }

    public function getStatusLabelAttribute(): string
    {
        return __('atrium-classifieds::classifieds.complaint.statuses.'.$this->status);
    }

    public function getAdTitleAttribute(): string
    {
        return (string) $this->ad?->title;
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-classifieds::classifieds.modules.complaints');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'classified_complaints';
    }

    public static function isCreatableInAdmin(): bool
    {
        return false;
    }

    public static function getAdministrationFields(): array
    {
        $field = static fn (string $key): string => __('atrium-classifieds::classifieds.fields.'.$key);
        $statuses = [];

        foreach (self::STATUSES as $status) {
            $statuses[$status] = __('atrium-classifieds::classifieds.complaint.statuses.'.$status);
        }

        return [
            DisplayField::make('ad_title')->label($field('ad'))->html(static fn (self $complaint): string => e($complaint->ad_title)),
            DisplayField::make('reason_label')->label($field('reason'))->html(static fn (self $complaint): string => e($complaint->reason_label)),
            DisplayField::make('text')->label($field('text'))->html(static fn (self $complaint): string => nl2br(e((string) $complaint->text))),
            SelectField::make('status')->label($field('status'))->options($statuses)->required(),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        $field = static fn (string $key): string => __('atrium-classifieds::classifieds.fields.'.$key);

        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('ad_title')->label($field('ad')),
            TextColumn::make('reason_label')->label($field('reason')),
            TextColumn::make('status_label')->label($field('status')),
            DateColumn::make('created_at')->label(__('atrium::fields.standard.created_at'))->sortable(),
        ];
    }
}
