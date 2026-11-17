<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Category;
use Atrium\Classifieds\Models\City;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * The board's search. Filters come from the query string or a saved
 * search, are cleaned by normalize() and applied by query():
 *
 *     category  id, with every subcategory
 *     region    id
 *     city      id
 *     price     {min, max}
 *     period    day | week | month: published within
 *     q         words of the title
 *     company   id: one company's ads
 *     props     code => value | [values] | {min, max}, the category's own
 *     sort      new | cheap | expensive
 *
 * VIP ads come first, then the most recently raised. A list page reads
 * ids only and draws cached cards (Cards), never the ads' properties.
 */
class AdSearch
{
    public const PERIODS = ['day' => 1, 'week' => 7, 'month' => 30];

    public const SORTS = ['new', 'cheap', 'expensive'];

    public function __construct(private readonly Properties $properties, private readonly Cards $cards) {}

    /**
     * Only known filters, with values of the right kind; empty ones dropped.
     *
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     */
    public function normalize(array $input): array
    {
        $filters = [];

        foreach (['category', 'region', 'city', 'company'] as $key) {
            if (isset($input[$key]) && is_numeric($input[$key]) && (int) $input[$key] > 0) {
                $filters[$key] = (int) $input[$key];
            }
        }

        $price = is_array($input['price'] ?? null) ? $input['price'] : [];
        $range = [];

        foreach (['min', 'max'] as $bound) {
            if (isset($price[$bound]) && is_numeric($price[$bound]) && (float) $price[$bound] >= 0) {
                $range[$bound] = (float) $price[$bound];
            }
        }

        if (isset($range['min'], $range['max']) && $range['min'] > $range['max']) {
            [$range['min'], $range['max']] = [$range['max'], $range['min']];
        }

        if ($range !== []) {
            $filters['price'] = $range;
        }

        if (is_string($input['period'] ?? null) && isset(self::PERIODS[$input['period']])) {
            $filters['period'] = $input['period'];
        }

        $words = is_string($input['q'] ?? null) ? trim(mb_substr($input['q'], 0, 100)) : '';

        if ($words !== '') {
            $filters['q'] = $words;
        }

        if (isset($filters['category']) && is_array($input['props'] ?? null)) {
            $props = $this->properties->filters(Category::query()->find($filters['category']), $input['props']);

            if ($props !== []) {
                $filters['props'] = $props;
            }
        }

        if (is_string($input['sort'] ?? null) && in_array($input['sort'], self::SORTS, true) && $input['sort'] !== 'new') {
            $filters['sort'] = $input['sort'];
        }

        return $filters;
    }

    /**
     * Published ads matching the filters, in the board's order.
     *
     * @param  array<string,mixed>  $filters  from normalize()
     * @return Builder<Ad>
     */
    public function query(array $filters): Builder
    {
        $query = $this->filtered($filters);

        return match ($filters['sort'] ?? 'new') {
            'cheap' => $query->orderByRaw('case when price is null then 1 else 0 end')->orderBy('price')->orderByDesc('id'),
            'expensive' => $query->orderByRaw('case when price is null then 1 else 0 end')->orderByDesc('price')->orderByDesc('id'),
            default => $query
                ->orderByRaw('case when vip_until > ? then 0 else 1 end', [now()])
                ->orderByDesc('raised_at')
                ->orderByDesc('id'),
        };
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return Builder<Ad>
     */
    public function filtered(array $filters): Builder
    {
        $query = Ad::query()->published();

        if (isset($filters['category'])) {
            $category = Category::query()->find($filters['category']);
            $query->whereIn('category_id', $category?->descendantIds() ?? [0]);
        }

        if (isset($filters['city'])) {
            $query->where('city_id', $filters['city']);
        } elseif (isset($filters['region'])) {
            $query->whereIn('city_id', City::query()->where('region_id', $filters['region'])->select('id'));
        }

        if (isset($filters['company'])) {
            $query->where('account_company_id', $filters['company']);
        }

        if (isset($filters['price']['min'])) {
            $query->where('price', '>=', $filters['price']['min']);
        }

        if (isset($filters['price']['max'])) {
            $query->where('price', '<=', $filters['price']['max']);
        }

        if (isset($filters['period'])) {
            $query->where('published_at', '>=', now()->subDays(self::PERIODS[$filters['period']]));
        }

        if (isset($filters['q'])) {
            foreach (preg_split('/\s+/u', $filters['q']) ?: [] as $word) {
                // "!" escapes the wildcards the same way in every database.
                $query->whereRaw("title like ? escape '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $word).'%']);
            }
        }

        if (isset($filters['props'])) {
            $this->properties->apply($query, $filters['props']);
        }

        return $query;
    }

    /**
     * A page of cards.
     *
     * @param  array<string,mixed>  $filters
     * @return LengthAwarePaginator<int,array<string,mixed>>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $page = $this->query($filters)->select(['id', 'updated_at'])->paginate(max(1, $perPage))->withQueryString();
        $cards = $this->cards->many($page->getCollection()->all());

        $page->setCollection(collect($cards));

        return $page;
    }

    /**
     * Ads published after the given id that match: for saved-search alerts.
     *
     * @param  array<string,mixed>  $filters
     * @return array<int,array<string,mixed>>
     */
    public function newSince(array $filters, int $afterId, int $limit): array
    {
        $ids = $this->filtered($filters)->where('id', '>', $afterId)->orderBy('id')->limit($limit)->get(['id', 'updated_at'])->all();

        return $this->cards->many($ids);
    }
}
