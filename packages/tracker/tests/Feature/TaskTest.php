<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\Tracker\Models\Comment;
use Atrium\Tracker\Models\Event;
use Atrium\Tracker\Models\Project;
use Atrium\Tracker\Models\Status;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A task is numbered in its project, remembers who made it, when it was
 * closed and what was changed in it.
 */
final class TaskTest extends TestCase
{
    public function test_tasks_are_numbered_in_their_own_project(): void
    {
        $atr = $this->project();
        $web = $this->project(['name' => 'Website', 'key' => 'WEB']);

        $this->assertSame('ATR-1', $this->task($atr)->code);
        $this->assertSame('ATR-2', $this->task($atr)->code);
        $this->assertSame('WEB-1', $this->task($web)->code);
        $this->assertSame('ATR-3', $this->task($atr)->code);
        $this->assertSame(3, $atr->fresh()->task_counter);
        $this->assertSame(1, $web->fresh()->task_counter);
    }

    public function test_a_deleted_task_does_not_give_its_number_away(): void
    {
        $project = $this->project();
        $this->task($project)->delete();

        $this->assertSame(2, $this->task($project)->number);
    }

    public function test_the_author_is_the_administrator_who_made_it(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin');

        $this->assertSame($admin->id, $this->task()->author_id);
    }

    public function test_the_author_and_the_project_of_a_task_are_its_relations(): void
    {
        $admin = $this->admin(name: 'Alice');
        $this->actingAs($admin, 'admin');
        $task = $this->task();

        $this->assertSame('Alice', $task->author?->name);
        $this->assertSame('ATR', $task->project->key);
    }

    public function test_a_task_made_by_a_script_has_no_author(): void
    {
        $this->assertNull($this->task()->author_id);
    }

    public function test_the_author_cannot_be_chosen_by_mass_assignment(): void
    {
        $admin = $this->admin();

        $task = Task::query()->create(['project_id' => $this->project()->id, 'title' => 'A', 'author_id' => $admin->id, 'number' => 99]);

        $this->assertNull($task->author_id);
        $this->assertSame(1, $task->number);
    }

    public function test_a_task_without_status_starts_in_the_first_one(): void
    {
        $task = Task::query()->create(['project_id' => $this->project()->id, 'title' => 'A']);

        $this->assertSame('todo', $task->fresh()->status);

        Status::query()->update(['is_default' => false]);
        Status::query()->where('code', 'in_progress')->update(['is_default' => true]);
        Status::flush();
        $this->assertSame('in_progress', Task::query()->create(['project_id' => $task->project_id, 'title' => 'B'])->fresh()->status);
    }

    public function test_the_closing_time_follows_the_last_status(): void
    {
        Carbon::setTestNow('2026-11-25 10:00:00');
        $task = $this->task();
        $this->assertNull($task->completed_at);

        $task->update(['title' => 'Renamed']);
        $this->assertNull($task->fresh()->completed_at, 'Another change does not close it.');

        $task->update(['status' => 'done']);
        $this->assertSame('2026-11-25 10:00:00', $task->fresh()->completed_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-11-26 10:00:00');
        $task->update(['title' => 'Renamed again']);
        $this->assertSame('2026-11-25 10:00:00', $task->fresh()->completed_at->format('Y-m-d H:i:s'), 'Another change does not move the time.');

        $task->update(['status' => 'review']);
        $this->assertNull($task->fresh()->completed_at, 'Opened again.');

        $this->assertNotNull($this->task(null, ['status' => 'done'])->completed_at, 'Made done.');
        Carbon::setTestNow();
    }

    public function test_a_status_of_the_done_category_closes_whatever_it_is_called(): void
    {
        Status::query()->create(['code' => 'shipped', 'name' => 'Shipped', 'sort' => 900, 'category' => Status::DONE]);
        Status::query()->where('code', 'done')->update(['category' => Status::ACTIVE]);
        Status::flush();
        $task = $this->task(null, ['status' => 'todo']);

        $task->update(['status' => 'done']);
        $this->assertNull($task->fresh()->completed_at, '"done" no longer closes.');

        $task->update(['status' => 'shipped']);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_the_history_remembers_what_was_changed_and_by_whom(): void
    {
        $me = $this->admin(name: 'Alice');
        $bob = $this->admin(name: 'Bob');
        $this->actingAs($me, 'admin');
        $task = $this->task();

        $task->update(['status' => 'review', 'priority' => 'high', 'assignee_id' => $bob->id]);
        $task->update(['assignee_id' => null]);

        $events = Event::query()->where('task_id', $task->id)->orderBy('id')->get();
        $this->assertSame(['created', 'status', 'assignee', 'priority', 'assignee'], $events->pluck('kind')->all());
        $this->assertSame(['todo', 'review'], [$events[1]->old_value, $events[1]->new_value]);
        $this->assertSame([null, 'Bob'], [$events[2]->old_value, $events[2]->new_value]);
        $this->assertSame(['medium', 'high'], [$events[3]->old_value, $events[3]->new_value]);
        $this->assertSame(['Bob', null], [$events[4]->old_value, $events[4]->new_value]);
        $this->assertSame([$me->id], $events->pluck('actor_id')->unique()->values()->all());
    }

    public function test_a_task_made_without_type_and_priority_gets_the_usual_ones(): void
    {
        $task = Task::query()->create(['project_id' => $this->project()->id, 'title' => 'A']);

        $this->assertSame(['task', 'medium'], [$task->type, $task->priority]);
    }

    public function test_a_save_that_changes_nothing_writes_no_history(): void
    {
        $task = $this->task();
        $task->update(['title' => 'Renamed', 'status' => 'todo', 'priority' => 'medium']);

        $this->assertSame(['created'], Event::query()->pluck('kind')->all());
    }

    public function test_other_modules_hear_about_a_new_status_and_a_new_assignee(): void
    {
        $heard = [];
        Hook::on('atrium.tracker.task.status_changed', function (Task $task, string $from, string $to) use (&$heard): void {
            $heard[] = [$task->code, $from, $to];
        });
        Hook::on('atrium.tracker.task.assigned', function (Task $task, int $adminId) use (&$heard): void {
            $heard[] = [$task->code, $adminId];
        });
        $admin = $this->admin();
        $task = $this->task();

        $task->update(['status' => 'done']);
        $task->update(['assignee_id' => $admin->id]);
        $task->update(['assignee_id' => null]);
        $task->update(['title' => 'Quiet']);

        $this->assertSame([['ATR-1', 'todo', 'done'], ['ATR-1', $admin->id]], $heard);
    }

    public function test_deleting_a_task_takes_its_comments_and_history_and_frees_its_subtasks(): void
    {
        $epic = $this->task(null, ['type' => 'epic']);
        $child = $this->task($epic->project, ['parent_id' => $epic->id]);
        $other = $this->task($epic->project);
        Comment::query()->create(['task_id' => $epic->id, 'body' => 'Hi']);
        Comment::query()->create(['task_id' => $other->id, 'body' => 'Stays']);

        $epic->delete();

        $this->assertNull($child->fresh()->parent_id);
        $this->assertSame(['Stays'], Comment::query()->pluck('body')->all());
        $this->assertSame(0, Event::query()->where('task_id', $epic->id)->count());
        $this->assertSame(2, Event::query()->count(), 'The others keep theirs.');
    }

    public function test_deleting_a_project_takes_its_tasks_and_members(): void
    {
        $project = $this->project();
        $keep = $this->task($this->project(['name' => 'Other', 'key' => 'OTH']));
        $task = $this->task($project);
        Comment::query()->create(['task_id' => $task->id, 'body' => 'Hi']);
        $project->members()->attach($this->admin()->id);

        $project->delete();

        $this->assertSame([$keep->id], Task::query()->pluck('id')->all());
        $this->assertSame(0, Comment::query()->count());
        $this->assertSame(0, DB::table('tracker_project_members')->count());
    }

    public function test_a_task_is_overdue_when_it_is_late_and_not_done(): void
    {
        Carbon::setTestNow('2026-11-25 10:00:00');

        $this->assertTrue($this->task(null, ['due_date' => '2026-11-24'])->isOverdue());
        $this->assertFalse($this->task(null, ['due_date' => '2026-11-25'])->isOverdue(), 'Today is not late yet.');
        $this->assertFalse($this->task(null, ['due_date' => '2026-11-26'])->isOverdue());
        $this->assertFalse($this->task(null, ['due_date' => '2026-11-24', 'status' => 'done'])->isOverdue());
        $this->assertFalse($this->task()->isOverdue(), 'No date, no delay.');
        Carbon::setTestNow();
    }

    public function test_the_thread_lists_comments_and_changes_newest_first(): void
    {
        $alice = $this->admin(name: 'Alice');
        $this->actingAs($alice, 'admin');
        Carbon::setTestNow('2026-11-25 09:00:00');
        $task = $this->task();
        Carbon::setTestNow('2026-11-25 10:00:00');
        Comment::query()->create(['task_id' => $task->id, 'author_id' => $alice->id, 'body' => 'First thoughts']);
        Comment::query()->create(['task_id' => $task->id, 'author_id' => null, 'body' => 'From a script']);
        $task->update(['status' => 'review']);
        Carbon::setTestNow();

        $thread = $task->thread();

        $this->assertSame(['comment', 'comment', 'status', 'created'], array_column($thread, 'kind'), 'A comment is above the changes of its second.');
        $this->assertSame(['From a script', 'First thoughts', 'Status: To do → In review', 'The task was created.'], array_column($thread, 'text'));
        $this->assertSame([null, 'Alice', 'Alice', 'Alice'], array_column($thread, 'who'));
    }

    public function test_the_title_of_a_task_is_its_code_and_name(): void
    {
        $task = $this->task();

        $this->assertSame('ATR-1 Write the docs', $task->getAdminTitle());
        $this->assertSame('New', (new Task(['title' => 'New']))->getAdminTitle());
    }

    public function test_a_task_without_a_loaded_project_still_has_a_code(): void
    {
        $this->assertSame('-', (new Task)->code);
    }

    public function test_the_next_number_belongs_to_the_project(): void
    {
        $project = $this->project();

        $this->assertSame(1, Project::nextNumber($project->id));
        $this->assertSame(2, Project::nextNumber($project->id));
    }
}
