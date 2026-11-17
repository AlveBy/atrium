<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Enums\BoostType;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Nobody touches somebody else's ad: every cabinet action answers 403
 * and changes nothing.
 */
final class OtherUsersAdTest extends TestCase
{
    /**
     * @return array<string,array{0:string,1:string,2:array<string,mixed>}>
     */
    public static function actions(): array
    {
        return [
            'open the form' => ['get', 'edit', []],
            'change it' => ['put', 'update', ['title' => 'Mine now', 'category_id' => 0, 'contact_phone' => '+1 202 555 0199']],
            'send it' => ['post', 'submit', []],
            'mark it sold' => ['post', 'sold', []],
            'renew it' => ['post', 'renew', []],
            'boost it' => ['post', 'boost', ['type' => 'vip']],
            'delete it' => ['delete', 'destroy', []],
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     */
    #[DataProvider('actions')]
    public function test_another_users_ad_is_off_limits(string $method, string $route, array $data): void
    {
        $owner = $this->user();
        $category = $this->category();
        $ad = $this->ad($owner, $category, ['status' => AdStatus::Expired, 'expires_at' => now()->subDay()]);
        if (isset($data['category_id'])) {
            $data['category_id'] = $category->id;
        }

        $this->actingAs($this->user('Mallory Stone'))
            ->call(strtoupper($method), route('atrium.classifieds.cabinet.'.$route, $ad->id), $data)
            ->assertForbidden();

        $ad->refresh();
        $this->assertSame('Blue bicycle', $ad->title);
        $this->assertSame(AdStatus::Expired, $ad->status);
        $this->assertSame(0, $ad->renewals);
        $this->assertSame(0, $ad->newQuery()->getConnection()->table('classified_boosts')->count());
    }

    public function test_an_unknown_ad_is_not_found(): void
    {
        $this->actingAs($this->user())->get(route('atrium.classifieds.cabinet.edit', 999))->assertNotFound();
    }

    public function test_the_list_shows_only_my_ads(): void
    {
        $category = $this->category();
        $me = $this->user();
        $this->ad($me, $category, ['title' => 'My kettle']);
        $this->ad($this->user('Sam Taylor'), $category, ['title' => 'Their toaster']);

        $this->actingAs($me)->get(route('atrium.classifieds.cabinet.ads'))
            ->assertOk()->assertSee('My kettle')->assertDontSee('Their toaster');
    }

    public function test_a_drafts_page_is_seen_by_its_owner_only(): void
    {
        $owner = $this->user();
        $ad = $this->ad($owner, $this->category(), ['status' => AdStatus::Rejected, 'reject_reason' => 'Blurry photos']);

        $this->actingAs($owner)->get($ad->url())->assertOk()->assertSee('Blurry photos');
        $this->actingAs($this->user('Sam Taylor'))->get($ad->url())->assertNotFound();
        unset($this->app['auth']);
    }

    public function test_the_boost_type_must_be_known(): void
    {
        $owner = $this->user();
        $ad = $this->ad($owner, $this->category());

        $this->actingAs($owner)->post(route('atrium.classifieds.cabinet.boost', $ad->id), ['type' => 'gold'])->assertSessionHasErrors('type');
        $this->assertNotNull(BoostType::tryFrom('vip'));
    }
}
