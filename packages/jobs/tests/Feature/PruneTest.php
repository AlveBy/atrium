<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Tests\Feature;

use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;

/**
 * Personal data is not kept for ever: old applications go with their CVs.
 */
final class PruneTest extends TestCase
{
    public function test_applications_untouched_for_the_retention_period_are_deleted_with_their_cvs(): void
    {
        Storage::fake('local');
        config(['atrium-jobs.retention_days' => 30]);

        $old = $this->stored('old', now()->subDays(31));
        $recent = $this->stored('recent', now()->subDays(29));

        $this->artisan('atrium:jobs:prune')->expectsOutputToContain('Deleted 1 application(s)')->assertSuccessful();

        $this->assertNull(Application::query()->find($old->id));
        $this->assertNotNull(Application::query()->find($recent->id));
        Storage::disk('local')->assertMissing('jobs/cv/old.pdf');
        Storage::disk('local')->assertExists('jobs/cv/recent.pdf');

        $this->artisan('atrium:jobs:prune', ['--days' => 10])->assertSuccessful();
        $this->assertSame(0, Application::query()->count());
    }

    public function test_zero_days_keeps_everything_and_nonsense_is_refused(): void
    {
        Storage::fake('local');
        config(['atrium-jobs.retention_days' => 0]);
        $this->stored('old', now()->subYears(3));

        $this->artisan('atrium:jobs:prune')->assertSuccessful();
        $this->artisan('atrium:jobs:prune', ['--days' => 'soon'])->assertFailed();

        $this->assertSame(1, Application::query()->count());
    }

    public function test_it_runs_every_day(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($event): bool => str_contains((string) $event->command, 'atrium:jobs:prune'));

        $this->assertCount(1, $events);
        $this->assertSame('0 0 * * *', $events->first()->expression);
    }

    private function stored(string $name, \DateTimeInterface $updated): Application
    {
        Storage::disk('local')->put("jobs/cv/{$name}.pdf", 'pdf');

        $application = new Application;
        $application->forceFill(['name' => $name, 'email' => "{$name}@example.com", 'cv_path' => "jobs/cv/{$name}.pdf"])->save();
        Application::query()->whereKey($application->id)->update(['updated_at' => $updated]);

        return $application;
    }
}
