<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Enums\ComplaintReason;
use Atrium\Classifieds\Mail\StatusMail;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Complaint;
use Atrium\Classifieds\Support\AdWorkflow;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * The moderators' side: the queue in the admin, decisions and the mail
 * about them, complaints from visitors.
 */
final class ModerationTest extends TestCase
{
    /**
     * @return array<string,mixed>
     */
    private function adminForm(Ad $ad, array $overrides = []): array
    {
        return [
            'title' => $ad->title,
            'category_id' => $ad->category_id,
            'account_user_id' => $ad->account_user_id,
            'status' => $ad->status->value,
            'text' => $ad->text,
            'price' => $ad->price,
            ...$overrides,
        ];
    }

    public function test_the_queue_lists_only_ads_waiting_for_a_moderator(): void
    {
        $user = $this->user();
        $category = $this->category();
        $this->ad($user, $category, ['title' => 'Waiting sofa', 'status' => AdStatus::Moderation]);
        $this->ad($user, $category, ['title' => 'Live lamp']);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.classified_moderation.index'))
            ->assertOk()
            ->assertSee('Waiting sofa')
            ->assertDontSee('Live lamp');
    }

    public function test_a_moderator_approves_from_the_queue_and_the_owner_is_told(): void
    {
        Mail::fake();
        $user = $this->user();
        $ad = $this->ad($user, $this->category(), ['status' => AdStatus::Moderation, 'expires_at' => null, 'published_at' => null]);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.classified_moderation.update', $ad->id), $this->adminForm($ad, ['status' => 'active']))
            ->assertSessionHasNoErrors();

        $ad->refresh();
        $this->assertSame(AdStatus::Active, $ad->status);
        $this->assertNotNull($ad->published_at);
        $this->assertNotNull($ad->expires_at);
        Mail::assertSent(StatusMail::class, fn (StatusMail $mail) => $mail->hasTo($user->email) && $mail->ad->is($ad));
    }

    public function test_rejecting_needs_a_reason_and_the_owner_reads_it(): void
    {
        Mail::fake();
        $user = $this->user();
        $ad = $this->ad($user, $this->category(), ['status' => AdStatus::Moderation]);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.classified_moderation.update', $ad->id), $this->adminForm($ad, ['status' => 'rejected', 'reject_reason' => '']))
            ->assertSessionHasErrors('reject_reason');
        $this->assertSame(AdStatus::Moderation, $ad->refresh()->status);
        Mail::assertNothingSent();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.classified_moderation.update', $ad->id), $this->adminForm($ad, ['status' => 'rejected', 'reject_reason' => 'No photos of the item']))
            ->assertSessionHasNoErrors();

        $this->assertSame(AdStatus::Rejected, $ad->refresh()->status);
        Mail::assertSent(StatusMail::class);

        $this->actingAs($user, 'web')->get(route('atrium.classifieds.cabinet.ads'))
            ->assertOk()
            ->assertSee('No photos of the item');
    }

    public function test_the_workflow_refuses_a_blank_reason(): void
    {
        $ad = $this->ad($this->user(), $this->category(), ['status' => AdStatus::Moderation]);

        $this->expectException(ValidationException::class);
        app(AdWorkflow::class)->reject($ad, '   ');
    }

    public function test_approving_clears_the_flags_and_rejecting_keeps_the_reason(): void
    {
        $workflow = app(AdWorkflow::class);
        $category = $this->category();
        $user = $this->user();
        $first = $this->ad($user, $category, ['status' => AdStatus::Moderation]);
        $first->flags = ['prepayment'];
        $first->save();
        $second = $this->ad($user, $category, ['status' => AdStatus::Moderation]);

        $workflow->approve($first);
        $workflow->reject($second, '  Duplicate of #1 ');

        $this->assertNull($first->refresh()->flags);
        $this->assertSame('Duplicate of #1', $second->refresh()->reject_reason);
    }

    public function test_the_owner_gets_no_mail_for_their_own_renewal(): void
    {
        Mail::fake();
        $user = $this->user();
        $ad = $this->ad($user, $this->category(), ['status' => AdStatus::Expired, 'expires_at' => now()->subDay()]);

        $this->actingAs($user)->post(route('atrium.classifieds.cabinet.renew', $ad->id))->assertRedirect();

        $this->assertSame(AdStatus::Active, $ad->refresh()->status);
        Mail::assertNothingSent();
    }

    public function test_a_failing_mailer_does_not_stop_the_decision(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));
        $ad = $this->ad($this->user(), $this->category(), ['status' => AdStatus::Moderation]);

        app(AdWorkflow::class)->approve($ad);

        $this->assertSame(AdStatus::Active, $ad->refresh()->status);
    }

    public function test_no_mail_when_the_owner_has_no_address(): void
    {
        Mail::fake();
        $ad = $this->ad(4242, $this->category(), ['status' => AdStatus::Moderation]);

        app(AdWorkflow::class)->approve($ad);

        Mail::assertNothingSent();
    }

    public function test_a_visitor_complains_and_one_person_counts_once(): void
    {
        $ad = $this->ad($this->user(), $this->category());

        $this->post(route('atrium.classifieds.complain', $ad->id), ['reason' => 'fraud', 'text' => ' Asks for a card number '])
            ->assertRedirect($ad->url().'#complain')
            ->assertSessionHas('atrium-classifieds.status');
        $this->post(route('atrium.classifieds.complain', $ad->id), ['reason' => 'duplicate']);

        $complaint = Complaint::query()->sole();
        $this->assertSame(ComplaintReason::Fraud, $complaint->reason);
        $this->assertSame('Asks for a card number', $complaint->text);
        $this->assertNull($complaint->user_id);
        $this->assertSame(AdStatus::Active, $ad->refresh()->status);
    }

    public function test_enough_people_send_the_ad_back_to_the_moderators(): void
    {
        $this->setting('complaints_to_review', 2);
        $ad = $this->ad($this->user(), $this->category());

        $this->actingAs($this->user('Ann Lee'))->post(route('atrium.classifieds.complain', $ad->id), ['reason' => 'sold']);
        $this->assertSame(AdStatus::Active, $ad->refresh()->status);

        $this->actingAs($this->user('Bo Grant'))->post(route('atrium.classifieds.complain', $ad->id), ['reason' => 'prohibited']);

        $ad->refresh();
        $this->assertSame(AdStatus::Moderation, $ad->status);
        $this->assertSame(['complaints'], $ad->flags);
        $this->actingAs($this->user('Cy Moss'))->get($ad->url())->assertNotFound();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.classified_complaints.index'))
            ->assertOk()
            ->assertSee($ad->title);
    }

    public function test_a_bad_reason_or_an_unpublished_ad_is_refused(): void
    {
        $category = $this->category();
        $ad = $this->ad($this->user(), $category);
        $draft = $this->ad($this->user('Sam Taylor'), $category, ['status' => AdStatus::Draft]);

        $this->post(route('atrium.classifieds.complain', $ad->id), ['reason' => 'boring'])->assertSessionHasErrors('reason');
        $this->post(route('atrium.classifieds.complain', $ad->id), ['reason' => 'other', 'text' => str_repeat('x', 1001)])->assertSessionHasErrors('text');
        $this->post(route('atrium.classifieds.complain', $draft->id), ['reason' => 'fraud'])->assertNotFound();
        $this->post(route('atrium.classifieds.complain', 999), ['reason' => 'fraud'])->assertNotFound();

        $this->assertSame(0, Complaint::query()->count());
    }

    public function test_complaints_from_one_address_are_rate_limited(): void
    {
        $category = $this->category();
        $user = $this->user();

        for ($i = 0; $i < 10; $i++) {
            $ad = $this->ad($user, $category, ['title' => 'Ad '.$i]);
            $this->post(route('atrium.classifieds.complain', $ad->id), ['reason' => 'other'])->assertRedirect();
        }

        $last = $this->ad($user, $category);
        $this->post(route('atrium.classifieds.complain', $last->id), ['reason' => 'other'])->assertStatus(429);
        $this->assertSame(10, Complaint::query()->count());
    }

    public function test_every_admin_section_opens(): void
    {
        $user = $this->user();
        $category = $this->category();
        $ad = $this->ad($user, $category, ['city_id' => $this->city()->id]);
        Complaint::query()->create(['ad_id' => $ad->id, 'fingerprint' => 'x', 'reason' => 'fraud']);
        $admin = $this->admin();

        foreach (['classified_ads', 'classified_moderation', 'classified_complaints', 'classified_categories', 'classified_regions', 'classified_cities', 'classified_stop_words', 'classified_boosts'] as $prefix) {
            $this->actingAs($admin, 'admin')->get(route('admin.'.$prefix.'.index'))->assertOk();
        }

        $this->actingAs($admin, 'admin')->get(route('admin.classified_ads.edit', $ad->id))->assertOk();
        $this->actingAs($admin, 'admin')->get(route('admin.classified_ads.create'))->assertOk();
    }
}
