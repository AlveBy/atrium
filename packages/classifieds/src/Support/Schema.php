<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Classifieds\Models\Ad;
use Illuminate\Database\Eloquent\Model;

/**
 * schema.org for the board's pages: an ad is a Product with an Offer, a
 * list is an ItemList, the way down is a BreadcrumbList.
 *
 * With the SEO module installed the nodes go to it (one @graph per page,
 * next to the site's Organization); without it the page prints them in its
 * own <script type="application/ld+json">.
 */
class Schema
{
    public const SEO = 'alveby/atrium-seo';

    private ?SeoTarget $seo = null;

    public function useSeo(?SeoTarget $seo): void
    {
        $this->seo = $seo;
    }

    /**
     * Hand the page to the SEO module, or return the JSON to print.
     *
     * @param  array<int,array{label:string,url:string}>  $breadcrumbs
     * @param  array<int,array<string,mixed>>  $nodes
     */
    public function emit(?Model $record, array $breadcrumbs, array $nodes): ?string
    {
        if ($this->seo !== null) {
            $this->seo->page($record, $record !== null, $breadcrumbs, $nodes);

            return null;
        }

        $graph = [...$nodes];

        if ($breadcrumbs !== []) {
            $graph[] = $this->breadcrumbs($breadcrumbs);
        }

        return json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?: null;
    }

    /**
     * @return array<string,mixed>
     */
    public function ad(Ad $ad): array
    {
        $offer = array_filter([
            '@type' => 'Offer',
            'url' => $ad->url(),
            'price' => $ad->price !== null && ! $ad->negotiable ? number_format($ad->price, 2, '.', '') : null,
            'priceCurrency' => $ad->price !== null && ! $ad->negotiable ? $ad->currency : null,
            'availability' => $ad->isPublished() ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
            'validFrom' => $ad->published_at?->toIso8601String(),
            'priceValidUntil' => $ad->expires_at?->toDateString(),
            'availableAtOrFrom' => $ad->city !== null ? array_filter([
                '@type' => 'Place',
                'name' => (string) $ad->city->name,
                'geo' => $ad->lat() !== null && $ad->lng() !== null
                    ? ['@type' => 'GeoCoordinates', 'latitude' => $ad->lat(), 'longitude' => $ad->lng()]
                    : null,
            ]) : null,
        ], static fn ($value): bool => $value !== null);

        return array_filter([
            '@type' => 'Product',
            '@id' => $ad->url().'#product',
            'name' => $ad->title,
            'description' => $ad->text !== null ? mb_substr(trim($ad->text), 0, 500) : null,
            'image' => $ad->photoUrls() ?: null,
            'category' => $ad->category !== null ? (string) $ad->category->name : null,
            'offers' => $offer,
        ], static fn ($value): bool => $value !== null);
    }

    /**
     * @param  array<int,array<string,mixed>>  $cards
     * @return array<string,mixed>
     */
    public function list(string $name, string $url, array $cards): array
    {
        $items = [];

        foreach (array_values($cards) as $position => $card) {
            $items[] = ['@type' => 'ListItem', 'position' => $position + 1, 'url' => $card['url'], 'name' => $card['title']];
        }

        return ['@type' => 'ItemList', '@id' => $url.'#list', 'name' => $name, 'url' => $url, 'numberOfItems' => count($items), 'itemListElement' => $items];
    }

    /**
     * @param  array<int,array{label:string,url:string}>  $breadcrumbs
     * @return array<string,mixed>
     */
    public function breadcrumbs(array $breadcrumbs): array
    {
        $items = [];

        foreach (array_values($breadcrumbs) as $position => $crumb) {
            $items[] = ['@type' => 'ListItem', 'position' => $position + 1, 'name' => $crumb['label'], 'item' => $crumb['url']];
        }

        return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
}
