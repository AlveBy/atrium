<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Tracker\Models\Project;
use Atrium\Tracker\Models\Task;
use Laravel\Dusk\Browser;

/**
 * The main path of the tracker: a task is made in a project, written to,
 * and its history shows it.
 */
final class TrackerTest extends AdminTestCase
{
    public function test_an_administrator_creates_a_task_and_comments_on_it(): void
    {
        $admin = $this->admin();
        $project = Project::query()->create(['name' => 'Demo shop', 'key' => 'SHOP']);

        $this->browse(function (Browser $browser) use ($admin, $project): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/tracker_tasks/create')
                ->waitFor('#title')
                ->type('#title', 'Show the delivery cost')
                ->select('#project_id', (string) $project->id)
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/tracker_tasks')
                ->waitForText('SHOP-1')
                ->assertSee('Show the delivery cost')
                ->screenshot('tracker-tasks');

            $this->assertInPanel($browser, 'admin.tracker_tasks.index');

            $task = Task::query()->sole();
            $this->assertSame('SHOP-1', $task->code);
            $this->assertSame($admin->id, $task->author_id);

            $browser->visit("/admin/tracker_tasks/{$task->id}/edit")
                ->waitFor('#title')
                ->assertSee('Comments and history')
                ->click('[data-tab="activity"]')
                ->waitFor('#new_comment')
                ->type('#new_comment', 'Taking this one.')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/tracker_tasks');

            $this->assertSame(['Taking this one.'], $task->comments()->pluck('body')->all());
        });
    }
}
