<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Admin\Modules\Modules;
use Atrium\Admin\Modules\Settings;
use Atrium\Tracker\Mail\TaskMail;
use Atrium\Tracker\Models\Comment;
use Atrium\Tracker\Models\Event;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Tests\TestCase;
use Illuminate\Support\Facades\Mail;

/**
 * The demo tasks, one by one: they are the first thing a new installation shows.
 */
final class DemoDataTest extends TestCase
{
    public function test_the_demo_tasks_are_these(): void
    {
        $admin = $this->admin();
        $module = app(Modules::class)->find('alveby/atrium-tracker');
        $this->assertNotNull($module);
        app(Modules::class)->seedDemo($module);

        $rows = Task::query()->orderBy('id')->get()->map(fn (Task $task): array => [
            $task->code, $task->title, $task->type, $task->status, $task->priority,
            $task->assignee_id === null ? null : $task->assignee_id === $admin->id,
            $task->labels,
            $task->due_date === null ? null : (int) today()->diffInDays($task->due_date, false),
            $task->estimate_hours === null ? null : (float) $task->estimate_hours,
            $task->parent?->code,
            Event::query()->where('task_id', $task->id)->where('kind', 'status')->count(),
            Comment::query()->where('task_id', $task->id)->pluck('author_id')->all() === ($task->assignee_id === null ? [] : [$admin->id]),
        ])->all();

        $this->assertSame([
            ['SHOP-1', 'Checkout redesign', 'epic', 'in_progress', 'high', true, ['checkout'], 14, null, null, 1, true],
            ['SHOP-2', 'Show delivery cost before payment', 'story', 'in_progress', 'high', true, ['checkout', 'delivery'], 3, 8.0, 'SHOP-1', 1, true],
            ['SHOP-3', 'Fix the promo code field on mobile', 'bug', 'review', 'highest', true, ['checkout', 'mobile'], -2, 2.0, 'SHOP-1', 1, true],
            ['SHOP-4', 'Save the cart for signed-in customers', 'story', 'todo', 'medium', null, ['checkout'], 21, 16.0, 'SHOP-1', 0, true],
            ['SHOP-5', 'Prices in the catalogue are rounded wrong', 'bug', 'todo', 'high', true, ['catalogue'], -5, 4.0, null, 0, true],
            ['SHOP-6', 'Update the delivery terms page', 'task', 'done', 'low', true, ['content'], null, 1.0, null, 1, true],
            ['WEB-1', 'Write the news post about the autumn sale', 'task', 'in_progress', 'medium', true, ['content', 'news'], 2, 3.0, null, 1, true],
            ['WEB-2', 'Contact form sends nothing on Safari', 'bug', 'review', 'highest', true, ['forms'], -1, 4.0, null, 1, true],
            ['WEB-3', 'Add the new team photos', 'task', 'todo', 'lowest', null, ['content'], 30, 2.0, null, 0, true],
            ['WEB-4', 'Speed up the home page', 'story', 'done', 'medium', true, ['performance'], null, 12.0, null, 1, true],
        ], $rows);
    }

    public function test_the_demo_starts_every_task_at_the_first_status(): void
    {
        $this->admin();
        $module = app(Modules::class)->find('alveby/atrium-tracker');
        $this->assertNotNull($module);
        app(Modules::class)->seedDemo($module);

        $this->assertSame(
            ['todo'],
            Event::query()->where('kind', 'status')->orderBy('id')->get()->pluck('old_value')->unique()->values()->all(),
        );
    }

    public function test_the_letters_go_out_even_when_the_module_is_not_listed_yet(): void
    {
        Mail::fake();
        $bob = $this->admin(name: 'Bob');
        config(['atrium.modules' => []]);
        $this->app->make(Settings::class)->flush();
        $this->actingAs($this->admin(name: 'Alice'), 'admin');

        $task = $this->task(null, ['assignee_id' => $bob->id]);
        $task->update(['status' => 'review']);

        Mail::assertSent(TaskMail::class, 2);
    }
}
