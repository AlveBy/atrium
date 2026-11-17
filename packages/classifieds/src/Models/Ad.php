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
use Atrium\Admin\Contracts\Sitemapable;
use Atrium\Admin\Fields\BelongsToField;
use Atrium\Admin\Fields\CheckboxField;
use Atrium\Admin\Fields\DateTimeField;
use Atrium\Admin\Fields\GalleryField;
use Atrium\Admin\Fields\MapField;
use Atrium\Admin\Fields\NumberField;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Fields\TextareaField;
use Atrium\Admin\Fields\TextField;
use Atrium\Admin\Fields\TreeSelectField;
use Atrium\Admin\Support\MediaUrl;
use Atrium\Admin\Traits\BelongsToSite;
use Atrium\Admin\Traits\HasAdminTabs;
use Atrium\Admin\Traits\InteractsWithAdmin;
use Atrium\Classifieds\Board;
use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Support\PersonalData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An ad. Only an active one is on the site; its owner (a person, or a
 * company and all its members) sees every status in the cabinet.
 *
 * The phone and the e-mail are personal data: encrypted in the table and
 * shown one at a time, on a click that is rate limited.
 *
 * @property int $id
 * @property int|null $site_id
 * @property int $category_id
 * @property int|null $city_id
 * @property int $account_user_id
 * @property int|null $account_company_id
 * @property string $title
 * @property string $code
 * @property string|null $text
 * @property float|null $price
 * @property bool $negotiable
 * @property string|null $currency
 * @property array<int,string>|null $photos
 * @property array<string,mixed>|null $location
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property AdStatus $status
 * @property string|null $reject_reason
 * @property array<int,string>|null $flags
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property int $renewals
 * @property Carbon|null $raised_at
 * @property Carbon|null $highlighted_until
 * @property Carbon|null $vip_until
 * @property int $views
 * @property int $phone_views
 * @property array<string,mixed>|null $custom
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category|null $category
 * @property-read City|null $city
 */
class Ad extends Model implements Administrable, Sitemapable
{
    use BelongsToSite, HasAdminTabs, InteractsWithAdmin;

    protected $table = 'classified_ads';

    protected $fillable = [
        'site_id', 'category_id', 'city_id', 'account_user_id', 'account_company_id', 'title', 'code', 'text',
        'price', 'negotiable', 'currency', 'photos', 'location', 'contact_name', 'contact_phone', 'contact_email',
        'status', 'reject_reason', 'published_at', 'expires_at', 'raised_at', 'highlighted_until', 'vip_until',
    ];

    /** @var array<int,string> */
    protected $appends = ['status_label'];

    /** @var array<string,mixed> */
    protected $attributes = ['status' => 'draft', 'negotiable' => false, 'views' => 0, 'phone_views' => 0, 'renewals' => 0];

    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'city_id' => 'integer',
            'account_user_id' => 'integer',
            'account_company_id' => 'integer',
            'price' => 'float',
            'negotiable' => 'boolean',
            'photos' => 'array',
            'location' => 'array',
            'flags' => 'array',
            'custom' => 'array',
            'contact_phone' => PersonalData::CAST,
            'contact_email' => PersonalData::CAST,
            'status' => AdStatus::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'raised_at' => 'datetime',
            'highlighted_until' => 'datetime',
            'vip_until' => 'datetime',
            'renewals' => 'integer',
            'views' => 'integer',
            'phone_views' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(static function (self $ad): void {
            if (trim((string) $ad->code) === '') {
                $ad->code = Str::limit(Str::slug($ad->title), 120, '') ?: 'ad';
            }

            $ad->currency = $ad->currency ?: app(Board::class)->currency();

            // Going live (from the cabinet, the queue or the form): it gets
            // its date and its term unless they were set by hand.
            if ($ad->status === AdStatus::Active) {
                $ad->published_at ??= now();
                $ad->raised_at ??= $ad->published_at;
                $ad->expires_at ??= now()->addDays(max(1, app(Board::class)->int('ttl_days')));
                $ad->reject_reason = null;
            }
        });
    }

    /**
     * @return BelongsTo<Category,$this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<City,$this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<Complaint,$this>
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'ad_id');
    }

    /**
     * @return HasMany<Message,$this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'ad_id');
    }

    /**
     * Ads a visitor may see: active and not past their term.
     *
     * @param  Builder<Ad>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where($query->qualifyColumn('status'), AdStatus::Active->value)
            ->where(static fn (Builder $term) => $term->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === AdStatus::Active && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isVip(): bool
    {
        return $this->vip_until !== null && $this->vip_until->isFuture();
    }

    public function isHighlighted(): bool
    {
        return $this->highlighted_until !== null && $this->highlighted_until->isFuture();
    }

    public function priceText(): string
    {
        return app(Board::class)->price($this->price, $this->negotiable, $this->currency);
    }

    public function photoUrl(int $index = 0): ?string
    {
        $path = $this->photos[$index] ?? null;

        return is_string($path) && $path !== '' ? MediaUrl::to($path) : null;
    }

    /**
     * @return array<int,string>
     */
    public function photoUrls(): array
    {
        return array_values(array_filter(array_map(static fn ($path): ?string => is_string($path) && $path !== '' ? MediaUrl::to($path) : null, $this->photos ?? [])));
    }

    public function lat(): ?float
    {
        $lat = $this->location['lat'] ?? null;

        return is_numeric($lat) ? (float) $lat : null;
    }

    public function lng(): ?float
    {
        $lng = $this->location['lng'] ?? null;

        return is_numeric($lng) ? (float) $lng : null;
    }

    public function url(?string $language = null): string
    {
        return app(Board::class)->route('ad', ['ad' => $this->id, 'slug' => $this->code], $language);
    }

    public static function sitemapQuery(): Builder
    {
        return static::query()->published();
    }

    public function sitemapUrl(string $language): ?string
    {
        return $this->url($language);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public static function getAdminLabel(): string
    {
        return __('atrium-classifieds::classifieds.modules.ads');
    }

    public static function getAdminCreateLabel(): string
    {
        return __('atrium-classifieds::classifieds.create.ad');
    }

    public static function getAdminRoutePrefix(): string
    {
        return 'classified_ads';
    }

    public function getAdminTitle(): string
    {
        return $this->title;
    }

    public static function getAdminTabs(): array
    {
        return [
            ['name' => 'params', 'label' => __('atrium-classifieds::classifieds.tabs.ad')],
            ['name' => 'contacts', 'label' => __('atrium-classifieds::classifieds.tabs.contacts')],
            ['name' => 'moderation', 'label' => __('atrium-classifieds::classifieds.tabs.moderation')],
            ['name' => 'placement', 'label' => __('atrium-classifieds::classifieds.tabs.placement')],
        ];
    }

    public static function getAdministrationFields(): array
    {
        $field = static fn (string $key): string => __('atrium-classifieds::classifieds.fields.'.$key);

        return [
            TextField::make('title')->label($field('title'))->required()->rules(['max:150']),
            TreeSelectField::make('category_id')->label($field('category'))->model(Category::class)->required(),
            BelongsToField::make('city_id')->label($field('city'))->model(City::class),
            TextareaField::make('text')->label($field('text'))->rows(8)->maxLength(5000),
            NumberField::make('price')->label($field('price'))->min(0)->decimals(2),
            CheckboxField::make('negotiable')->label($field('negotiable')),
            TextField::make('currency')->label($field('currency'))->rules(['nullable', 'size:3']),
            GalleryField::make('photos')->label($field('photos'))->directory('classifieds')->maxFiles(max(1, (int) config('atrium-classifieds.defaults.max_photos', 10))),
            MapField::make('location')->label($field('location')),

            TextField::make('contact_name')->label($field('contact_name'))->tab('contacts'),
            TextField::make('contact_phone')->label($field('contact_phone'))->rules(['nullable', 'max:40'])->tab('contacts'),
            TextField::make('contact_email')->label($field('contact_email'))->rules(['nullable', 'email', 'max:191'])->tab('contacts'),
            NumberField::make('account_user_id')->label($field('owner'))->required()->min(1)->tab('contacts'),
            NumberField::make('account_company_id')->label($field('company'))->min(1)->tab('contacts'),

            SelectField::make('status')->label($field('status'))->options(AdStatus::options())->required()->tab('moderation'),
            TextareaField::make('reject_reason')->label($field('reject_reason'))->rows(3)
                ->rules(['required_if:status,rejected', 'nullable', 'max:1000'])->tab('moderation'),
            DateTimeField::make('published_at')->label($field('published_at'))->tab('moderation'),
            DateTimeField::make('expires_at')->label($field('expires_at'))->tab('moderation'),

            DateTimeField::make('raised_at')->label($field('raised_at'))->tab('placement'),
            DateTimeField::make('highlighted_until')->label($field('highlighted_until'))->tab('placement'),
            DateTimeField::make('vip_until')->label($field('vip_until'))->tab('placement'),
        ];
    }

    public static function getAdministrationListColumns(): array
    {
        $field = static fn (string $key): string => __('atrium-classifieds::classifieds.fields.'.$key);

        return [
            TextColumn::make('id')->label(__('atrium::fields.standard.id'))->sortable(),
            TextColumn::make('title')->label($field('title'))->sortable()->searchable(),
            TextColumn::make('category.name')->label($field('category')),
            TextColumn::make('city.name')->label($field('city')),
            TextColumn::make('price')->label($field('price'))->sortable(),
            TextColumn::make('status_label')->label($field('status')),
            DateColumn::make('expires_at')->label($field('expires_at'))->sortable(),
            DateColumn::make('created_at')->label(__('atrium::fields.standard.created_at'))->sortable(),
        ];
    }
}
