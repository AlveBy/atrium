<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests;

use Atrium\Admin\Facades\Setting;
use Atrium\Integrations\IntegrationsServiceProvider;
use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\Attributes\DefineEnvironment;

final class PruneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-24 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function scheduleOff($app): void
    {
        $app['config']->set('atrium-integrations.schedule', false);
    }

    private function line(string $when): void
    {
        ExchangeLog::query()->create(['integration' => '1c', 'action' => 'x', 'status' => 'ok', 'created_at' => Carbon::parse($when)]);
    }

    /** @return array<int,string> */
    private function scheduled(): array
    {
        $events = array_filter(app(Schedule::class)->events(), static fn ($event): bool => str_contains((string) $event->command, 'atrium:integrations'));

        return array_values(array_map(static fn ($event): string => preg_replace('/^.*artisan.? /', '', (string) $event->command).' '.$event->expression.' lock '.($event->withoutOverlapping ? $event->expiresAt : 'none'), $events));
    }

    public function test_lines_older_than_ninety_days_go_and_the_rest_stay(): void
    {
        $this->line('2026-08-26 11:59:59');
        $this->line('2026-08-26 12:00:00');
        $this->line('2026-11-24 11:00:00');

        $this->artisan('atrium:integrations:prune')->expectsOutput('Deleted: 1')->assertSuccessful();

        $this->assertSame(2, ExchangeLog::query()->count());
        $this->assertSame(0, ExchangeLog::query()->where('created_at', '<', '2026-08-26 12:00:00')->count(), 'a line exactly 90 days old stays');
    }

    public function test_the_period_is_the_one_set_in_the_admin(): void
    {
        Setting::save('integrations', ['retention_days' => 7]);
        $this->line('2026-11-16 12:00:00');
        $this->line('2026-11-18 12:00:00');

        Artisan::call('atrium:integrations:prune');

        $this->assertSame(1, ExchangeLog::query()->count());
    }

    public function test_a_period_of_nothing_still_keeps_a_day(): void
    {
        Setting::save('integrations', ['retention_days' => 0]);
        $this->line('2026-11-24 11:00:00');
        $this->line('2026-11-23 13:00:00');
        $this->line('2026-11-23 00:00:00');

        Artisan::call('atrium:integrations:prune');

        $this->assertSame(['2026-11-23 13:00:00', '2026-11-24 11:00:00'], ExchangeLog::query()->orderBy('created_at')->get()->map(fn (ExchangeLog $line): string => $line->created_at->format('Y-m-d H:i:s'))->all());
    }

    public function test_an_empty_log_is_not_a_problem(): void
    {
        $this->artisan('atrium:integrations:prune')->expectsOutput('Deleted: 0')->assertSuccessful();
    }

    public function test_the_clean_up_runs_nightly_with_a_lock(): void
    {
        $this->assertSame(['atrium:integrations:prune 41 3 * * * lock 60'], $this->scheduled());
        $this->assertArrayHasKey(IntegrationsServiceProvider::class, ServiceProvider::$publishes);
    }

    #[DefineEnvironment('scheduleOff')]
    public function test_the_schedule_can_be_switched_off(): void
    {
        $this->assertSame([], $this->scheduled());
        $this->assertArrayHasKey('atrium:integrations:prune', Artisan::all());
    }
}
