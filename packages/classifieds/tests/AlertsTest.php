<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Classifieds\Mail\AlertMail;
use Atrium\Classifieds\Models\SavedSearch;
use Atrium\Classifieds\Support\Alerts;
use Illuminate\Support\Facades\Mail;

/**
 * Saved searches: new matches are mailed once, nothing old and nothing
 * that does not match.
 */
final class AlertsTest extends TestCase
{
    public function test_a_search_is_saved_from_the_list_and_managed_in_the_cabinet(): void
    {
        $user = $this->user();
        $category = $this->category();
        $this->ad($user, $category);

        $this->actingAs($user)->post(route('atrium.classifieds.searches.store'), [
            'name' => 'Cheap bikes',
            'filters' => ['category' => $category->id, 'price' => ['max' => '200'], 'junk' => 'x'],
        ])->assertRedirect(route('atrium.classifieds.cabinet.searches'));

        $saved = SavedSearch::query()->sole();
        $this->assertEquals(['category' => $category->id, 'price' => ['max' => 200]], $saved->filters);
        $this->assertTrue($saved->notify);
        $this->assertSame($saved->last_ad_id, (int) \Atrium\Classifieds\Models\Ad::query()->max('id'));

        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.searches'))->assertOk()->assertSee('Cheap bikes');
        $this->actingAs($user)->patch(route('atrium.classifieds.cabinet.searches.update', $saved->id))->assertRedirect();
        $this->assertFalse($saved->refresh()->notify);
        $this->actingAs($user)->delete(route('atrium.classifieds.cabinet.searches.destroy', $saved->id))->assertRedirect();
        $this->assertSame(0, SavedSearch::query()->count());
    }

    public function test_a_search_needs_a_name_and_a_signed_in_user(): void
    {
        $this->post(route('atrium.classifieds.searches.store'), ['name' => 'x'])->assertRedirect('/login');
        $this->actingAs($this->user())->post(route('atrium.classifieds.searches.store'), ['name' => ''])->assertSessionHasErrors('name');
        $this->assertSame(0, SavedSearch::query()->count());
    }

    public function test_somebody_elses_search_is_off_limits(): void
    {
        $saved = app(Alerts::class)->save($this->user()->id, 'Mine', []);
        $stranger = $this->user('Mallory Stone');

        $this->actingAs($stranger)->patch(route('atrium.classifieds.cabinet.searches.update', $saved->id))->assertNotFound();
        $this->actingAs($stranger)->delete(route('atrium.classifieds.cabinet.searches.destroy', $saved->id))->assertNotFound();
        $this->assertTrue($saved->refresh()->notify);
    }

    public function test_new_matches_are_mailed_once(): void
    {
        Mail::fake();
        $user = $this->user();
        $category = $this->category();
        $other = $this->category('home');
        $this->ad($user, $category, ['title' => 'Before the search']);
        $saved = app(Alerts::class)->save($user->id, 'Bikes', ['category' => $category->id]);

        $this->artisan('atrium:classifieds:alerts')->assertSuccessful();
        Mail::assertNothingSent();

        $new = $this->ad($this->user('Sam Taylor'), $category, ['title' => 'Fresh bike']);
        $this->ad($user, $other, ['title' => 'Sofa']);

        $this->artisan('atrium:classifieds:alerts')->assertSuccessful();
        Mail::assertSent(AlertMail::class, function (AlertMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && array_column($mail->cards, 'title') === ['Fresh bike']
                && str_contains($mail->render(), 'Fresh bike');
        });
        $this->assertSame($new->id, $saved->refresh()->last_ad_id);
        $this->assertNotNull($saved->notified_at);

        $this->artisan('atrium:classifieds:alerts')->assertSuccessful();
        Mail::assertSent(AlertMail::class, 1);
    }

    public function test_switched_off_searches_and_users_without_an_address_get_nothing(): void
    {
        Mail::fake();
        $user = $this->user();
        $category = $this->category();
        app(Alerts::class)->save($user->id, 'Quiet', [], false);
        $orphan = app(Alerts::class)->save(4242, 'Nobody', []);
        $this->ad($user, $category);

        $this->assertSame(0, app(Alerts::class)->send());
        Mail::assertNothingSent();
        $this->assertSame(0, $orphan->refresh()->last_ad_id);
    }

    public function test_the_letter_is_in_the_language_the_search_was_saved_in(): void
    {
        Mail::fake();
        $user = $this->user();
        $category = $this->category();
        app()->setLocale('ru');
        app(Alerts::class)->save($user->id, 'Велосипеды', []);
        app()->setLocale('en');
        $this->ad($user, $category);

        $this->assertSame(1, app(Alerts::class)->send());
        Mail::assertSent(AlertMail::class, fn (AlertMail $mail) => $mail->locale === 'ru');
        $this->assertSame('en', app()->getLocale());
    }
}
