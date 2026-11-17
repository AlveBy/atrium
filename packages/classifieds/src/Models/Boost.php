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
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Classifieds\Enums\BoostType;
use Atrium\Classifieds\Support\Boosts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A paid placement ordered for an ad. It waits as "pending" until paid —
 * through the shop's checkout, or marked paid here by an administrator
 * where there is no shop — and then takes effect at once.
 *
 * @property int $id
 * @property int $ad_id
 * @property int $account_user_id
 * @property BoostType $type
 * @property int $days
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property string|null $reference
 * @property Carbon|null $paid_at
 * @property-read Ad|null $ad
 */
class Boost extends Model implements Administrable
{
    use InteractsWithAdmin;

    public const PENDING = 'pending';

    public const PAID = 'paid';

    public const CANCELLED = 'cancelled';

    protected $table = 'classified_boosts';

    protected $fillable = ['ad_id', 'account_user_id', 'type', 'days', 'amount', 'currency', 'status', 'reference', 'paid_at'];

    /** @var array<int,string> */
    protected $appends = ['type_label', 'status_label', 'ad_title'];

    /** @var array<string,mixed> */
    protected $attributes = ['status' => self::PENDING];

    protected function casts(): array
    {
        return ['ad_id' => 'integer', 'account_user_id' => 'integer', 'type' => BoostType::class, 'days' => 'integer', 'amount' => 'float', 'paid_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Marked paid by hand in the admin: it works the same as a payment.
        static::updated(static function (self $boost): void {
            if ($boost->wasChanged('status') && $boost->status === self::PAID && $boost->getOriginal('status') === self::PENDING) {
                app(Boosts::class)->apply($boost);
            }
        });
    }

    /**
     * @return BelongsTo<Ad,$this>
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class)->withoutGlobalScopes();
    }

    public function scopeAdminList(Builder $query): Builder
    {
        return $query->whereIn('ad_id', Ad::query()->select('id'));
    }

    /** The reference the shop's cart knows it by. */
    public function cartReference(): string
    {
        return 'classifieds:boost:'.$this->id;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type->label();
    }

    public function getStatusLabelAttribute(): string
    {
        return __('atrium-classifieds::classifieds.boost.statuses.'.$this->status);
    }

    public function getAdTitleAttribute(): string
    {
        return (string) $this->ad?->title;
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-classifieds::classifieds.modules.boosts');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'classified_boosts';
    }

    public static function isCreatableInAdmin(): bool
    {
        return false;
    }

    public static function getAdministrationFields(): array
    {
        $field = static fn (string $key): string => __('atrium-classifieds::classifieds.fields.'.$key);
        $statuses = [];

        foreach ([self::PENDING, self::PAID, self::CANCELLED] as $status) {
            $statuses[$status] = __('atrium-classifieds::classifieds.boost.statuses.'.$status);
        }

        return [
            DisplayField::make('ad_title')->label($field('ad'))->html(static fn (self $boost): string => e($boost->ad_title)),
            DisplayField::make('type_label')->label($field('boost'))->html(static fn (self $boost): string => e($boost->type_label.' · '.$boost->days.' · '.$boost->amount.' '.$boost->currency)),
            SelectField::make('status')->label($field('status'))->options($statuses)->required(),
            TextField::make('reference')->label($field('reference'))->rules(['nullable', 'max:100']),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        $field = static fn (string $key): string => __('atrium-classifieds::classifieds.fields.'.$key);

        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('ad_title')->label($field('ad')),
            TextColumn::make('type_label')->label($field('boost')),
            TextColumn::make('amount')->label($field('price'))->sortable(),
            TextColumn::make('status_label')->label($field('status')),
            DateColumn::make('created_at')->label(__('atrium::fields.standard.created_at'))->sortable(),
        ];
    }
}
