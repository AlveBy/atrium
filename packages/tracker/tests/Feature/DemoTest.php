<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Admin\Modules\Modules;
use Atrium\Tracker\Mail\TaskMail;
use Atrium\Tracker\Models\Comment;
use Atrium\Tracker\Models\Project;
use Atrium\Tracker\Models\Status;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Support\Notifier;
use Atrium\Tracker\Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * The demo projects of `atrium:install --demo`.
 */
final class DemoTest extends TestCase
{
    private function seed_(): bool
    {
        $module = app(Modules::class)->find('alveby/atrium-tracker');
        $this->assertNotNull($module);

        return app(Modules::class)->seedDemo($module);
    }

    public function test_the_demo_fills_the_tracker_once(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $this->assertTrue($this->seed_());
        $this->assertSame(['SHOP', 'WEB'], Project::query()->orderBy('key')->pluck('key')->all());
        $this->assertSame(10, Task::query()->count());
        $this->assertSame(8, Comment::query()->count(), 'Everyone who has a task says a word on it.');
        $this->assertSame([$admin->id], Project::query()->pluck('lead_id')->unique()->values()->all());

        $this->assertFalse($this->seed_());
        $this->assertSame(10, Task::query()->count());
        Mail::assertNothingSent();
    }

    public function test_the_demo_has_tasks_at_every_status_with_an_epic_late_ones_and_a_history(): void
    {
        $this->admin();
        $this->seed_();

        $this->assertSame(['todo', 'in_progress', 'review', 'done'], Task::query()->distinct()->orderByRaw("case status when 'todo' then 0 when 'in_progress' then 1 when 'review' then 2 else 3 end")->pluck('status')->all());

        $epic = Task::query()->where('type', 'epic')->sole();
        $this->assertSame(3, $epic->children()->count());
        $this->assertSame('SHOP-1', $epic->code);

        $this->assertSame(['SHOP-3', 'SHOP-5', 'WEB-2'], Task::query()->with('project')->where('due_date', '<', today())->where('status', '!=', 'done')->get()->map(fn (Task $task): string => $task->code)->sort()->values()->all());

        $done = Task::query()->where('status', 'done')->first();
        $this->assertNotNull($done?->completed_at);
        $this->assertSame(['created', 'status'], $done->events()->orderBy('id')->pluck('kind')->all());
    }

    public function test_the_demo_without_an_administrator_leaves_the_tasks_unassigned(): void
    {
        $this->seed_();

        $this->assertSame(10, Task::query()->count());
        $this->assertSame(0, Task::query()->whereNotNull('assignee_id')->count());
        $this->assertSame(0, Comment::query()->count());
        $this->assertNull(Project::query()->first()?->lead_id);
    }

    public function test_the_demo_goes_by_the_statuses_of_the_table(): void
    {
        $this->admin();
        Status::query()->get()->each->delete();
        Status::query()->create(['code' => 'open', 'name' => 'Open', 'sort' => 1, 'category' => Status::TODO]);
        Status::query()->create(['code' => 'closed', 'name' => 'Closed', 'sort' => 2, 'category' => Status::DONE]);
        $this->seed_();

        $this->assertSame(['closed', 'open'], Task::query()->distinct()->orderBy('status')->pluck('status')->all());
    }

    public function test_the_demo_does_not_touch_a_tracker_that_has_content(): void
    {
        $this->project(['name' => 'Mine', 'key' => 'MINE']);

        $this->assertFalse($this->seed_());
        $this->assertSame(1, Project::query()->count());
        $this->assertSame(0, Task::query()->count());
    }

    public function test_silence_ends_with_the_work_even_when_it_fails(): void
    {
        Mail::fake();
        $bob = $this->admin(name: 'Bob');

        try {
            Notifier::silently(static fn () => throw new RuntimeException('boom'));
        } catch (RuntimeException) {
        }

        $this->task(null, ['assignee_id' => $bob->id]);
        Mail::assertSent(TaskMail::class, 1);

        Notifier::silently(function () use ($bob): void {
            Notifier::silently(fn () => null);
            $this->task(null, ['assignee_id' => $bob->id]);
        });
        Mail::assertSent(TaskMail::class, 1);
    }
}
