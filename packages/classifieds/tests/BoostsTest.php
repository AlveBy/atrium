<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Enums\BoostType;
use Atrium\Classifieds\Models\Boost;
use Atrium\Classifieds\Support\Boosts;
use Atrium\Classifieds\Support\Checkout;
use Illuminate\Validation\ValidationException;

/**
 * Raising, highlighting and VIP: ordered from the cabinet, paid through
 * the shop's checkout or by hand in the admin, applied once paid.
 */
final class BoostsTest extends TestCase
{
    public function test_a_free_boost_works_at_once(): void
    {
        $this->setting('raise_price', 0);
        $user = $this->user();
        $ad = $this->ad($user, $this->category(), ['raised_at' => now()->subDays(5)]);

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.boost', $ad->id), ['type' => 'raise'])
            ->assertRedirect(route('atrium.classifieds.cabinet.ads'))
            ->assertSessionHas('atrium-classifieds.status', 'Done: the boost is on.');

        $boost = Boost::query()->sole();
        $this->assertSame(Boost::PAID, $boost->status);
        $this->assertNotNull($boost->paid_at);
        $this->assertEqualsWithDelta(now()->getTimestamp(), $ad->refresh()->raised_at?->getTimestamp(), 5);
    }

    public function test_a_paid_boost_waits_without_a_shop_and_works_once_marked_paid_in_the_admin(): void
    {
        $this->setting('vip_price', 12.5);
        $this->setting('vip_days', 3);
        $user = $this->user();
        $ad = $this->ad($user, $this->category());

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.boost', $ad->id), ['type' => 'vip'])
            ->assertSessionHas('atrium-classifieds.status');

        $boost = Boost::query()->sole();
        $this->assertSame(Boost::PENDING, $boost->status);
        $this->assertSame(12.5, $boost->amount);
        $this->assertSame(3, $boost->days);
        $this->assertSame('USD', $boost->currency);
        $this->assertNull($ad->refresh()->vip_until);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.classified_boosts.update', $boost->id), ['ad_id' => $ad->id, 'account_user_id' => $user->id, 'type' => 'vip', 'days' => 3, 'amount' => 12.5, 'currency' => 'USD', 'status' => 'paid'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($ad->refresh()->isVip());
        $this->assertEqualsWithDelta(now()->addDays(3)->getTimestamp(), $ad->vip_until?->getTimestamp(), 5);
    }

    public function test_the_shop_checkout_gets_the_boost(): void
    {
        $user = $this->user();
        $ad = $this->ad($user, $this->category());
        $this->app->instance(Checkout::class, new class implements Checkout
        {
            public function start(Boost $boost): ?string
            {
                return 'https://shop.example.com/cart?ref='.$boost->cartReference();
            }
        });

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.boost', $ad->id), ['type' => 'highlight'])
            ->assertRedirect('https://shop.example.com/cart?ref=classifieds:boost:'.Boost::query()->sole()->id);
    }

    public function test_a_payment_reference_finds_the_boost_and_extends_from_the_current_end(): void
    {
        $boosts = app(Boosts::class);
        $user = $this->user();
        $ad = $this->ad($user, $this->category(), ['highlighted_until' => now()->addDays(2)]);
        $boost = $boosts->order($this->owner($user), $ad, BoostType::Highlight);

        $this->assertTrue($boosts->find($boost->cartReference())?->is($boost));
        $this->assertNull($boosts->find('shop:product:1'));
        $this->assertNull($boosts->find('classifieds:boost:999'));

        $boosts->paid($boost, 'ORDER-17');
        $boosts->paid($boost->refresh(), 'ORDER-18');

        $this->assertSame('ORDER-17', $boost->refresh()->reference);
        $this->assertEqualsWithDelta(now()->addDays(2 + 7)->getTimestamp(), $ad->refresh()->highlighted_until?->getTimestamp(), 5);
        $this->assertTrue($ad->isHighlighted());
    }

    public function test_a_cancelled_boost_does_nothing_and_a_paid_one_cannot_be_cancelled(): void
    {
        $boosts = app(Boosts::class);
        $user = $this->user();
        $ad = $this->ad($user, $this->category());
        $first = $boosts->order($this->owner($user), $ad, BoostType::Vip);
        $second = $boosts->order($this->owner($user), $ad, BoostType::Raise);

        $boosts->cancel($first);
        $boosts->paid($first->refresh(), 'late');
        $this->assertSame(Boost::CANCELLED, $first->refresh()->status);
        $this->assertNull($ad->refresh()->vip_until);
        $this->assertNull($boosts->checkoutUrl($first));

        $boosts->paid($second, null);
        $boosts->cancel($second->refresh());
        $this->assertSame(Boost::PAID, $second->refresh()->status);
    }

    public function test_an_ad_that_is_not_live_cannot_be_boosted(): void
    {
        $user = $this->user();
        $ad = $this->ad($user, $this->category(), ['status' => AdStatus::Draft]);

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.boost', $ad->id), ['type' => 'vip'])->assertSessionHasErrors('ad');
        $this->assertSame(0, Boost::query()->count());

        $this->expectException(ValidationException::class);
        app(Boosts::class)->order($this->owner($user), $ad, BoostType::Raise);
    }

    public function test_a_boost_of_a_deleted_ad_is_marked_paid_without_failing(): void
    {
        $boosts = app(Boosts::class);
        $user = $this->user();
        $ad = $this->ad($user, $this->category());
        $boost = $boosts->order($this->owner($user), $ad, BoostType::Vip);
        $ad->delete();

        $boosts->paid($boost, 'ORDER-1');

        $this->assertNotNull($boost->refresh()->paid_at);
    }

    public function test_the_cabinet_shows_the_prices(): void
    {
        $this->setting('highlight_price', 4);
        $user = $this->user();
        $this->ad($user, $this->category());

        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.ads'))->assertOk()->assertSee('$4');
        $this->assertSame(['amount' => 4.0, 'days' => 7, 'currency' => 'USD'], app(Boosts::class)->offer(BoostType::Highlight));
        $this->assertSame(1, app(Boosts::class)->offer(BoostType::Raise)['days']);
    }
}
