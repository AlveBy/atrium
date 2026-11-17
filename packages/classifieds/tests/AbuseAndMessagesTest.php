<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Mail\NewMessageMail;
use Atrium\Classifieds\Models\Favourite;
use Atrium\Classifieds\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Contacts shown on a click within a limit, messages between buyers and
 * sellers that nobody else reads, and favourites.
 */
final class AbuseAndMessagesTest extends TestCase
{
    public function test_the_phone_is_shown_on_a_click_and_counted(): void
    {
        $ad = $this->ad($this->user(), $this->category());

        $this->postJson(route('atrium.classifieds.phone', $ad->id))
            ->assertOk()
            ->assertExactJson(['name' => 'Robin', 'phone' => '+1 202 555 0101', 'email' => 'robin@example.com'])
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertSame(1, $ad->refresh()->phone_views);
    }

    public function test_phone_clicks_are_limited_per_visitor(): void
    {
        $this->setting('phone_reveals_per_hour', 3);
        $category = $this->category();
        $owner = $this->user();

        foreach (range(1, 3) as $i) {
            $this->postJson(route('atrium.classifieds.phone', $this->ad($owner, $category)->id))->assertOk();
        }

        $this->postJson(route('atrium.classifieds.phone', $this->ad($owner, $category)->id))->assertStatus(429);

        // A signed-in visitor has a limit of their own.
        $this->actingAs($this->user('Sam Taylor'))->postJson(route('atrium.classifieds.phone', $this->ad($owner, $category)->id))->assertOk();

        $this->travel(61)->minutes();
        $this->postJson(route('atrium.classifieds.phone', $this->ad($owner, $category)->id))->assertOk();
    }

    public function test_an_unpublished_ad_shows_no_phone(): void
    {
        $ad = $this->ad($this->user(), $this->category(), ['status' => AdStatus::Moderation]);

        $this->postJson(route('atrium.classifieds.phone', $ad->id))->assertNotFound();
        $this->assertSame(0, $ad->refresh()->phone_views);
    }

    public function test_a_buyer_writes_and_the_seller_answers(): void
    {
        Mail::fake();
        $seller = $this->user();
        $buyer = $this->user('Sam Taylor');
        $ad = $this->ad($seller, $this->category());

        $this->actingAs($buyer)->post(route('atrium.classifieds.message', $ad->id), ['body' => '  Is it still available?  '])
            ->assertRedirect(route('atrium.classifieds.cabinet.thread', ['ad' => $ad->id, 'buyer' => $buyer->id]));

        Mail::assertSent(NewMessageMail::class, fn (NewMessageMail $mail) => $mail->hasTo($seller->email));
        $message = Message::query()->sole();
        $this->assertSame('Is it still available?', $message->body);
        $this->assertFalse($message->from_seller);
        $this->assertStringNotContainsString('available', (string) DB::table('classified_messages')->value('body'));

        // The letter carries a link, not the text.
        $mail = new NewMessageMail($ad, 'https://example.com/thread');
        $this->assertStringNotContainsString('available', $mail->render());

        $this->actingAs($seller)->get(route('atrium.classifieds.cabinet.messages'))->assertOk()->assertSee('Is it still available?');
        $this->actingAs($seller)->get(route('atrium.classifieds.cabinet.thread', ['ad' => $ad->id, 'buyer' => $buyer->id]))
            ->assertOk()->assertSee('Is it still available?');
        $this->assertNotNull($message->refresh()->read_at);

        $this->actingAs($seller)->post(route('atrium.classifieds.cabinet.reply', ['ad' => $ad->id, 'buyer' => $buyer->id]), ['body' => 'Yes, come tomorrow'])
            ->assertRedirect();
        Mail::assertSent(NewMessageMail::class, fn (NewMessageMail $mail) => $mail->hasTo($buyer->email));

        $reply = Message::query()->latest('id')->first();
        $this->assertTrue($reply?->from_seller);
        $this->assertSame($buyer->id, $reply?->buyer_id);

        $this->actingAs($buyer)->get(route('atrium.classifieds.cabinet.messages'))->assertOk()->assertSee('Yes, come tomorrow');
    }

    public function test_a_third_person_reads_no_thread_and_cannot_answer_in_it(): void
    {
        $seller = $this->user();
        $buyer = $this->user('Sam Taylor');
        $stranger = $this->user('Mallory Stone');
        $ad = $this->ad($seller, $this->category());
        $this->actingAs($buyer)->post(route('atrium.classifieds.message', $ad->id), ['body' => 'Hello']);

        $this->actingAs($stranger)->get(route('atrium.classifieds.cabinet.thread', ['ad' => $ad->id, 'buyer' => $buyer->id]))->assertForbidden();
        $this->actingAs($stranger)->post(route('atrium.classifieds.cabinet.reply', ['ad' => $ad->id, 'buyer' => $buyer->id]), ['body' => 'Hi'])->assertForbidden();
        $this->actingAs($stranger)->get(route('atrium.classifieds.cabinet.messages'))->assertOk()->assertDontSee('Hello');
        $this->assertSame(1, Message::query()->count());
    }

    public function test_the_seller_cannot_start_a_thread_or_write_to_themselves(): void
    {
        $seller = $this->user();
        $ad = $this->ad($seller, $this->category());

        $this->actingAs($seller)->post(route('atrium.classifieds.message', $ad->id), ['body' => 'Hi me'])->assertForbidden();
        $this->actingAs($seller)->post(route('atrium.classifieds.cabinet.reply', ['ad' => $ad->id, 'buyer' => 999]), ['body' => 'Hi'])->assertNotFound();
        $this->assertSame(0, Message::query()->count());
    }

    public function test_messages_need_text_a_live_ad_and_stay_within_the_limit(): void
    {
        $this->setting('messages_per_hour', 2);
        $seller = $this->user();
        $buyer = $this->user('Sam Taylor');
        $category = $this->category();
        $ad = $this->ad($seller, $category);
        $sold = $this->ad($seller, $category, ['status' => AdStatus::Sold]);

        $this->actingAs($buyer)->post(route('atrium.classifieds.message', $ad->id), ['body' => ''])->assertSessionHasErrors('body');
        $this->actingAs($buyer)->post(route('atrium.classifieds.message', $ad->id), ['body' => str_repeat('a', 2001)])->assertSessionHasErrors('body');
        $this->actingAs($buyer)->post(route('atrium.classifieds.message', $sold->id), ['body' => 'Still there?'])->assertSessionHasErrors('body');
        $this->actingAs($buyer)->post(route('atrium.classifieds.message', 999), ['body' => 'Hi'])->assertNotFound();

        $this->actingAs($buyer)->post(route('atrium.classifieds.message', $ad->id), ['body' => 'One'])->assertRedirect();
        $this->actingAs($buyer)->post(route('atrium.classifieds.message', $ad->id), ['body' => 'Two'])->assertRedirect();
        $this->actingAs($buyer)->post(route('atrium.classifieds.message', $ad->id), ['body' => 'Three'])->assertStatus(429);

        $this->assertSame(2, Message::query()->count());
    }

    public function test_a_guest_cannot_write(): void
    {
        $ad = $this->ad($this->user(), $this->category());

        $this->post(route('atrium.classifieds.message', $ad->id), ['body' => 'Hi'])->assertRedirect('/login');
        $this->assertSame(0, Message::query()->count());
    }

    public function test_a_failing_mailer_keeps_the_message(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));
        $ad = $this->ad($this->user(), $this->category());

        $this->actingAs($this->user('Sam Taylor'))->post(route('atrium.classifieds.message', $ad->id), ['body' => 'Hi'])->assertRedirect();
        $this->assertSame(1, Message::query()->count());
    }

    public function test_unread_messages_are_counted_for_each_side(): void
    {
        $seller = $this->user();
        $buyer = $this->user('Sam Taylor');
        $ad = $this->ad($seller, $this->category());
        $conversations = app(\Atrium\Classifieds\Support\Conversations::class);

        $conversations->send($this->owner($buyer), $ad, $buyer->id, 'One');
        $conversations->send($this->owner($buyer), $ad, $buyer->id, 'Two');

        $this->assertSame(2, $conversations->unread($this->owner($seller)));
        $this->assertSame(0, $conversations->unread($this->owner($buyer)));
        $this->assertSame(2, $conversations->threads($this->owner($seller))[0]['unread']);

        $conversations->send($this->owner($seller), $ad, $buyer->id, 'Three');
        $this->assertSame(1, $conversations->unread($this->owner($buyer)));

        $conversations->open($this->owner($seller), $ad, $buyer->id);
        $this->assertSame(0, $conversations->unread($this->owner($seller)));
        $this->assertSame(1, $conversations->unread($this->owner($buyer)));
    }

    public function test_threads_of_a_deleted_ad_are_left_out(): void
    {
        $seller = $this->user();
        $buyer = $this->user('Sam Taylor');
        $ad = $this->ad($seller, $this->category());
        $conversations = app(\Atrium\Classifieds\Support\Conversations::class);
        $conversations->send($this->owner($buyer), $ad, $buyer->id, 'One');
        $ad->delete();

        $this->assertSame([], $conversations->threads($this->owner($buyer)));
    }

    public function test_favourites_are_toggled_and_listed(): void
    {
        $user = $this->user();
        $category = $this->category();
        $ad = $this->ad($this->user('Sam Taylor'), $category, ['title' => 'Oak table']);
        $draft = $this->ad($this->user('Sam Taylor'), $category, ['status' => AdStatus::Draft]);

        $this->post(route('atrium.classifieds.favourite', $ad->id))->assertRedirect('/login');
        $this->actingAs($user)->post(route('atrium.classifieds.favourite', $ad->id))->assertRedirect();
        $this->assertSame(1, Favourite::query()->count());
        $this->actingAs($user)->get(route('atrium.classifieds.cabinet.favourites'))->assertOk()->assertSee('Oak table');

        $this->actingAs($user)->post(route('atrium.classifieds.favourite', $ad->id));
        $this->assertSame(0, Favourite::query()->count());

        $this->actingAs($user)->post(route('atrium.classifieds.favourite', $draft->id))->assertNotFound();
    }
}
