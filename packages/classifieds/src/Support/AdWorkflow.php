<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Admin\Support\Sites;
use Atrium\Classifieds\Board;
use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Enums\ComplaintReason;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Category;
use Atrium\Classifieds\Models\Complaint;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * The life of an ad, whoever moves it: the owner in the cabinet, a
 * moderator in the admin, the scheduler at night.
 *
 *   draft ──submit──▶ moderation ──approve──▶ active ──term──▶ expired
 *     ▲                  │  ▲                  │  ▲              │
 *     └──── rejected ◀───┘  └── complaints ────┘  └── renew ─────┘
 *                                              └──▶ sold
 *
 * With pre-moderation off an ad goes live at once, unless a stop word
 * holds it for a moderator.
 */
class AdWorkflow
{
    public function __construct(
        private readonly Board $board,
        private readonly StopWords $stopWords,
        private readonly Properties $properties,
    ) {}

    /**
     * Create or change an ad from the cabinet form. A new one is counted
     * against the owner's daily limit; a live one that is changed goes
     * through moderation again where the site moderates.
     *
     * @param  array<string,mixed>  $data  validated form data
     */
    public function save(Owner $owner, ?Ad $ad, array $data, bool $submit = false): Ad
    {
        if ($ad === null) {
            $this->hit('post', $owner->key(), $this->board->int('ads_per_day'), 86400);
            $ad = new Ad($owner->attributes());
        } else {
            $this->assertOwner($owner, $ad);

            if (! $ad->status->isEditable()) {
                throw ValidationException::withMessages(['ad' => __('atrium-classifieds::classifieds.errors.not_editable')]);
            }
        }

        $photos = array_values($data['photos'] ?? $ad->photos ?? []);

        $ad->fill([
            'title' => $data['title'],
            'text' => $data['text'] ?? null,
            'category_id' => (int) $data['category_id'],
            'city_id' => isset($data['city_id']) ? (int) $data['city_id'] : null,
            'price' => isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : null,
            'negotiable' => (bool) ($data['negotiable'] ?? false),
            'photos' => $photos === [] ? null : $photos,
            'location' => $data['location'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
        ]);
        $ad->code = '';

        $this->properties->write($ad, Category::query()->find($ad->category_id), (array) ($data['props'] ?? []));

        if ($ad->exists && $ad->status === AdStatus::Active && $ad->isDirty(['title', 'text', 'photos', 'price', 'category_id'])) {
            $this->review($ad);
        }

        if ($submit && $ad->status->canSubmit()) {
            $this->review($ad);
        }

        $ad->save();

        return $ad;
    }

    /**
     * Send a draft, a rejected or an expired ad for publishing.
     */
    public function submit(Owner $owner, Ad $ad): void
    {
        $this->assertOwner($owner, $ad);

        if (! $ad->status->canSubmit()) {
            throw ValidationException::withMessages(['ad' => __('atrium-classifieds::classifieds.errors.cannot_submit')]);
        }

        $this->review($ad);
        $ad->save();
    }

    public function approve(Ad $ad): void
    {
        $ad->status = AdStatus::Active;
        $ad->flags = null;
        $ad->save();
    }

    public function reject(Ad $ad, string $reason): void
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reject_reason' => __('atrium-classifieds::classifieds.errors.reason_required')]);
        }

        $ad->status = AdStatus::Rejected;
        $ad->reject_reason = trim($reason);
        $ad->save();
    }

    public function markSold(Owner $owner, Ad $ad): void
    {
        $this->assertOwner($owner, $ad);

        if (! in_array($ad->status, [AdStatus::Active, AdStatus::Expired], true)) {
            throw ValidationException::withMessages(['ad' => __('atrium-classifieds::classifieds.errors.cannot_sell')]);
        }

        $ad->status = AdStatus::Sold;
        $ad->save();
    }

    /**
     * Another term for a live or an expired ad, up to the site's number of
     * renewals. It was moderated already, so it goes live again at once.
     */
    public function renew(Owner $owner, Ad $ad): void
    {
        $this->assertOwner($owner, $ad);

        if (! in_array($ad->status, [AdStatus::Active, AdStatus::Expired], true)) {
            throw ValidationException::withMessages(['ad' => __('atrium-classifieds::classifieds.errors.cannot_renew')]);
        }

        if ($ad->renewals >= $this->board->int('renewals')) {
            throw ValidationException::withMessages(['ad' => __('atrium-classifieds::classifieds.errors.renewals_used')]);
        }

        $from = $ad->status === AdStatus::Active && $ad->expires_at?->isFuture() ? $ad->expires_at : now();
        $ad->expires_at = $from->copy()->addDays(max(1, $this->board->int('ttl_days')));
        $ad->status = AdStatus::Active;
        $ad->renewals++;
        $ad->save();
    }

    /**
     * Take ads past their term off the site. Returns how many.
     */
    public function expireDue(): int
    {
        $count = 0;

        Ad::query()->withoutGlobalScopes()
            ->where('status', AdStatus::Active->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(200, function ($ads) use (&$count): void {
                foreach ($ads as $ad) {
                    $ad->status = AdStatus::Expired;
                    $ad->save();
                    $count++;
                }
            });

        return $count;
    }

    /**
     * A complaint about a live ad. One person (by address or account)
     * counts once; enough people send the ad back to the moderators.
     */
    public function complain(Ad $ad, ?int $userId, string $ip, ComplaintReason $reason, ?string $text): Complaint
    {
        $this->hit('complain', $ip, 10, 3600);
        $fingerprint = hash('sha256', $userId !== null ? 'user:'.$userId : 'ip:'.$ip);

        return DB::transaction(function () use ($ad, $userId, $fingerprint, $reason, $text): Complaint {
            $complaint = Complaint::query()->firstOrCreate(
                ['ad_id' => $ad->id, 'fingerprint' => $fingerprint],
                ['user_id' => $userId, 'reason' => $reason, 'text' => $text !== null ? trim($text) : null],
            );

            $open = Complaint::query()->where('ad_id', $ad->id)->where('status', 'new')->count();

            if ($ad->status === AdStatus::Active && $open >= max(1, $this->board->int('complaints_to_review'))) {
                $ad->status = AdStatus::Moderation;
                $ad->flags = array_values(array_unique([...($ad->flags ?? []), 'complaints']));
                $ad->save();
            }

            return $complaint;
        });
    }

    /**
     * Count one more action against a limit, or refuse it with 429.
     */
    public function hit(string $action, string $who, int $max, int $seconds): void
    {
        $key = 'atrium-classifieds:'.$action.':'.app(Sites::class)->current()->id.':'.$who;

        if (RateLimiter::tooManyAttempts($key, max(1, $max))) {
            throw new ThrottleRequestsException(__('atrium-classifieds::classifieds.errors.too_many'));
        }

        RateLimiter::hit($key, $seconds);
    }

    public function assertOwner(Owner $owner, Ad $ad): void
    {
        abort_unless($owner->owns($ad), 403);
    }

    /**
     * Moderation or the site, by the site's rule and the stop words.
     */
    private function review(Ad $ad): void
    {
        $wasLive = $ad->status === AdStatus::Active;
        $flags = $this->stopWords->find((string) $ad->title, (string) $ad->text);
        $ad->flags = $flags === [] ? null : $flags;
        $ad->reject_reason = null;

        // A new term starts when it goes live; a live ad that was only
        // edited keeps the one it has.
        if (! $wasLive) {
            $ad->expires_at = null;
        }

        $ad->status = $this->board->option('premoderation') || $flags !== [] ? AdStatus::Moderation : AdStatus::Active;
    }
}
