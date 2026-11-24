<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests;

use Atrium\Integrations\Alerts\FailureMail;
use Atrium\Integrations\Exchange;
use Atrium\Integrations\Models\ExchangeLog;
use Atrium\Integrations\Tests\Fixtures\BareJob;
use Atrium\Integrations\Tests\Fixtures\ProbeJob;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;

/**
 * What one run of a job writes to the log, attempt by attempt; the queue's
 * own retries are in QueueRetriesTest.
 */
final class IntegrationJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->admin();
    }

    /** The job as the worker runs it on its $attempt-th try. */
    private function onAttempt(ProbeJob $job, int $attempt): ProbeJob
    {
        $queueJob = Mockery::mock(Job::class);
        $queueJob->shouldReceive('attempts')->andReturn($attempt);
        $job->setJob($queueJob);

        return $job;
    }

    public function test_a_job_that_works_writes_one_line_with_what_it_did_and_how_long_it_took(): void
    {
        $job = new ProbeJob(secret: 'KEY', about: 'Form submission #12');

        $job->handle(app(Exchange::class));

        $row = ExchangeLog::query()->sole();
        $this->assertSame('probe', $row->integration);
        $this->assertSame('thing.send', $row->action);
        $this->assertSame('ok', $row->status);
        $this->assertSame('Form submission #12', $row->subject);
        $this->assertSame('Sent with *** on try 1', $row->message, 'a secret is hidden in what was done too');
        $this->assertSame(1, $row->attempt);
        $this->assertNotNull($row->duration_ms);
        $this->assertGreaterThanOrEqual(0, $row->duration_ms);
        Mail::assertNothingOutgoing();
    }

    public function test_the_line_says_how_many_milliseconds_the_call_took(): void
    {
        Carbon::setTestNow('2026-11-24 10:00:00');

        (new ProbeJob(takesMs: 1250))->handle(app(Exchange::class));
        $this->assertSame(1250, ExchangeLog::query()->sole()->duration_ms);

        ExchangeLog::query()->delete();
        $this->onAttempt(new ProbeJob(failTimes: 9, takesMs: 400), 1);
        try {
            $this->onAttempt(new ProbeJob(failTimes: 9, takesMs: 400), 1)->handle(app(Exchange::class));
        } catch (RuntimeException) {
        }
        $this->assertSame(400, ExchangeLog::query()->sole()->duration_ms, 'a failed attempt is timed too');

        Carbon::setTestNow();
    }

    public function test_without_an_about_the_line_has_none(): void
    {
        (new ProbeJob)->handle(app(Exchange::class));

        $this->assertNull(ExchangeLog::query()->sole()->subject);
    }

    public function test_a_job_that_names_no_subject_and_no_secrets_still_logs_whole_and_unmasked(): void
    {
        (new BareJob)->handle(app(Exchange::class));

        $line = ExchangeLog::query()->sole();
        $this->assertSame('bare', $line->integration);
        $this->assertNull($line->subject);
        $this->assertNull($line->message);
        $this->assertSame('ok', $line->status);
    }

    public function test_without_named_secrets_nothing_is_masked_in_the_error(): void
    {
        $job = new BareJob(fail: true);
        $job->failed(new RuntimeException('Refused by https://hooks.example.test/x'));

        $this->assertSame('Refused by https://hooks.example.test/x', ExchangeLog::query()->sole()->error);
    }

    public function test_a_failure_before_the_last_attempt_is_a_retry_line_and_the_error_goes_on(): void
    {
        $job = $this->onAttempt(new ProbeJob(failTimes: 9, secret: 'KEY'), 2);

        try {
            $job->handle(app(Exchange::class));
            $this->fail('the exception must reach the queue so that it retries');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('KEY', $e->getMessage(), 'the queue gets the real error');
        }

        $row = ExchangeLog::query()->sole();
        $this->assertSame('retry', $row->status);
        $this->assertSame(2, $row->attempt);
        $this->assertSame('Could not reach https://hooks.example.test/***/add (try 1)', $row->error);
        $this->assertNull($row->message);
        $this->assertNotNull($row->duration_ms);
        Mail::assertNothingOutgoing();
    }

    public function test_the_last_attempt_leaves_its_line_to_failed_and_failed_tells_the_administrators(): void
    {
        $job = $this->onAttempt(new ProbeJob(failTimes: 9, secret: 'KEY'), 5);

        try {
            $job->handle(app(Exchange::class));
            $this->fail('expected the exception');
        } catch (RuntimeException $e) {
            $this->assertSame(0, ExchangeLog::query()->count(), 'the queue calls failed() next, which writes the line');
            $job->failed($e);
        }

        $row = ExchangeLog::query()->sole();
        $this->assertSame('failed', $row->status);
        $this->assertSame(5, $row->attempt);
        $this->assertSame('Could not reach https://hooks.example.test/***/add (try 1)', $row->error);
        Mail::assertSent(FailureMail::class, 1);
    }

    public function test_a_job_the_queue_killed_is_logged_as_failed_on_its_attempt(): void
    {
        $job = $this->onAttempt(new ProbeJob(secret: 'KEY'), 3);

        $job->failed(new RuntimeException('has timed out for KEY'));

        $row = ExchangeLog::query()->sole();
        $this->assertSame('failed', $row->status);
        $this->assertSame(3, $row->attempt);
        $this->assertSame('has timed out for ***', $row->error);
        $this->assertNull($row->duration_ms);
    }

    public function test_run_by_hand_without_a_queue_it_counts_as_the_first_attempt_and_does_not_promise_a_retry(): void
    {
        try {
            (new ProbeJob(failTimes: 9))->handle(app(Exchange::class));
            $this->fail('expected the exception');
        } catch (RuntimeException) {
        }

        $this->assertSame(0, ExchangeLog::query()->count());
    }

    public function test_the_sync_queue_never_retries_so_its_failure_is_one_failed_line(): void
    {
        $this->expectException(RuntimeException::class);

        try {
            ProbeJob::dispatchSync(9);
        } finally {
            $row = ExchangeLog::query()->sole();
            $this->assertSame('failed', $row->status);
            $this->assertSame(1, $row->attempt);
            Mail::assertSent(FailureMail::class, 1);
        }
    }

    public function test_the_queue_is_told_five_tries_a_minute_and_the_configured_pauses(): void
    {
        $job = new ProbeJob;

        $this->assertSame(5, $job->tries);
        $this->assertSame(60, $job->timeout);
        $this->assertTrue($job->failOnTimeout);
        $this->assertSame([10, 60, 300, 900], $job->backoff());

        config(['atrium-integrations.backoff' => ['5', 20]]);
        $this->assertSame([5, 20], $job->backoff());

        config(['atrium-integrations.backoff' => ['soon' => 5, 'later' => 20]]);
        $this->assertSame([5, 20], $job->backoff(), 'a list whatever the keys of the config');
    }
}
