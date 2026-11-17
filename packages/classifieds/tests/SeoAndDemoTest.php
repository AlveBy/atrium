<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Classifieds\Board;
use Atrium\Classifieds\Database\ClassifiedsDemoSeeder;
use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Category;
use Atrium\Classifieds\Models\City;
use Atrium\Classifieds\Models\StopWord;
use Atrium\Classifieds\Support\Schema;
use Atrium\Classifieds\Support\SeoTarget;
use Illuminate\Database\Eloquent\Model;

/**
 * schema.org for ads and lists, the hand-over to the SEO module, the
 * sitemap, prices as visitors read them, and the demo board.
 */
final class SeoAndDemoTest extends TestCase
{
    public function test_an_ad_is_a_product_with_an_offer_and_a_place(): void
    {
        $city = $this->city('northbridge');
        $ad = $this->ad($this->user(), $this->category('bikes'), [
            'price' => 640, 'city_id' => $city->id, 'photos' => ['classifieds/a.jpg'],
            'location' => ['lat' => 40.5, 'lng' => -74.25],
        ])->load('category', 'city');

        $node = app(Schema::class)->ad($ad);

        $this->assertSame('Product', $node['@type']);
        $this->assertSame('Bikes', $node['category']);
        $this->assertSame('640.00', $node['offers']['price']);
        $this->assertSame('USD', $node['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $node['offers']['availability']);
        $this->assertSame(['@type' => 'GeoCoordinates', 'latitude' => 40.5, 'longitude' => -74.25], $node['offers']['availableAtOrFrom']['geo']);
        $this->assertCount(1, $node['image']);
    }

    public function test_a_negotiable_ad_has_no_price_and_a_sold_one_is_sold_out(): void
    {
        $category = $this->category();
        $user = $this->user();
        $negotiable = app(Schema::class)->ad($this->ad($user, $category, ['price' => null, 'negotiable' => true, 'text' => null]));
        $sold = app(Schema::class)->ad($this->ad($user, $category, ['status' => AdStatus::Sold]));

        $this->assertArrayNotHasKey('price', $negotiable['offers']);
        $this->assertArrayNotHasKey('description', $negotiable);
        $this->assertArrayNotHasKey('availableAtOrFrom', $negotiable['offers']);
        $this->assertSame('https://schema.org/SoldOut', $sold['offers']['availability']);
    }

    public function test_without_the_seo_module_the_page_prints_its_own_graph_safely(): void
    {
        $this->ad($this->user(), $this->category(), ['title' => 'Bike </script><script>alert(1)</script>']);

        $json = app(Schema::class)->emit(null, [['label' => 'Board', 'url' => 'https://example.com/b']], [['@type' => 'Thing', 'name' => '</script>']]);
        $this->assertStringNotContainsString('</script>', (string) $json);
        $data = json_decode((string) $json, true);
        $this->assertSame('BreadcrumbList', $data['@graph'][1]['@type']);

        $this->get(route('atrium.classifieds.index'))
            ->assertOk()
            ->assertSee('"@type":"ItemList"', false)
            ->assertDontSee('</script><script>alert(1)', false);
    }

    public function test_with_the_seo_module_the_nodes_go_to_it(): void
    {
        $target = new class implements SeoTarget
        {
            /** @var array<int,array<string,mixed>> */
            public array $pages = [];

            public function page(?Model $record, bool $isRecord, array $breadcrumbs, array $nodes): void
            {
                $this->pages[] = compact('record', 'isRecord', 'breadcrumbs', 'nodes');
            }
        };
        app(Schema::class)->useSeo($target);
        $ad = $this->ad($this->user(), $this->category());

        $this->get($ad->url())->assertOk()->assertDontSee('application/ld+json', false);

        $this->assertCount(1, $target->pages);
        $this->assertTrue($target->pages[0]['isRecord']);
        $this->assertTrue($ad->is($target->pages[0]['record']));
        $this->assertSame('Product', $target->pages[0]['nodes'][0]['@type']);
        $this->assertSame('Blue bicycle', end($target->pages[0]['breadcrumbs'])['label']);
        app(Schema::class)->useSeo(null);
    }

    public function test_the_sitemap_has_live_ads_and_active_categories(): void
    {
        $user = $this->user();
        $category = $this->category();
        $this->category('archive', null, ['active' => false]);
        $live = $this->ad($user, $category);
        $this->ad($user, $category, ['status' => AdStatus::Draft]);

        $this->assertSame([$live->id], Ad::sitemapQuery()->pluck('id')->all());
        $this->assertSame([$category->id], Category::sitemapQuery()->pluck('id')->all());
        $this->assertSame($live->url('en'), $live->sitemapUrl('en'));
        $this->assertSame($category->url('en'), $category->sitemapUrl('en'));
    }

    public function test_prices_as_visitors_read_them(): void
    {
        $board = app(Board::class);

        $this->assertStringContainsString('1,250', $board->price(1250, false, 'USD'));
        $this->assertSame('Negotiable', $board->price(null, false));
        $this->assertSame('Negotiable', $board->price(0.0, true));
        $this->assertStringEndsWith('Negotiable', $board->price(90, true, 'EUR'));
        $this->setting('currency', 'eur');
        $this->assertSame('EUR', $board->currency());
    }

    public function test_the_demo_board_is_filled_once(): void
    {
        $this->assertTrue((new ClassifiedsDemoSeeder)->run());

        $this->assertGreaterThan(10, Category::query()->count());
        $this->assertGreaterThan(2, City::query()->count());
        $this->assertGreaterThan(10, Ad::query()->published()->count());
        $this->assertSame(1, Ad::query()->where('status', AdStatus::Rejected->value)->count());
        $this->assertNotNull(Ad::query()->where('status', AdStatus::Rejected->value)->value('reject_reason'));
        $this->assertGreaterThan(0, Ad::query()->where('vip_until', '>', now())->count());
        $this->assertGreaterThan(0, StopWord::query()->count());
        $this->assertSame('Транспорт', Category::query()->where('code', 'transport')->first()?->getTranslation('name', 'ru'));

        $this->assertFalse((new ClassifiedsDemoSeeder)->run());

        $this->get(route('atrium.classifieds.index'))->assertOk();
        $ad = Ad::query()->published()->first();
        $this->get((string) $ad?->url())->assertOk();
    }

    public function test_the_cabinet_pages_open(): void
    {
        $user = $this->user();
        $parent = $this->category('transport');
        $this->category('cars', $parent);
        $this->city();
        $this->ad($user, $parent, ['status' => AdStatus::Draft, 'title' => 'Draft trailer']);

        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.ads'))->assertOk()->assertSee('Draft trailer');
        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.ads', ['status' => 'draft']))->assertOk()->assertSee('Draft trailer');
        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.ads', ['status' => 'sold']))->assertOk()->assertDontSee('Draft trailer');
        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.create'))->assertOk()->assertSee('Cars')->assertSee('Northbridge');
        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.create', ['category' => 'cars']))->assertOk();
        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.favourites'))->assertOk();
        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.messages'))->assertOk();
        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.searches'))->assertOk();
    }
}
