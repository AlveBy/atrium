<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Mail\StatusMail;
use Illuminate\Support\Facades\Mail;

/**
 * The term of an ad: it ends on schedule, can be renewed a few times,
 * and a sold or expired ad leaves the site.
 */
final class ExpiryTest extends TestCase
{
    public function test_the_command_takes_ads_past_their_term_off_the_site(): void
    {
        Mail::fake();
        $user = $this->user();
        $category = $this->category();
        $due = $this->ad($user, $category, ['title' => 'Old lamp', 'expires_at' => now()->subMinute()]);
        $live = $this->ad($user, $category, ['title' => 'New lamp', 'expires_at' => now()->addDay()]);
        $draft = $this->ad($user, $category, ['status' => AdStatus::Draft, 'expires_at' => now()->subDay()]);

        // Past its term it is already off the site, before the command.
        $this->get($due->url())->assertNotFound();

        $this->artisan('atrium:classifieds:expire')->expectsOutputToContain('1')->assertSuccessful();

        $this->assertSame(AdStatus::Expired, $due->refresh()->status);
        $this->assertSame(AdStatus::Active, $live->refresh()->status);
        $this->assertSame(AdStatus::Draft, $draft->refresh()->status);
        Mail::assertSent(StatusMail::class, 1);

        $this->artisan('atrium:classifieds:expire')->assertSuccessful();
        Mail::assertSent(StatusMail::class, 1);
    }

    public function test_renewing_an_expired_ad_starts_a_new_term_from_today(): void
    {
        $this->setting('ttl_days', 15);
        $user = $this->user();
        $ad = $this->ad($user, $this->category(), ['status' => AdStatus::Expired, 'expires_at' => now()->subDays(3)]);

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.renew', $ad->id))->assertRedirect();

        $ad->refresh();
        $this->assertSame(AdStatus::Active, $ad->status);
        $this->assertSame(1, $ad->renewals);
        $this->assertEqualsWithDelta(now()->addDays(15)->getTimestamp(), $ad->expires_at?->getTimestamp(), 5);
        $this->get($ad->url())->assertOk();
    }

    public function test_renewing_a_live_ad_adds_to_the_term_it_has(): void
    {
        $this->setting('ttl_days', 10);
        $user = $this->user();
        $ad = $this->ad($user, $this->category(), ['expires_at' => now()->addDays(4)]);

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.renew', $ad->id));

        $this->assertEqualsWithDelta(now()->addDays(14)->getTimestamp(), $ad->refresh()->expires_at?->getTimestamp(), 5);
    }

    public function test_renewals_run_out(): void
    {
        $this->setting('renewals', 2);
        $user = $this->user();
        $ad = $this->ad($user, $this->category());

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.renew', $ad->id))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.renew', $ad->id))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.renew', $ad->id))->assertSessionHasErrors('ad');

        $this->assertSame(2, $ad->refresh()->renewals);
    }

    public function test_a_draft_or_a_rejected_ad_cannot_be_renewed(): void
    {
        $user = $this->user();
        $category = $this->category();

        foreach ([AdStatus::Draft, AdStatus::Rejected, AdStatus::Moderation, AdStatus::Sold] as $status) {
            $ad = $this->ad($user, $category, ['status' => $status]);
            $this->actingAs($user)->post(route('atrium.classifieds.cabinet.renew', $ad->id))->assertSessionHasErrors('ad');
            $this->assertSame($status, $ad->refresh()->status);
        }
    }

    public function test_a_sold_ad_leaves_the_site_and_cannot_be_sold_twice(): void
    {
        $user = $this->user();
        $ad = $this->ad($user, $this->category());

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.sold', $ad->id))->assertRedirect();
        $this->assertSame(AdStatus::Sold, $ad->refresh()->status);

        $this->actingAs($this->user('Sam Taylor'))->get($ad->url())->assertNotFound();
        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.sold', $ad->id))->assertSessionHasErrors('ad');
    }

    public function test_a_draft_cannot_be_marked_sold(): void
    {
        $user = $this->user();
        $ad = $this->ad($user, $this->category(), ['status' => AdStatus::Draft]);

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.sold', $ad->id))->assertSessionHasErrors('ad');
    }

    public function test_an_expired_or_rejected_ad_can_be_sent_again(): void
    {
        $user = $this->user();
        $category = $this->category();
        $expired = $this->ad($user, $category, ['status' => AdStatus::Expired, 'expires_at' => now()->subDay()]);
        $rejected = $this->ad($user, $category, ['status' => AdStatus::Rejected, 'reject_reason' => 'Blurry']);
        $live = $this->ad($user, $category);

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.submit', $expired->id))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.submit', $rejected->id))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.submit', $live->id))->assertSessionHasErrors('ad');

        $this->assertSame(AdStatus::Moderation, $expired->refresh()->status);
        $this->assertNull($expired->expires_at);
        $this->assertSame(AdStatus::Moderation, $rejected->refresh()->status);
        $this->assertNull($rejected->reject_reason);
    }

    public function test_the_owner_deletes_an_ad(): void
    {
        $user = $this->user();
        $ad = $this->ad($user, $this->category());

        $this->actingAs($user)->delete(route('atrium.classifieds.cabinet.destroy', $ad->id))->assertRedirect();
        $this->assertNull($ad->fresh());
    }
}
