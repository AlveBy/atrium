<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Database;

use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Modules\DemoSeeder;
use Atrium\Tracker\Models\Comment;
use Atrium\Tracker\Models\Project;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Support\Notifier;
use Atrium\Tracker\Support\Statuses;

/**
 * Two projects of a small team and the tasks in them, at every status, with
 * an epic, subtasks, labels, late and coming due dates, comments and a
 * history. The first administrator is the lead and has the tasks; nobody is
 * written to.
 */
final class TrackerDemoSeeder extends DemoSeeder
{
    public function run(): bool
    {
        if (Project::query()->exists()) {
            return false;
        }

        $admin = AdminUser::query()->orderBy('id')->first();

        Notifier::silently(function () use ($admin): void {
            $this->shop($admin);
            $this->site($admin);
        });

        return true;
    }

    private function shop(?AdminUser $admin): void
    {
        $project = Project::query()->create([
            'name' => 'Demo shop', 'key' => 'SHOP', 'lead_id' => $admin?->id,
            'description' => 'The online shop: catalogue, checkout and delivery.',
        ]);

        $epic = $this->task($project, 'Checkout redesign', 'epic', 1, 'high', $admin, ['checkout'], 14, null);
        $this->task($project, 'Show delivery cost before payment', 'story', 1, 'high', $admin, ['checkout', 'delivery'], 3, 8, $epic);
        $this->task($project, 'Fix the promo code field on mobile', 'bug', 2, 'highest', $admin, ['checkout', 'mobile'], -2, 2, $epic);
        $this->task($project, 'Save the cart for signed-in customers', 'story', 0, 'medium', null, ['checkout'], 21, 16, $epic);
        $this->task($project, 'Prices in the catalogue are rounded wrong', 'bug', 0, 'high', $admin, ['catalogue'], -5, 4);
        $this->task($project, 'Update the delivery terms page', 'task', null, 'low', $admin, ['content'], null, 1);
    }

    private function site(?AdminUser $admin): void
    {
        $project = Project::query()->create([
            'name' => 'Company website', 'key' => 'WEB', 'lead_id' => $admin?->id,
            'description' => 'The site of the company: pages, news and the contact form.',
        ]);

        $this->task($project, 'Write the news post about the autumn sale', 'task', 1, 'medium', $admin, ['content', 'news'], 2, 3);
        $this->task($project, 'Contact form sends nothing on Safari', 'bug', 2, 'highest', $admin, ['forms'], -1, 4);
        $this->task($project, 'Add the new team photos', 'task', 0, 'lowest', null, ['content'], 30, 2);
        $this->task($project, 'Speed up the home page', 'story', null, 'medium', $admin, ['performance'], null, 12);
    }

    /**
     * A task that has been through the board: it starts at the first status
     * and moves to the one that many steps on (the last one when it is null or
     * the settings list fewer), so its history shows the way, and the assignee leaves a
     * comment on it.
     *
     * @param  list<string>  $labels
     */
    private function task(Project $project, string $title, string $type, ?int $steps, string $priority, ?AdminUser $assignee, array $labels, ?int $dueInDays, int|float|null $estimate, ?Task $parent = null): Task
    {
        $statuses = array_keys(Statuses::options());

        $task = Task::query()->create([
            'project_id' => $project->id, 'type' => $type, 'title' => $title, 'priority' => $priority,
            'status' => $statuses[0], 'labels' => $labels, 'parent_id' => $parent?->id,
            'due_date' => $dueInDays === null ? null : now()->addDays($dueInDays)->toDateString(),
            'estimate_hours' => $estimate, 'assignee_id' => $assignee?->id,
        ]);

        $task->update(['status' => $statuses[min($steps ?? PHP_INT_MAX, count($statuses) - 1)]]);

        if ($assignee !== null) {
            Comment::query()->create(['task_id' => $task->id, 'author_id' => $assignee->id, 'body' => 'Taking this one.']);
        }

        return $task;
    }
}
