<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Database;

use Atrium\Admin\Modules\DemoSeeder;
use Atrium\Admin\Support\Sites;
use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Category;
use Atrium\Classifieds\Models\City;
use Atrium\Classifieds\Models\Region;
use Atrium\Classifieds\Models\StopWord;
use Illuminate\Support\Str;

/**
 * A board of a dozen and a half made-up ads, for the country the demo is
 * installed for: its cities, its language, its currency and its phone
 * numbers (Atrium\Admin\Modules\DemoLocale). Without that helper the ads
 * are in English, in invented towns, priced in dollars.
 *
 * Ads have every status a moderator meets: on the site, waiting, rejected
 * for a stop word, expired, sold; one is VIP and two are highlighted. They
 * belong to no account (user 0): the contacts are on the ads.
 */
final class ClassifiedsDemoSeeder extends DemoSeeder
{
    private const DEMO_LOCALE = 'Atrium\\Admin\\Modules\\DemoLocale';

    /** Parent code => child codes. */
    private const TREE = [
        'transport' => ['cars', 'bicycles'],
        'property' => ['rent', 'sale'],
        'electronics' => ['phones', 'computers'],
        'home' => ['furniture'],
        'services' => [],
    ];

    /**
     * Per ad, in the order of the texts: category, city, price in dollars
     * (null: negotiable), status, photo, placement.
     *
     * @var array<int,array{0:string,1:int,2:float|null,3:AdStatus,4:string|null,5:string|null}>
     */
    private const ADS = [
        ['cars', 0, 9500, AdStatus::Active, 'car', 'vip'],
        ['cars', 1, 14800, AdStatus::Active, 'car', null],
        ['bicycles', 0, 180, AdStatus::Active, 'bike', 'highlight'],
        ['bicycles', 2, 640, AdStatus::Active, 'bike', null],
        ['rent', 0, 650, AdStatus::Active, 'flat', null],
        ['rent', 1, 420, AdStatus::Active, 'flat', null],
        ['sale', 3, 185000, AdStatus::Active, 'house', null],
        ['phones', 0, 320, AdStatus::Active, 'phone', null],
        ['phones', 2, 45, AdStatus::Sold, 'phone', null],
        ['computers', 1, 780, AdStatus::Active, 'laptop', null],
        ['computers', 0, 1450, AdStatus::Expired, 'laptop', null],
        ['furniture', 3, 390, AdStatus::Active, null, null],
        ['furniture', 0, 500, AdStatus::Moderation, 'sofa', null],
        ['furniture', 1, 0, AdStatus::Active, null, null],
        ['services', 0, null, AdStatus::Active, 'tools', 'highlight'],
        ['services', 2, 60, AdStatus::Active, null, null],
        ['services', 1, 25, AdStatus::Rejected, null, null],
    ];

    /** Invented towns for a demo without DemoLocale. */
    private const TOWNS = [
        ['name' => 'Northbridge', 'region' => 'Lake County', 'lat' => 40.7128, 'lng' => -74.006],
        ['name' => 'Eastport', 'region' => 'Bay County', 'lat' => 41.8781, 'lng' => -87.6298],
        ['name' => 'Millbrook', 'region' => 'Lake County', 'lat' => 39.9526, 'lng' => -75.1652],
        ['name' => 'Westfield', 'region' => 'Hill County', 'lat' => 42.3601, 'lng' => -71.0589],
    ];

    private const PEOPLE = ['Alex Morgan', 'Sam Taylor', 'Jordan Lee', 'Casey Brooks', 'Riley Parker', 'Jamie Quinn'];

    public function run(): bool
    {
        if (Category::query()->withoutGlobalScopes()->exists()) {
            return false;
        }

        $site = app(Sites::class)->default();
        $place = $this->place();
        $texts = $this->texts($place['language']);

        $this->copyFiles(dirname(__DIR__, 2).'/resources/demo/photos');

        $cities = $this->cities($site->id, $place);
        $categories = $this->categories($site->id);

        foreach ((array) $texts['stop_words'] as $word) {
            StopWord::query()->create(['site_id' => $site->id, 'word' => (string) $word]);
        }

        foreach (self::ADS as $index => [$category, $city, $dollars, $status, $photo, $placement]) {
            [$title, $text] = $texts['ads'][$index];
            $published = now()->subDays(1 + $index)->subHours($index * 3 % 11);
            $town = $cities[$city % count($cities)];
            $person = $place['person']($index);

            $ad = new Ad([
                'site_id' => $site->id,
                'category_id' => $categories[$category]->id,
                'city_id' => $town->id,
                'account_user_id' => 0,
                'title' => $title,
                'text' => $text,
                'price' => $dollars === null ? null : ($dollars > 0 ? $place['amount']((float) $dollars) : 0.0),
                'negotiable' => $dollars === null || $index === 1 || $index === 6,
                'currency' => $place['currency'],
                'photos' => $photo !== null ? [$this->path('classifieds-'.$photo.'.svg')] : [],
                'location' => ['lat' => (float) $town->lat + ($index % 5) * 0.004, 'lng' => (float) $town->lng - ($index % 3) * 0.004, 'address' => null],
                'contact_name' => $person,
                'contact_phone' => $place['phone']($index),
                'contact_email' => Str::slug(Str::ascii($person)) .'@example.com',
                'status' => $status,
                'published_at' => $status === AdStatus::Moderation || $status === AdStatus::Rejected ? null : $published,
                'expires_at' => $status === AdStatus::Expired ? now()->subDays(2) : ($status === AdStatus::Active ? $published->copy()->addDays(30) : null),
                'raised_at' => $placement === 'vip' ? now()->subHour() : null,
                'vip_until' => $placement === 'vip' ? now()->addDays(7) : null,
                'highlighted_until' => $placement === 'highlight' ? now()->addDays(5) : null,
            ]);

            if ($status === AdStatus::Rejected) {
                $ad->reject_reason = (string) $texts['reject_reason'];
                $ad->flags = array_values(array_map('strval', (array) $texts['stop_words']));
            }

            $ad->views = 12 + ($index * 37) % 240;
            $ad->save();
        }

        return true;
    }

    /**
     * The demo country: language, currency, cities, people and phones.
     *
     * @return array{language:string,currency:string,cities:array<int,array<string,mixed>>,person:\Closure(int):string,phone:\Closure(int):string,amount:\Closure(float):float}
     */
    private function place(): array
    {
        if (class_exists(self::DEMO_LOCALE)) {
            $locale = (self::DEMO_LOCALE)::current();

            return [
                'language' => $locale->locale,
                'currency' => $locale->currency,
                'cities' => $locale->cities(),
                'person' => static fn (int $index): string => (string) $locale->person($index)['name'],
                'phone' => static fn (int $index): string => (string) $locale->phone($index),
                'amount' => static fn (float $dollars): float => (float) $locale->amount($dollars),
            ];
        }

        return [
            'language' => 'en',
            'currency' => 'USD',
            'cities' => self::TOWNS,
            'person' => static fn (int $index): string => self::PEOPLE[$index % count(self::PEOPLE)],
            // 555-01xx: numbers kept for fiction.
            'phone' => static fn (int $index): string => sprintf('+1 202 555 01%02d', $index % 100),
            'amount' => static fn (float $dollars): float => $dollars,
        ];
    }

    /**
     * The texts in the demo language, English when there are none.
     *
     * @return array<string,mixed>
     */
    private function texts(string $language): array
    {
        $file = $this->textFile($language) ?? $this->textFile('en');

        return (array) require $file;
    }

    private function textFile(string $language): ?string
    {
        $path = dirname(__DIR__, 2).'/resources/demo/texts/'.$language.'.php';

        return is_file($path) ? $path : null;
    }

    /**
     * @param  array<string,mixed>  $place
     * @return array<int,City>
     */
    private function cities(int $siteId, array $place): array
    {
        $language = $place['language'];
        $regions = [];
        $cities = [];

        foreach (array_values($place['cities']) as $index => $town) {
            $regionName = (string) ($town['region'] ?? $town['name']);

            $regions[$regionName] ??= Region::query()->create([
                'site_id' => $siteId,
                'name' => [$language => $regionName],
                'code' => Str::slug(Str::ascii($regionName)) ?: 'region-'.(count($regions) + 1),
                'sort' => (count($regions) + 1) * 10,
            ]);

            $cities[] = City::query()->create([
                'site_id' => $siteId,
                'region_id' => $regions[$regionName]->id,
                'name' => [$language => (string) $town['name']],
                'code' => Str::slug(Str::ascii((string) $town['name'])) ?: 'city-'.($index + 1),
                'lat' => $town['lat'] ?? null,
                'lng' => $town['lng'] ?? null,
                'sort' => ($index + 1) * 10,
            ]);
        }

        return $cities;
    }

    /**
     * The tree, named in every language the demo has texts in.
     *
     * @return array<string,Category>
     */
    private function categories(int $siteId): array
    {
        $names = [];

        foreach (glob(dirname(__DIR__, 2).'/resources/demo/texts/*.php') ?: [] as $file) {
            $names[basename($file, '.php')] = (array) ((array) require $file)['categories'];
        }

        $name = static fn (string $code): array => array_map(static fn (array $set): string => (string) ($set[$code] ?? $code), $names);
        $categories = [];
        $sort = 0;

        foreach (self::TREE as $parent => $children) {
            $categories[$parent] = Category::query()->create(['site_id' => $siteId, 'name' => $name($parent), 'code' => $parent, 'sort' => $sort += 10]);

            foreach ($children as $child) {
                $categories[$child] = Category::query()->create([
                    'site_id' => $siteId, 'parent_id' => $categories[$parent]->id, 'name' => $name($child), 'code' => $child, 'sort' => $sort += 10,
                ]);
            }
        }

        return $categories;
    }
}
