<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Classifieds\Board;
use Atrium\Classifieds\Enums\BoostType;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Boost;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Paid placement: ordering, paying and applying a boost.
 *
 *     $boost = $boosts->order($owner, $ad, BoostType::Vip);  // pending
 *     $url = $boosts->checkoutUrl($boost);                    // the shop's cart, or null
 *     $boosts->paid($boost, 'A-1042');                        // from the shop, or the admin
 */
class Boosts
{
    public function __construct(private readonly Board $board) {}

    /**
     * The price and the term of a boost on the current site.
     *
     * @return array{amount:float,days:int,currency:string}
     */
    public function offer(BoostType $type): array
    {
        return [
            'amount' => max(0.0, (float) $this->board->option($type->value.'_price')),
            'days' => $type === BoostType::Raise ? 1 : max(1, $this->board->int($type->value.'_days')),
            'currency' => $this->board->currency(),
        ];
    }

    public function order(Owner $owner, Ad $ad, BoostType $type): Boost
    {
        abort_unless($owner->owns($ad), 403);

        if (! $ad->isPublished()) {
            throw ValidationException::withMessages(['ad' => __('atrium-classifieds::classifieds.errors.not_published')]);
        }

        $offer = $this->offer($type);

        $boost = Boost::query()->create([
            'ad_id' => $ad->id,
            'account_user_id' => $owner->userId,
            'type' => $type,
            'days' => $offer['days'],
            'amount' => $offer['amount'],
            'currency' => $offer['currency'],
        ]);

        // Free on this site: no payment to wait for.
        if ($offer['amount'] <= 0) {
            $this->paid($boost, null);
        }

        return $boost;
    }

    public function checkoutUrl(Boost $boost): ?string
    {
        if ($boost->status !== Boost::PENDING || ! app()->bound(Checkout::class)) {
            return null;
        }

        return app(Checkout::class)->start($boost);
    }

    /**
     * The boost of a shop reference "classifieds:boost:{id}".
     */
    public function find(string $reference): ?Boost
    {
        if (preg_match('/^classifieds:boost:(\d+)$/', $reference, $match) !== 1) {
            return null;
        }

        return Boost::query()->find((int) $match[1]);
    }

    public function paid(Boost $boost, ?string $reference): void
    {
        if ($boost->status !== Boost::PENDING) {
            return;
        }

        $boost->reference = $reference ?? $boost->reference;
        $boost->status = Boost::PAID;
        $boost->save();
    }

    public function cancel(Boost $boost): void
    {
        if ($boost->status === Boost::PENDING) {
            $boost->status = Boost::CANCELLED;
            $boost->save();
        }
    }

    /**
     * Make a paid boost work on its ad. VIP raises the ad too.
     */
    public function apply(Boost $boost): void
    {
        $ad = Ad::query()->withoutGlobalScopes()->find($boost->ad_id);

        if ($boost->paid_at === null) {
            $boost->paid_at = now();
            $boost->saveQuietly();
        }

        if ($ad === null) {
            return;
        }

        $extend = static fn (?Carbon $until): Carbon => ($until !== null && $until->isFuture() ? $until->copy() : now())->addDays($boost->days);

        match ($boost->type) {
            BoostType::Raise => $ad->raised_at = now(),
            BoostType::Highlight => $ad->highlighted_until = $extend($ad->highlighted_until),
            BoostType::Vip => [$ad->vip_until = $extend($ad->vip_until), $ad->raised_at = now()],
        };

        $ad->save();
    }
}
