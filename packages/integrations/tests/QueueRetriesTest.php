<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests;

use Atrium\Integrations\Alerts\FailureMail;
use Atrium\Integrations\Models\ExchangeLog;
use Atrium\Integrations\Tests\Fixtures\ProbeJob;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * The real queue worker, on the database queue: what a connector's job
 * leaves in the log when it works at once, works at the third attempt and
 * never works.
 */
final class QueueRetriesTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('queue.default', 'database');
        $app['config']->set('queue.failed.driver', 'null');
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-24 10:00:00');
        Mail::fake();
        $this->admin();
        Cache::forget('probe-job-tries');

        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** One pass of the worker, an hour later than the last, so no pause is left to wait out. */
    private function work(): void
    {
        Carbon::setTestNow(now()->addHour());
        $this->artisan('queue:work', ['--once' => true, '--sleep' => 0, '--queue' => 'default'])->assertSuccessful();
    }

    /**
     * @return list<string>
     */
    private function statuses(): array
    {
        return ExchangeLog::query()->orderBy('id')->get()->map(fn (ExchangeLog $row): string => $row->status.':'.$row->attempt)->all();
    }

    public function test_a_job_that_works_leaves_one_ok_line_and_nothing_in_the_queue(): void
    {
        ProbeJob::dispatch(0, 'KEY', 'Form submission #1');

        $this->work();

        $this->assertSame(['ok:1'], $this->statuses());
        $this->assertSame(0, DB::table('jobs')->count());
        Mail::assertNothingOutgoing();
    }

    public function test_a_job_that_fails_twice_retries_and_the_third_attempt_is_a_success(): void
    {
        ProbeJob::dispatch(2, 'KEY');

        $this->work();
        $this->assertSame(['retry:1'], $this->statuses());
        $this->assertSame(1, DB::table('jobs')->count(), 'waiting for the next attempt');

        $this->work();
        $this->work();

        $this->assertSame(['retry:1', 'retry:2', 'ok:3'], $this->statuses());
        $this->assertSame(0, DB::table('jobs')->count());
        Mail::assertNothingOutgoing();
    }

    public function test_a_job_that_never_works_is_tried_five_times_and_then_the_administrators_are_told_once(): void
    {
        ProbeJob::dispatch(99, 'KEY');

        foreach (range(1, 5) as $ignored) {
            $this->work();
        }

        $this->assertSame(['retry:1', 'retry:2', 'retry:3', 'retry:4', 'failed:5'], $this->statuses());
        $this->assertSame(0, DB::table('jobs')->count(), 'given up, not left to loop');
        Mail::assertSent(FailureMail::class, 1);

        $this->work();
        $this->assertCount(5, $this->statuses(), 'nothing more is tried');
    }

    public function test_the_pause_before_the_next_attempt_follows_the_configured_backoff(): void
    {
        ProbeJob::dispatch(99);

        $this->artisan('queue:work', ['--once' => true, '--sleep' => 0])->assertSuccessful();

        $this->assertSame(Carbon::now()->getTimestamp() + 10, (int) DB::table('jobs')->value('available_at'));
    }
}
