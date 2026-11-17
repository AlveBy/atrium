<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\StopWord;
use Atrium\Classifieds\Support\PropertySource;
use Atrium\Classifieds\Tests\Fixtures\FakeProperties;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Posting from the cabinet: drafts, sending for moderation, publishing at
 * once, the form's checks, photos, contacts and the daily limit.
 */
final class PostingTest extends TestCase
{
    /**
     * @return array<string,mixed>
     */
    private function form(int $categoryId, array $overrides = []): array
    {
        return [
            'title' => 'Mountain bike, 29-inch wheels',
            'text' => 'Hydraulic disc brakes.',
            'category_id' => $categoryId,
            'price' => '640',
            'contact_name' => 'Robin',
            'contact_phone' => '+1 (202) 555-0101',
            'contact_email' => 'robin@example.com',
            ...$overrides,
        ];
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('atrium.classifieds.cabinet.create'))->assertRedirect('/login');
        $this->post(route('atrium.classifieds.cabinet.store'), [])->assertRedirect('/login');
        $this->assertSame(0, Ad::query()->count());
    }

    public function test_the_form_opens_with_the_fields_of_the_chosen_category(): void
    {
        $this->app->instance(PropertySource::class, new FakeProperties);
        $category = $this->category('cars', null, ['fields' => ['brand', 'year']]);

        $this->actingAs($this->user())
            ->get(route('atrium.classifieds.cabinet.create', ['category_id' => $category->id]))
            ->assertOk()
            ->assertSee('name="props[brand]"', false)
            ->assertSee('name="props[year]"', false)
            ->assertSee('Of manufacture');
    }

    public function test_a_draft_is_saved_and_stays_off_the_site(): void
    {
        $user = $this->user();
        $category = $this->category();

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, ['submit' => '0']))
            ->assertRedirect(route('atrium.classifieds.cabinet.ads'));

        $ad = Ad::query()->sole();
        $this->assertSame(AdStatus::Draft, $ad->status);
        $this->assertSame($user->id, $ad->account_user_id);
        $this->assertNull($ad->account_company_id);
        $this->assertSame('mountain-bike-29-inch-wheels', $ad->code);
        $this->assertSame(640.0, $ad->price);
        $this->assertSame('USD', $ad->currency);
        $this->assertNull($ad->published_at);

        $this->get($ad->url())->assertOk();
        $this->actingAs($this->user('Sam Taylor'))->get($ad->url())->assertNotFound();
    }

    public function test_sending_it_waits_for_a_moderator_where_the_site_moderates(): void
    {
        $category = $this->category();

        $this->actingAs($this->user())->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, ['submit' => '1']))
            ->assertSessionHas('atrium-classifieds.status');

        $ad = Ad::query()->sole();
        $this->assertSame(AdStatus::Moderation, $ad->status);
        $this->assertNull($ad->published_at);
        $this->assertNull($ad->expires_at);
    }

    public function test_without_pre_moderation_it_goes_live_at_once_with_its_term(): void
    {
        $this->setting('premoderation', false);
        $this->setting('ttl_days', 10);
        $category = $this->category();

        $this->actingAs($this->user())->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, ['submit' => '1']));

        $ad = Ad::query()->sole();
        $this->assertSame(AdStatus::Active, $ad->status);
        $this->assertNotNull($ad->published_at);
        $this->assertEqualsWithDelta(now()->addDays(10)->getTimestamp(), $ad->expires_at?->getTimestamp(), 5);
        $this->get($ad->url())->assertOk()->assertSee('Mountain bike');
    }

    public function test_a_stop_word_holds_it_for_a_moderator_even_without_pre_moderation(): void
    {
        $this->setting('premoderation', false);
        StopWord::query()->create(['word' => 'Prepayment only']);
        $category = $this->category();

        $this->actingAs($this->user())->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, [
            'submit' => '1',
            'text' => 'Cheap! PREPAYMENT ONLY, no viewing.',
        ]));

        $ad = Ad::query()->sole();
        $this->assertSame(AdStatus::Moderation, $ad->status);
        $this->assertSame(['Prepayment only'], $ad->flags);
    }

    public function test_a_stop_word_inside_another_word_is_not_a_match(): void
    {
        $this->setting('premoderation', false);
        StopWord::query()->create(['word' => 'cash']);
        $category = $this->category();

        $this->actingAs($this->user())->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, ['submit' => '1', 'text' => 'Cashmere scarf']));

        $this->assertSame(AdStatus::Active, Ad::query()->sole()->status);
    }

    /**
     * @return array<string,array{0:array<string,mixed>,1:string}>
     */
    public static function invalidForms(): array
    {
        return [
            'no title' => [['title' => ''], 'title'],
            'title too long' => [['title' => str_repeat('a', 151)], 'title'],
            'negative price' => [['price' => '-5'], 'price'],
            'price is text' => [['price' => 'cheap'], 'price'],
            'phone with letters' => [['contact_phone' => 'call me maybe'], 'contact_phone'],
            'no phone and no e-mail' => [['contact_phone' => '', 'contact_email' => ''], 'contact_phone'],
            'bad e-mail' => [['contact_email' => 'not-an-email'], 'contact_email'],
            'latitude alone' => [['lat' => '40.1'], 'lng'],
            'latitude off the globe' => [['lat' => '91', 'lng' => '10'], 'lat'],
            'text too long' => [['text' => str_repeat('b', 5001)], 'text'],
        ];
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    #[DataProvider('invalidForms')]
    public function test_the_form_refuses_bad_data(array $overrides, string $field): void
    {
        $category = $this->category();

        $this->actingAs($this->user())->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, $overrides))
            ->assertSessionHasErrors($field);

        $this->assertSame(0, Ad::query()->count());
    }

    public function test_an_unknown_or_switched_off_category_is_refused(): void
    {
        $off = $this->category('archive', null, ['active' => false]);
        $user = $this->user();

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form(999))->assertSessionHasErrors('category_id');
        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($off->id))->assertSessionHasErrors('category_id');
        $this->assertSame(0, Ad::query()->count());
    }

    public function test_an_unknown_city_is_refused(): void
    {
        $category = $this->category();

        $this->actingAs($this->user())->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, ['city_id' => 4242]))
            ->assertSessionHasErrors('city_id');
    }

    public function test_required_category_properties_are_checked_and_saved(): void
    {
        $this->app->instance(PropertySource::class, new FakeProperties);
        $category = $this->category('cars', null, ['fields' => ['brand', 'year', 'colours']]);
        $user = $this->user();

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id))
            ->assertSessionHasErrors('props.brand');
        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, ['props' => ['brand' => 'unknown']]))
            ->assertSessionHasErrors('props.brand');
        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, ['props' => ['brand' => 'acme', 'colours' => ['green']]]))
            ->assertSessionHasErrors('props.colours.0');

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, ['props' => ['brand' => 'acme', 'year' => '2019', 'colours' => ['red']]]))
            ->assertSessionHasNoErrors();

        $ad = Ad::query()->sole();
        $this->assertSame(['brand' => 'acme', 'year' => '2019', 'colours' => ['red']], $ad->custom);
    }

    public function test_contacts_are_stored_encrypted_and_read_back(): void
    {
        $category = $this->category();
        $this->actingAs($this->user())->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id));

        $row = DB::table('classified_ads')->first();
        $this->assertStringNotContainsString('555', (string) $row->contact_phone);
        $this->assertStringNotContainsString('robin@', (string) $row->contact_email);
        $this->assertSame('+1 (202) 555-0101', Ad::query()->sole()->contact_phone);
        $this->assertSame('robin@example.com', Ad::query()->sole()->contact_email);
    }

    public function test_photos_are_uploaded_and_only_the_ads_own_ones_can_be_kept(): void
    {
        Storage::fake('public');
        $this->setting('max_photos', 2);
        $user = $this->user();
        $category = $this->category();

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, [
            'photos' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')],
        ]))->assertSessionHasNoErrors();

        $ad = Ad::query()->sole();
        $this->assertCount(2, $ad->photos);
        Storage::disk('public')->assertExists($ad->photos[0]);

        // Keep the first one, smuggle in a path that is not the ad's.
        $this->actingAs($user)->put(route('atrium.classifieds.cabinet.update', $ad->id), $this->form($category->id, [
            'keep' => [$ad->photos[0], 'demo/somebody-else.jpg'],
        ]))->assertSessionHasNoErrors();

        $this->assertSame([$ad->photos[0]], $ad->refresh()->photos);
    }

    public function test_too_many_photos_or_a_file_that_is_not_an_image_are_refused(): void
    {
        Storage::fake('public');
        $this->setting('max_photos', 1);
        $category = $this->category();
        $user = $this->user();

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, [
            'photos' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')],
        ]))->assertSessionHasErrors('photos');

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, [
            'photos' => [UploadedFile::fake()->create('virus.exe', 10)],
        ]))->assertSessionHasErrors('photos.0');
    }

    public function test_the_location_is_kept_as_a_point_with_its_address(): void
    {
        $category = $this->category();
        $city = $this->city();

        $this->actingAs($this->user())->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id, [
            'city_id' => $city->id, 'lat' => '40.71', 'lng' => '-74.01', 'address' => '12 Elm Street',
        ]));

        $ad = Ad::query()->sole();
        $this->assertSame($city->id, $ad->city_id);
        $this->assertSame(['lat' => 40.71, 'lng' => -74.01, 'address' => '12 Elm Street'], $ad->location);
    }

    public function test_the_daily_limit_stops_the_next_ad(): void
    {
        $this->setting('ads_per_day', 2);
        $category = $this->category();
        $user = $this->user();

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id))->assertRedirect();
        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id))->assertRedirect();
        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id))->assertStatus(429);

        $this->assertSame(2, Ad::query()->count());

        // Somebody else still may.
        $this->actingAs($this->user('Sam Taylor'))->post(route('atrium.classifieds.cabinet.store'), $this->form($category->id))->assertRedirect();
        $this->assertSame(3, Ad::query()->count());
    }

    public function test_editing_a_live_ad_sends_it_back_to_moderation_but_keeps_its_term(): void
    {
        $user = $this->user();
        $category = $this->category();
        $ad = $this->ad($user, $category, ['expires_at' => now()->addDays(12)]);
        $term = $ad->expires_at;

        $this->actingAs($user)->put(route('atrium.classifieds.cabinet.update', $ad->id), $this->form($category->id, ['title' => 'Blue bicycle, new tyres']));

        $ad->refresh();
        $this->assertSame(AdStatus::Moderation, $ad->status);
        $this->assertSame($term?->getTimestamp(), $ad->expires_at?->getTimestamp());
        $this->assertSame('blue-bicycle-new-tyres', $ad->code);
    }

    public function test_changing_only_the_contacts_of_a_live_ad_keeps_it_live(): void
    {
        $user = $this->user();
        $category = $this->category();
        $ad = $this->ad($user, $category, ['title' => 'Mountain bike, 29-inch wheels', 'text' => 'Hydraulic disc brakes.', 'price' => 640]);

        $this->actingAs($user)->put(route('atrium.classifieds.cabinet.update', $ad->id), $this->form($category->id, ['contact_phone' => '+1 202 555 0199']));

        $this->assertSame(AdStatus::Active, $ad->refresh()->status);
        $this->assertSame('+1 202 555 0199', $ad->contact_phone);
    }

    public function test_a_sold_ad_cannot_be_changed(): void
    {
        $user = $this->user();
        $category = $this->category();
        $ad = $this->ad($user, $category, ['status' => AdStatus::Sold]);

        $this->actingAs($user)->put(route('atrium.classifieds.cabinet.update', $ad->id), $this->form($category->id))
            ->assertSessionHasErrors('ad');
        $this->assertSame('Blue bicycle', $ad->refresh()->title);
    }

    public function test_the_edit_form_shows_the_ad(): void
    {
        $this->app->instance(PropertySource::class, new FakeProperties);
        $user = $this->user();
        $category = $this->category('cars', null, ['fields' => ['brand', 'electric', 'note', 'serial', 'built', 'colours']]);
        $ad = $this->ad($user, $category, ['photos' => ['classifieds/one.jpg'], 'location' => ['lat' => 1.5, 'lng' => 2.5, 'address' => 'Elm']]);
        $ad->custom = ['brand' => 'zenith', 'electric' => true, 'colours' => ['blue']];
        $ad->save();

        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.edit', $ad->id))
            ->assertOk()
            ->assertSee('value="Blue bicycle"', false)
            ->assertSee('value="zenith" selected', false)
            ->assertSee('classifieds/one.jpg')
            ->assertSee('value="1.5"', false);
    }
}
