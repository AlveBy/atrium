<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Classifieds\Models\Ad;
use Illuminate\Support\Facades\Cache;

/**
 * What a list shows of an ad — title, price, city, first photo, dates and
 * flags — kept in the cache under the ad's id, last change and language.
 * A list reads ids and updated_at, then the cards: one query for those
 * not cached yet, none for the rest. Saving an ad changes its key.
 */
class Cards
{
    /**
     * @param  array<int,Ad>  $ads  with at least id and updated_at
     * @return array<int,array<string,mixed>>
     */
    public function many(array $ads): array
    {
        if ($ads === []) {
            return [];
        }

        $keys = [];

        foreach ($ads as $ad) {
            $keys[$ad->id] = $this->key($ad);
        }

        $cached = Cache::many(array_values($keys));
        $missing = array_keys(array_filter($keys, static fn (string $key): bool => ! is_array($cached[$key] ?? null)));

        if ($missing !== []) {
            $fresh = Ad::query()->withoutGlobalScopes()->with('city')->whereIn('id', $missing)
                ->get(['id', 'title', 'code', 'price', 'negotiable', 'currency', 'photos', 'city_id', 'published_at', 'raised_at', 'highlighted_until', 'vip_until', 'updated_at']);
            $put = [];

            foreach ($fresh as $ad) {
                $put[$keys[$ad->id]] = $cached[$keys[$ad->id]] = $this->card($ad);
            }

            Cache::putMany($put, (int) config('atrium-classifieds.card_ttl', 3600));
        }

        $cards = [];

        foreach ($keys as $key) {
            if (is_array($cached[$key] ?? null)) {
                $cards[] = $cached[$key];
            }
        }

        return $cards;
    }

    /**
     * @return array<string,mixed>
     */
    public function card(Ad $ad): array
    {
        return [
            'id' => $ad->id,
            'title' => $ad->title,
            'url' => $ad->url(),
            'price' => $ad->priceText(),
            'city' => $ad->city?->name,
            'photo' => $ad->photoUrl(),
            'published_at' => $ad->published_at?->toIso8601String(),
            'vip_until' => $ad->vip_until?->getTimestamp(),
            'highlighted_until' => $ad->highlighted_until?->getTimestamp(),
        ];
    }

    private function key(Ad $ad): string
    {
        return 'atrium-classifieds:card:'.$ad->id.':'.$ad->updated_at?->getTimestamp().':'.app()->getLocale();
    }
}
