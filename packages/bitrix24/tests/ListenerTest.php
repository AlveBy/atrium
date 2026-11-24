<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Tests;

use Atrium\Admin\Facades\Setting;
use Atrium\Bitrix24\Jobs\SendSubmission;
use Atrium\Bitrix24\Models\FormLink;
use Atrium\Forms\Models\FormSubmission;
use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * When a stored submission is sent to Bitrix24, and when it is not.
 */
final class ListenerTest extends TestCase
{
    public function test_a_stored_submission_is_queued_once_after_the_commit(): void
    {
        Queue::fake();
        $this->connect();

        $submission = $this->submit();

        Queue::assertPushed(SendSubmission::class, 1);
        Queue::assertPushed(SendSubmission::class, fn (SendSubmission $job): bool => $job->afterCommit === true && (fn () => $this->submissionId)->call($job) === $submission->id);
    }

    public function test_without_a_webhook_nothing_is_queued(): void
    {
        Queue::fake();
        Setting::save('bitrix24', ['send_forms' => true]);

        $this->submit();

        Queue::assertNothingPushed();
    }

    public function test_with_sending_switched_off_nothing_is_queued(): void
    {
        Queue::fake();
        $this->connect(sendForms: false);

        $this->submit();

        Queue::assertNothingPushed();
    }

    public function test_a_form_whose_link_is_inactive_stays_out_and_others_still_go(): void
    {
        Queue::fake();
        $this->connect();
        $off = $this->form('Off');
        FormLink::query()->create(['form_id' => $off->id, 'mode' => 'lead', 'active' => false]);
        $on = $this->form('On');
        FormLink::query()->create(['form_id' => $on->id, 'mode' => 'deal', 'active' => true]);

        $this->submit($off);
        Queue::assertNothingPushed();

        $this->submit($on);
        $this->submit($this->form('No link'));
        Queue::assertPushed(SendSubmission::class, 2);
    }

    public function test_when_the_module_is_not_installed_the_site_sends_nothing(): void
    {
        Queue::fake();
        $this->connect();
        config(['atrium.modules' => ['alveby/atrium-forms']]);

        $this->submit();

        Queue::assertNothingPushed();
    }

    public function test_the_whole_way_a_submission_reaches_bitrix24_and_the_log(): void
    {
        $this->connect();
        Http::fake(['*' => Http::response(['result' => 9])]);

        $submission = $this->submit();

        Http::assertSentCount(1);
        $row = ExchangeLog::query()->sole();
        $this->assertSame('Form submission #'.$submission->id, $row->subject);
        $this->assertSame('ok', $row->status);
    }

    public function test_a_portal_that_is_down_loses_nothing_the_submission_is_kept(): void
    {
        $this->connect();
        Http::fake(['*' => Http::response('down', 503)]);

        try {
            $this->submit();
        } catch (\Throwable) {
            // The sync queue raises what a worker would retry.
        }

        $this->assertSame(1, FormSubmission::withoutGlobalScopes()->count());
    }
}
