<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Support\AdSearch;
use Atrium\Classifieds\Support\Cards;
use Atrium\Classifieds\Support\PropertySource;
use Atrium\Classifieds\Tests\Fixtures\FakeProperties;
use Illuminate\Support\Facades\DB;

/**
 * Search and filters: price, place, category with its subcategories,
 * date, words, category properties, the order, and the landing pages.
 */
final class SearchTest extends TestCase
{
    /**
     * @param  array<string,mixed>  $input
     * @return array<int,string>
     */
    private function titles(array $input): array
    {
        $search = app(AdSearch::class);

        return $search->query($search->normalize($input))->pluck('title')->all();
    }

    public function test_price_range_both_ways_and_swapped(): void
    {
        $user = $this->user();
        $category = $this->category();
        $this->ad($user, $category, ['title' => 'Cheap', 'price' => 20]);
        $this->ad($user, $category, ['title' => 'Middle', 'price' => 300]);
        $this->ad($user, $category, ['title' => 'Dear', 'price' => 9000]);
        $this->ad($user, $category, ['title' => 'Ask me', 'price' => null, 'negotiable' => true]);

        $this->assertEqualsCanonicalizing(['Middle', 'Dear'], $this->titles(['price' => ['min' => '100']]));
        $this->assertEqualsCanonicalizing(['Cheap', 'Middle'], $this->titles(['price' => ['max' => '300']]));
        $this->assertSame(['Middle'], $this->titles(['price' => ['min' => 1000, 'max' => 100]]));
        $this->assertCount(4, $this->titles(['price' => ['min' => '-5', 'max' => 'lots']]));
        $this->assertSame(['min' => 100.0, 'max' => 1000.0], app(AdSearch::class)->normalize(['price' => ['min' => 1000, 'max' => 100]])['price']);
    }

    public function test_a_category_includes_its_subcategories(): void
    {
        $user = $this->user();
        $transport = $this->category('transport');
        $cars = $this->category('cars', $transport);
        $home = $this->category('home');
        $this->ad($user, $transport, ['title' => 'Trailer']);
        $this->ad($user, $cars, ['title' => 'Sedan']);
        $this->ad($user, $home, ['title' => 'Sofa']);

        $this->assertEqualsCanonicalizing(['Trailer', 'Sedan'], $this->titles(['category' => $transport->id]));
        $this->assertSame(['Sedan'], $this->titles(['category' => $cars->id]));
        $this->assertSame([], $this->titles(['category' => 999]));
    }

    public function test_by_city_or_by_region(): void
    {
        $user = $this->user();
        $category = $this->category();
        $lake = $this->region('lake-county');
        $hill = $this->region('hill-county');
        $north = $this->city('northbridge', $lake);
        $south = $this->city('southport', $lake);
        $east = $this->city('eastfield', $hill);
        $this->ad($user, $category, ['title' => 'North', 'city_id' => $north->id]);
        $this->ad($user, $category, ['title' => 'South', 'city_id' => $south->id]);
        $this->ad($user, $category, ['title' => 'East', 'city_id' => $east->id]);
        $this->ad($user, $category, ['title' => 'Nowhere']);

        $this->assertSame(['North'], $this->titles(['city' => $north->id]));
        $this->assertEqualsCanonicalizing(['North', 'South'], $this->titles(['region' => $lake->id]));
        // The city wins over a region it is not in.
        $this->assertSame(['East'], $this->titles(['region' => $lake->id, 'city' => $east->id]));
        $this->assertCount(4, $this->titles(['city' => 'abc', 'region' => -1]));
    }

    public function test_by_date_and_by_words(): void
    {
        $user = $this->user();
        $category = $this->category();
        $this->ad($user, $category, ['title' => 'Red bike 100%', 'published_at' => now()->subHours(3)]);
        $this->ad($user, $category, ['title' => 'Red chair', 'published_at' => now()->subDays(5)]);
        $this->ad($user, $category, ['title' => 'Blue bike', 'published_at' => now()->subDays(20)]);

        $this->assertSame(['Red bike 100%'], $this->titles(['period' => 'day']));
        $this->assertCount(2, $this->titles(['period' => 'week']));
        $this->assertCount(3, $this->titles(['period' => 'month']));
        $this->assertCount(3, $this->titles(['period' => 'century']));
        $this->assertSame(['Red bike 100%'], $this->titles(['q' => '  red   BIKE ']));
        $this->assertSame(['Red bike 100%'], $this->titles(['q' => '100%']));
        $this->assertSame([], $this->titles(['q' => '_']));
    }

    public function test_only_live_ads_are_found(): void
    {
        $user = $this->user();
        $category = $this->category();
        $this->ad($user, $category, ['title' => 'Live']);

        foreach ([AdStatus::Draft, AdStatus::Moderation, AdStatus::Rejected, AdStatus::Sold, AdStatus::Expired] as $status) {
            $this->ad($user, $category, ['title' => $status->value, 'status' => $status]);
        }

        $this->ad($user, $category, ['title' => 'Past its term', 'expires_at' => now()->subMinute()]);

        $this->assertSame(['Live'], $this->titles([]));
    }

    public function test_vip_first_then_newest_raised_or_by_price(): void
    {
        $user = $this->user();
        $category = $this->category();
        $this->ad($user, $category, ['title' => 'Old', 'price' => 50, 'raised_at' => now()->subDays(3)]);
        $this->ad($user, $category, ['title' => 'Raised', 'price' => 500, 'raised_at' => now()->subMinute()]);
        $this->ad($user, $category, ['title' => 'Vip', 'price' => 5, 'raised_at' => now()->subDays(9), 'vip_until' => now()->addDay()]);
        $this->ad($user, $category, ['title' => 'Was vip', 'price' => 70, 'raised_at' => now()->subDays(8), 'vip_until' => now()->subDay()]);
        $this->ad($user, $category, ['title' => 'Free', 'price' => null, 'raised_at' => now()->subDays(10)]);

        $this->assertSame(['Vip', 'Raised', 'Old', 'Was vip', 'Free'], $this->titles([]));
        $this->assertSame(['Vip', 'Old', 'Was vip', 'Raised', 'Free'], $this->titles(['sort' => 'cheap']));
        $this->assertSame(['Raised', 'Was vip', 'Old', 'Vip', 'Free'], $this->titles(['sort' => 'expensive']));
        $this->assertArrayNotHasKey('sort', app(AdSearch::class)->normalize(['sort' => 'random']));
        $this->assertArrayNotHasKey('sort', app(AdSearch::class)->normalize(['sort' => 'new']));
    }

    public function test_by_category_properties(): void
    {
        $this->app->instance(PropertySource::class, new FakeProperties);
        $user = $this->user();
        $cars = $this->category('cars', null, ['fields' => ['brand', 'year', 'electric', 'colours']]);
        $other = $this->category('home');

        foreach ([['Acme 2015', 'acme', 2015], ['Acme 2021', 'acme', 2021], ['Zenith 2019', 'zenith', 2019]] as [$title, $brand, $year]) {
            $ad = $this->ad($user, $cars, ['title' => $title]);
            $ad->custom = ['brand' => $brand, 'year' => $year];
            $ad->save();
        }

        $this->assertEqualsCanonicalizing(['Acme 2015', 'Acme 2021'], $this->titles(['category' => $cars->id, 'props' => ['brand' => 'acme']]));
        $this->assertEqualsCanonicalizing(['Acme 2021', 'Zenith 2019'], $this->titles(['category' => $cars->id, 'props' => ['year' => ['min' => '2018']]]));
        $this->assertSame(['Acme 2015'], $this->titles(['category' => $cars->id, 'props' => ['brand' => ['acme'], 'year' => ['max' => 2016, 'min' => '']]]));

        // Not filterable, unknown, empty or without a category: ignored.
        $search = app(AdSearch::class);
        $this->assertArrayNotHasKey('props', $search->normalize(['category' => $cars->id, 'props' => ['colours' => 'red', 'mystery' => 'x', 'brand' => '', 'year' => ['min' => 'old']]]));
        $this->assertArrayNotHasKey('props', $search->normalize(['props' => ['brand' => 'acme']]));
        $this->assertArrayNotHasKey('props', $search->normalize(['category' => $other->id, 'props' => ['brand' => 'acme']]));
    }

    public function test_the_category_page_shows_property_filters_with_counts(): void
    {
        $this->app->instance(PropertySource::class, new FakeProperties);
        $user = $this->user();
        $cars = $this->category('cars', null, ['fields' => ['brand', 'year', 'electric']]);

        foreach (['acme', 'acme', 'zenith'] as $i => $brand) {
            $ad = $this->ad($user, $cars, ['title' => 'Car '.$i]);
            $ad->custom = ['brand' => $brand, 'electric' => true];
            $ad->save();
        }

        $this->get($cars->url().'?props[brand]=acme')
            ->assertOk()
            ->assertSee('name="props[brand][]"', false)
            ->assertSee('name="props[year][min]"', false)
            ->assertSee('Acme <span class="cl-meta">(2)</span>', false)
            ->assertSee('Zenith <span class="cl-meta">(1)</span>', false)
            ->assertSee('Car 0')
            ->assertDontSee('Car 2')
            ->assertSee('noindex', false);
    }

    public function test_without_the_custom_fields_module_the_properties_are_skipped(): void
    {
        $fake = new FakeProperties;
        $fake->installed = false;
        $this->app->instance(PropertySource::class, $fake);
        $cars = $this->category('cars', null, ['fields' => ['brand']]);
        $this->ad($this->user(), $cars, ['title' => 'Plain car']);

        $this->assertSame(['Plain car'], $this->titles(['category' => $cars->id, 'props' => ['brand' => 'acme']]));
        $this->get($cars->url())->assertOk()->assertDontSee('props[brand]', false);
    }

    public function test_landing_pages_for_category_city_and_both(): void
    {
        $user = $this->user();
        $transport = $this->category('transport');
        $cars = $this->category('cars', $transport);
        $north = $this->city('northbridge');
        $south = $this->city('southport');
        $this->ad($user, $cars, ['title' => 'North sedan', 'city_id' => $north->id]);
        $this->ad($user, $cars, ['title' => 'South sedan', 'city_id' => $south->id]);
        $this->ad($user, $transport, ['title' => 'North trailer', 'city_id' => $north->id]);

        $this->get($cars->url())->assertOk()->assertSee('North sedan')->assertSee('South sedan')->assertDontSee('North trailer')
            ->assertSee('<link rel="canonical" href="'.$cars->url().'"', false);
        $this->assertStringEndsWith('/transport/cars', $cars->url());

        $this->get(route('atrium.classifieds.city', ['city' => 'northbridge']))
            ->assertOk()->assertSee('North sedan')->assertSee('North trailer')->assertDontSee('South sedan');

        $this->get(route('atrium.classifieds.city', ['city' => 'northbridge', 'path' => 'transport/cars']))
            ->assertOk()->assertSee('North sedan')->assertDontSee('North trailer')->assertDontSee('South sedan')
            ->assertSee('<title>Cars in Northbridge', false);

        // The landing's own place cannot be changed by the query string.
        $this->get(route('atrium.classifieds.city', ['city' => 'northbridge']).'?city='.$south->id)->assertDontSee('South sedan');
    }

    public function test_unknown_landings_are_not_found(): void
    {
        $this->category('transport');
        $this->city('northbridge');

        $this->get(route('atrium.classifieds.category', ['path' => 'nothing-here']))->assertNotFound();
        $this->get(route('atrium.classifieds.city', ['city' => 'atlantis']))->assertNotFound();
        $this->get(route('atrium.classifieds.city', ['city' => 'northbridge', 'path' => 'nothing']))->assertNotFound();
        $this->get(route('atrium.classifieds.company', ['company' => 5]))->assertNotFound();
    }

    public function test_a_switched_off_category_is_not_a_landing(): void
    {
        $this->category('archive', null, ['active' => false]);

        $this->get(route('atrium.classifieds.category', ['path' => 'archive']))->assertNotFound();
    }

    public function test_pages_of_the_list(): void
    {
        $this->setting('per_page', 2);
        $user = $this->user();
        $category = $this->category();

        foreach (range(1, 5) as $i) {
            $this->ad($user, $category, ['title' => 'Lamp number '.$i, 'raised_at' => now()->subMinutes($i)]);
        }

        $this->get(route('atrium.classifieds.index'))->assertOk()->assertSee('Lamp number 1')->assertDontSee('Lamp number 3');
        $this->get(route('atrium.classifieds.index').'?page=2')->assertOk()->assertSee('Lamp number 3')->assertDontSee('Lamp number 1')
            ->assertSee('?page=2"', false)
            ->assertDontSee('noindex', false);
    }

    public function test_cards_come_from_the_cache_until_the_ad_changes(): void
    {
        $ad = $this->ad($this->user(), $this->category(), ['title' => 'Old title']);
        $cards = app(Cards::class);
        $fresh = fn () => Ad::query()->select(['id', 'updated_at'])->whereKey($ad->id)->get()->all();

        $this->assertSame('Old title', $cards->many($fresh())[0]['title']);

        // A change that does not touch the ad's time is not seen: the card is cached.
        DB::table('classified_ads')->where('id', $ad->id)->update(['title' => 'Sneaky']);
        DB::enableQueryLog();
        $this->assertSame('Old title', $cards->many($fresh())[0]['title']);
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();

        $this->travel(2)->seconds();
        $ad->refresh()->update(['title' => 'New title']);
        $this->assertSame('New title', $cards->many($fresh())[0]['title']);
        $this->assertSame([], $cards->many([]));
    }

    public function test_the_ad_page(): void
    {
        $this->app->instance(PropertySource::class, new FakeProperties);
        $owner = $this->user();
        $cars = $this->category('cars', null, ['fields' => ['brand', 'electric']]);
        $ad = $this->ad($owner, $cars, [
            'title' => 'Zenith hatchback',
            'photos' => ['classifieds/car.jpg'],
            'location' => ['lat' => 40.7, 'lng' => -74.0, 'address' => '5 Oak Lane'],
            'city_id' => $this->city()->id,
        ]);
        $ad->custom = ['brand' => 'zenith', 'electric' => true];
        $ad->save();

        $this->get($ad->url())
            ->assertOk()
            ->assertSee('Zenith hatchback')
            ->assertSee('Zenith')
            ->assertSee('5 Oak Lane')
            ->assertSee('openstreetmap', false)
            ->assertSee('application/ld+json', false)
            ->assertDontSee('555 0101');

        $this->assertSame(1, $ad->refresh()->views);

        // The owner's own visits are not counted.
        $this->actingAs($owner)->get($ad->url())->assertOk();
        $this->assertSame(1, $ad->refresh()->views);
    }

    public function test_a_wrong_slug_redirects_to_the_right_one(): void
    {
        $ad = $this->ad($this->user(), $this->category(), ['title' => 'Blue bicycle']);

        $this->get(route('atrium.classifieds.ad', ['ad' => $ad->id, 'slug' => 'old-name']))
            ->assertStatus(301)
            ->assertRedirect($ad->url());
        $this->get(route('atrium.classifieds.ad', ['ad' => 999, 'slug' => 'x']))->assertNotFound();
    }
}
