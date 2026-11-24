<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Facades\Setting;
use Atrium\Integrations\Alerts\FailureAlert;
use Atrium\Integrations\Alerts\FailureMail;
use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

final class FailureAlertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-24 10:00:00');
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function entry(string $integration = 'bitrix24', ?string $subject = 'Form submission #12', ?string $error = 'HTTP 401'): ExchangeLog
    {
        return new ExchangeLog([
            'integration' => $integration, 'action' => 'lead.add', 'status' => 'failed',
            'subject' => $subject, 'error' => $error, 'attempt' => 5,
        ]);
    }

    public function test_the_letter_says_what_failed_about_what_and_why(): void
    {
        $entry = $this->entry();

        $mail = new FailureMail($entry);

        $this->assertSame('Demo site: integration bitrix24 failed', $mail->envelope()->subject);
        $mail->assertSeeInText('Integration bitrix24 could not do lead.add and has stopped trying.');
        $mail->assertSeeInText('About: Form submission #12');
        $mail->assertSeeInText('Error: HTTP 401');
        $mail->assertSeeInText('Attempts made: 5');
        $this->assertTrue($mail->hasMetadata('atrium_purpose', 'integrations'));
    }

    public function test_a_line_without_a_subject_or_an_error_leaves_them_out_or_dashes_them(): void
    {
        $mail = new FailureMail($this->entry(subject: null, error: null));

        $mail->assertDontSeeInText('About:');
        $mail->assertSeeInText('Error: —');
    }

    public function test_the_letter_hands_its_lines_to_the_view_in_order_without_gaps(): void
    {
        $with = (new FailureMail($this->entry(subject: null)))->content()->with;

        $this->assertSame([
            'Integration bitrix24 could not do lead.add and has stopped trying.',
            'Error: HTTP 401',
            'Attempts made: 5',
        ], $with['lines']);
    }

    public function test_the_letter_links_to_the_log_when_the_screen_exists(): void
    {
        (new FailureMail($this->entry()))->assertSeeInText(route('admin.integration_logs.index'));
    }

    public function test_the_hook_fires_for_every_failure_even_when_the_letter_is_held_back(): void
    {
        $seen = [];
        Hook::on('atrium.integrations.failed', function (ExchangeLog $entry) use (&$seen): void {
            $seen[] = $entry->integration;
        });
        $this->admin();

        app(FailureAlert::class)->send($this->entry('1c'));
        app(FailureAlert::class)->send($this->entry('1c'));

        $this->assertSame(['1c', '1c'], $seen);
        Mail::assertSent(FailureMail::class, 1);
    }

    public function test_the_letter_goes_to_the_listed_addresses_cleaned_and_once_each(): void
    {
        $this->admin();
        Setting::save('integrations', ['alert_emails' => "ops@example.test, ops@example.test\nlead@example.test;bad@@x  second@example.test"]);

        $this->assertTrue(app(FailureAlert::class)->send($this->entry()));

        Mail::assertSent(FailureMail::class, fn (FailureMail $mail): bool => $mail->hasTo('ops@example.test') && $mail->hasTo('lead@example.test') && $mail->hasTo('second@example.test') && count($mail->to) === 3);
    }

    public function test_without_addresses_every_active_superadministrator_is_told_and_nobody_else(): void
    {
        $first = $this->admin();
        $second = $this->admin();
        $this->admin(super: false);
        $this->admin(active: false);

        $this->assertSame([$first->email, $second->email], app(FailureAlert::class)->recipients());

        $this->assertTrue(app(FailureAlert::class)->send($this->entry()));
        Mail::assertSent(FailureMail::class, fn (FailureMail $mail): bool => count($mail->to) === 2);
    }

    public function test_addresses_that_are_all_wrong_fall_back_to_the_superadministrators(): void
    {
        $admin = $this->admin();
        Setting::save('integrations', ['alert_emails' => "  \n ,; nonsense"]);

        $this->assertSame([$admin->email], app(FailureAlert::class)->recipients());
    }

    public function test_nobody_to_tell_means_no_letter_and_the_hour_is_not_used_up(): void
    {
        $this->assertFalse(app(FailureAlert::class)->send($this->entry()));
        Mail::assertNothingOutgoing();

        $this->admin();
        $this->assertTrue(app(FailureAlert::class)->send($this->entry()), 'the first failure after an address appears is told');
    }

    public function test_one_letter_per_integration_per_interval_then_another(): void
    {
        $this->admin();
        $alert = app(FailureAlert::class);

        $this->assertTrue($alert->send($this->entry('1c')));
        $this->assertFalse($alert->send($this->entry('1c')), 'the same integration within the hour');
        $this->assertTrue($alert->send($this->entry('bitrix24')), 'another integration has its own hour');

        Carbon::setTestNow('2026-11-24 10:59:00');
        $this->assertFalse($alert->send($this->entry('1c')));

        Carbon::setTestNow('2026-11-24 11:01:00');
        $this->assertTrue($alert->send($this->entry('1c')), 'an hour later it speaks again');
    }

    public function test_the_hour_is_kept_in_the_cache_under_the_integration_and_ends_on_the_dot(): void
    {
        $this->admin();
        $alert = app(FailureAlert::class);

        $alert->send($this->entry('1c'));

        $this->assertTrue(Cache::get('atrium-integrations:alert:1c'));
        Carbon::setTestNow('2026-11-24 10:59:59');
        $this->assertFalse($alert->send($this->entry('1c')));
        Carbon::setTestNow('2026-11-24 11:00:00');
        $this->assertTrue($alert->send($this->entry('1c')));
    }

    public function test_the_addresses_are_listed_without_repeats_and_in_order(): void
    {
        Setting::save('integrations', ['alert_emails' => "a@example.test\na@example.test\nb@example.test"]);

        $this->assertSame(['a@example.test', 'b@example.test'], app(FailureAlert::class)->recipients());
    }

    public function test_a_setting_that_was_saved_empty_means_the_superadministrators(): void
    {
        $admin = $this->admin();
        Setting::save('integrations', ['alert_emails' => null]);

        $this->assertSame([$admin->email], app(FailureAlert::class)->recipients());
    }

    public function test_a_setting_that_is_not_text_is_read_as_text(): void
    {
        $admin = $this->admin();
        Setting::save('integrations', ['alert_emails' => 12345]);

        $this->assertSame([$admin->email], app(FailureAlert::class)->recipients());
    }

    public function test_the_interval_is_the_configured_one_and_never_less_than_a_minute(): void
    {
        $this->admin();
        config(['atrium-integrations.alert_interval' => 5]);
        $alert = app(FailureAlert::class);

        $alert->send($this->entry());
        Carbon::setTestNow('2026-11-24 10:04:00');
        $this->assertFalse($alert->send($this->entry()));
        Carbon::setTestNow('2026-11-24 10:06:00');
        $this->assertTrue($alert->send($this->entry()));

        config(['atrium-integrations.alert_interval' => 0]);
        Carbon::setTestNow('2026-11-24 12:00:00');
        $this->assertTrue($alert->send($this->entry('other')));
        Carbon::setTestNow('2026-11-24 12:00:30');
        $this->assertFalse($alert->send($this->entry('other')), 'zero still means a minute');
        Carbon::setTestNow('2026-11-24 12:01:30');
        $this->assertTrue($alert->send($this->entry('other')));
    }

    public function test_a_letter_that_cannot_be_sent_is_logged_by_the_kind_of_error_only(): void
    {
        $this->admin();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('smtp://user:secret@mail.example.test refused'));
        Log::spy();

        $this->assertFalse(app(FailureAlert::class)->send($this->entry()));

        Log::shouldHaveReceived('warning')->once()->with('Atrium integrations could not send an alert: RuntimeException');
    }
}
