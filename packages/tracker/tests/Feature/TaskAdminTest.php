<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\Tracker\Models\Comment;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Tests\TestCase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/**
 * The administrators work with tasks in the admin: the form, the list, the
 * filters and the thread of a task.
 */
final class TaskAdminTest extends TestCase
{
    /**
     * @param  array<string,mixed>  $extra
     * @return array<string,mixed>
     */
    private function form(int $projectId, array $extra = []): array
    {
        return [...['project_id' => $projectId, 'type' => 'task', 'title' => 'Fix the cart', 'status' => 'todo', 'priority' => 'medium'], ...$extra];
    }

    /**
     * @return list<string>
     */
    private function titles(string $query = ''): array
    {
        $titles = [];
        $this->get('/admin/tracker_tasks'.$query)->assertOk()->assertInertia(function (AssertableInertia $page) use (&$titles): void {
            $titles = array_column($page->toArray()['props']['items']['data'], 'title');
        });
        sort($titles);

        return $titles;
    }

    public function test_a_task_is_made_in_the_form_and_numbered(): void
    {
        $admin = $this->admin();
        $project = $this->project();
        $this->actingAs($admin, 'admin');

        $this->get('/admin/tracker_tasks/create')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Resource/Create'));
        $this->post('/admin/tracker_tasks', $this->form($project->id, ['labels' => ['web', 'urgent'], 'due_date' => '2026-12-01', 'estimate_hours' => '2.5']))
            ->assertSessionDoesntHaveErrors()->assertRedirect(route('admin.tracker_tasks.index'));

        $task = Task::query()->sole();
        $this->assertSame('ATR-1', $task->code);
        $this->assertSame($admin->id, $task->author_id);
        $this->assertSame(['web', 'urgent'], $task->labels);
        $this->assertSame('2.50', $task->estimate_hours);
    }

    public function test_the_form_of_a_new_task_starts_with_the_usual_values(): void
    {
        $this->actingAs($this->admin(), 'admin')->get('/admin/tracker_tasks/create')
            ->assertInertia(function (AssertableInertia $page): void {
                $fields = collect($page->toArray()['props']['fields'])->keyBy('name');

                $this->assertSame('task', $fields['type']['default']);
                $this->assertSame('todo', $fields['status']['default']);
                $this->assertSame('medium', $fields['priority']['default']);
                $this->assertFalse($fields->has('new_comment'), 'A comment is for a task that exists.');
            });
    }

    public function test_the_statuses_of_the_form_are_the_ones_of_the_settings(): void
    {
        $this->settings(['statuses' => ['Backlog', 'Doing']]);

        $this->actingAs($this->admin(), 'admin')->get('/admin/tracker_tasks/create')
            ->assertInertia(function (AssertableInertia $page): void {
                $status = collect($page->toArray()['props']['fields'])->firstWhere('name', 'status');

                $this->assertSame('Backlog', $status['default']);
                $this->assertSame(['Backlog', 'Doing'], array_column($status['options'], 'value'));
            });
    }

    public function test_a_task_needs_a_project_and_a_title(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/tracker_tasks', ['type' => 'task', 'status' => 'todo', 'priority' => 'medium'])
            ->assertSessionHasErrors(['project_id', 'title']);

        $this->post('/admin/tracker_tasks', $this->form(999))->assertSessionHasErrors(['project_id']);
        $this->assertSame(0, Task::query()->count());
    }

    public function test_a_task_cannot_be_made_in_an_archived_project(): void
    {
        $archived = $this->project(['is_archived' => true]);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/tracker_tasks', $this->form($archived->id))
            ->assertSessionHasErrors(['project_id' => 'The project is archived: no tasks can be moved into it.']);
        $this->assertSame(0, Task::query()->count());
    }

    public function test_a_task_of_a_project_archived_later_can_still_be_edited_and_closed(): void
    {
        $task = $this->task();
        $task->project->update(['is_archived' => true]);

        $this->actingAs($this->admin(), 'admin')
            ->put("/admin/tracker_tasks/{$task->id}", $this->form($task->project_id, ['status' => 'done']))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('done', $task->fresh()->status);
    }

    public function test_a_task_cannot_be_moved_into_an_archived_project(): void
    {
        $task = $this->task();
        $archived = $this->project(['name' => 'Old', 'key' => 'OLD', 'is_archived' => true]);

        $this->actingAs($this->admin(), 'admin')
            ->put("/admin/tracker_tasks/{$task->id}", $this->form($archived->id))
            ->assertSessionHasErrors(['project_id']);
        $this->assertSame($task->project_id, $task->fresh()->project_id);
    }

    public function test_an_open_project_gives_tasks_to_anyone(): void
    {
        $project = $this->project();
        $someone = $this->admin(name: 'Someone');

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/tracker_tasks', $this->form($project->id, ['assignee_id' => $someone->id]))
            ->assertSessionDoesntHaveErrors();
        $this->assertSame($someone->id, Task::query()->sole()->assignee_id);
    }

    public function test_a_project_with_people_gives_tasks_only_to_them(): void
    {
        $lead = $this->admin(name: 'Lead');
        $member = $this->admin(name: 'Member');
        $stranger = $this->admin(name: 'Stranger');
        $project = $this->project(['lead_id' => $lead->id]);
        $project->members()->attach($member->id);
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/tracker_tasks', $this->form($project->id, ['assignee_id' => $lead->id]))->assertSessionDoesntHaveErrors();
        $this->post('/admin/tracker_tasks', $this->form($project->id, ['assignee_id' => $member->id]))->assertSessionDoesntHaveErrors();
        $this->post('/admin/tracker_tasks', $this->form($project->id, ['assignee_id' => $stranger->id]))
            ->assertSessionHasErrors(['assignee_id' => 'This administrator does not work in the project.']);

        $this->assertSame(2, Task::query()->count());
    }

    public function test_a_project_with_only_members_is_not_open(): void
    {
        $member = $this->admin(name: 'Member');
        $project = $this->project();
        $project->members()->attach($member->id);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/tracker_tasks', $this->form($project->id, ['assignee_id' => $this->admin(name: 'Stranger')->id]))
            ->assertSessionHasErrors(['assignee_id']);
    }

    public function test_a_task_can_be_left_without_an_assignee_in_any_project(): void
    {
        $project = $this->project(['lead_id' => $this->admin()->id]);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/tracker_tasks', $this->form($project->id, ['assignee_id' => null]))
            ->assertSessionDoesntHaveErrors();
    }

    public function test_a_subtask_belongs_to_a_task_of_the_same_project(): void
    {
        $parent = $this->task();
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/tracker_tasks', $this->form($parent->project_id, ['parent_id' => $parent->id, 'title' => 'Sub']))->assertSessionDoesntHaveErrors();

        $this->assertSame($parent->id, Task::query()->where('title', 'Sub')->sole()->parent_id);
    }

    public function test_a_subtask_cannot_hang_below_a_task_of_another_project(): void
    {
        $other = $this->task($this->project(['name' => 'Other', 'key' => 'OTH']));
        $project = $this->project();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/tracker_tasks', $this->form($project->id, ['parent_id' => $other->id]))
            ->assertSessionHasErrors(['parent_id' => 'The parent task must be in the same project.']);
    }

    public function test_an_epic_has_no_parent(): void
    {
        $parent = $this->task();

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/tracker_tasks', $this->form($parent->project_id, ['type' => 'epic', 'parent_id' => $parent->id]))
            ->assertSessionHasErrors(['parent_id' => 'An epic has no parent task.']);
    }

    public function test_a_task_cannot_become_its_own_ancestor(): void
    {
        $top = $this->task();
        $middle = $this->task($top->project, ['parent_id' => $top->id]);
        $bottom = $this->task($top->project, ['parent_id' => $middle->id]);
        $this->actingAs($this->admin(), 'admin');

        $this->put("/admin/tracker_tasks/{$top->id}", $this->form($top->project_id, ['parent_id' => $top->id]))
            ->assertSessionHasErrors(['parent_id' => 'A task cannot be a child of itself or of one of its subtasks.']);
        $this->put("/admin/tracker_tasks/{$top->id}", $this->form($top->project_id, ['parent_id' => $bottom->id]))
            ->assertSessionHasErrors(['parent_id']);
        $this->put("/admin/tracker_tasks/{$bottom->id}", $this->form($top->project_id, ['parent_id' => $top->id]))
            ->assertSessionDoesntHaveErrors();

        $this->assertNull($top->fresh()->parent_id);
        $this->assertSame($top->id, $bottom->fresh()->parent_id);
    }

    public function test_a_task_keeps_its_parent_when_the_form_does_not_mention_it(): void
    {
        $parent = $this->task();
        $child = $this->task($parent->project, ['parent_id' => $parent->id]);

        $this->actingAs($this->admin(), 'admin')
            ->put("/admin/tracker_tasks/{$child->id}", $this->form($parent->project_id, ['title' => 'Renamed']))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame($parent->id, $child->fresh()->parent_id);
    }

    public function test_a_comment_typed_in_the_form_is_kept_with_its_author(): void
    {
        $admin = $this->admin();
        $task = $this->task();
        $this->actingAs($admin, 'admin');

        $this->put("/admin/tracker_tasks/{$task->id}", $this->form($task->project_id, ['new_comment' => '  Looks good.  ']))->assertSessionDoesntHaveErrors();
        $this->put("/admin/tracker_tasks/{$task->id}", $this->form($task->project_id, ['new_comment' => '   ']))->assertSessionDoesntHaveErrors();
        $this->put("/admin/tracker_tasks/{$task->id}", $this->form($task->project_id))->assertSessionDoesntHaveErrors();

        $comment = Comment::query()->sole();
        $this->assertSame(['Looks good.', $admin->id, $task->id], [$comment->body, $comment->author_id, $comment->task_id]);
    }

    public function test_a_comment_is_not_kept_when_the_task_is_refused(): void
    {
        $task = $this->task();

        $this->actingAs($this->admin(), 'admin')
            ->put("/admin/tracker_tasks/{$task->id}", $this->form($task->project_id, ['title' => '', 'new_comment' => 'Lost']))
            ->assertSessionHasErrors(['title']);

        $this->assertSame(0, Comment::query()->count());
    }

    public function test_the_same_comment_is_not_added_twice_by_the_next_save(): void
    {
        $task = $this->task();
        $this->actingAs($this->admin(), 'admin');

        $this->put("/admin/tracker_tasks/{$task->id}", $this->form($task->project_id, ['new_comment' => 'Once']));
        $this->put("/admin/tracker_tasks/{$task->id}", $this->form($task->project_id, ['title' => 'Again']));

        $this->assertSame(1, Comment::query()->count());
    }

    public function test_the_edit_form_shows_the_thread_of_the_task(): void
    {
        $task = $this->task();
        Comment::query()->create(['task_id' => $task->id, 'body' => 'Please <b>check</b> it']);
        $task->update(['status' => 'done']);

        $this->actingAs($this->admin(), 'admin')->get("/admin/tracker_tasks/{$task->id}/edit")
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $html = $page->toArray()['props']['item']['thread'];

                $this->assertStringContainsString('data-tracker-thread', $html);
                $this->assertStringContainsString('Status: To do → Done', $html);
                $this->assertStringContainsString('Please &lt;b&gt;check&lt;/b&gt; it', $html, 'A comment is text, not markup.');
            });
    }

    public function test_the_thread_of_a_task_without_history_says_so(): void
    {
        $html = view('atrium-tracker::admin.thread', ['task' => new Task, 'entries' => []])->render();

        $this->assertStringContainsString('Nothing here yet.', $html);
        $this->assertStringNotContainsString('data-tracker-entry', $html);
    }

    public function test_the_list_shows_the_code_the_status_and_the_priority_in_words(): void
    {
        $admin = $this->admin(name: 'Alice');
        $this->task(null, ['title' => 'Late one', 'priority' => 'highest', 'assignee_id' => $admin->id, 'status' => 'in_progress']);

        $this->actingAs($admin, 'admin')->get('/admin/tracker_tasks')
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page): void {
                $props = $page->toArray()['props'];
                $row = $props['items']['data'][0];

                $this->assertSame(['code', 'title', 'type', 'status', 'priority', 'assignee.name', 'due_date'], array_column($props['columns'], 'name'));
                $this->assertSame('ATR-1', $row['code']);
                $this->assertSame('In progress', $row['status']);
                $this->assertSame('⇡ Highest', $row['priority']);
                $this->assertSame('Task', $row['type']);
            });
    }

    public function test_the_list_shows_a_status_or_a_type_it_no_longer_knows_as_it_was_stored(): void
    {
        $task = $this->task(null, ['status' => 'Gone']);
        $task->forceFill(['type' => 'legacy', 'priority' => 'legacy'])->save();

        $this->actingAs($this->admin(), 'admin')->get('/admin/tracker_tasks')
            ->assertInertia(function (AssertableInertia $page): void {
                $row = $page->toArray()['props']['items']['data'][0];

                $this->assertSame(['Gone', 'legacy', 'legacy'], [$row['status'], $row['type'], $row['priority']]);
            });
    }

    public function test_the_list_is_narrowed_by_the_filters(): void
    {
        Carbon::setTestNow('2026-11-25 10:00:00');
        $alice = $this->admin(name: 'Alice');
        $atr = $this->project();
        $web = $this->project(['name' => 'Website', 'key' => 'WEB']);
        $this->task($atr, ['title' => 'Late', 'due_date' => '2026-11-01', 'assignee_id' => $alice->id, 'labels' => ['web']]);
        $this->task(null, ['title' => 'Closed late', 'due_date' => '2026-11-01', 'status' => 'done', 'type' => 'bug']);
        $this->task(null, ['title' => 'Future', 'due_date' => '2026-12-01', 'labels' => ['api', 'web']]);
        $this->task(null, ['title' => 'Undated']);
        $this->task($web, ['title' => 'Elsewhere', 'status' => 'review']);
        $this->actingAs($alice, 'admin');

        $this->assertSame(['Closed late', 'Elsewhere', 'Future', 'Late', 'Undated'], $this->titles());
        $this->assertSame(['Elsewhere'], $this->titles("?project_id={$web->id}"));
        $this->assertSame(['Elsewhere'], $this->titles('?status=review'));
        $this->assertSame(['Late'], $this->titles("?assignee_id={$alice->id}"));
        $this->assertSame(['Closed late'], $this->titles('?type=bug'));
        $this->assertSame(['Future', 'Late'], $this->titles('?label=web'));
        $this->assertCount(5, $this->titles('?label=nothing'), 'A label nobody uses is no filter.');
        $this->assertSame(['Late'], $this->titles('?overdue=1'), 'Late and not done.');
        $this->assertSame(['Closed late', 'Elsewhere', 'Future', 'Undated'], $this->titles('?overdue=0'));
        $this->assertSame(['Late'], $this->titles('?mine=1'));
        $this->assertSame(['Closed late', 'Elsewhere', 'Future', 'Undated'], $this->titles('?mine=0'));
        Carbon::setTestNow();
    }

    public function test_the_filters_offer_what_exists(): void
    {
        $alice = $this->admin(name: 'Alice');
        $this->admin(active: false, name: 'Gone');
        $this->task(null, ['labels' => ['web', 'api']]);
        $this->task(null, ['labels' => ['api']]);

        $this->actingAs($alice, 'admin')->get('/admin/tracker_tasks')
            ->assertInertia(function (AssertableInertia $page): void {
                $controls = collect($page->toArray()['props']['filterControls'])->keyBy('name');

                $this->assertSame(['project_id', 'status', 'assignee_id', 'type', 'label', 'overdue', 'mine'], $controls->keys()->all());
                $this->assertSame(['api', 'web'], array_column($controls['label']['options'], 'value'));
                $this->assertSame(['Alice'], array_column($controls['assignee_id']['options'], 'label'));
                $this->assertSame(['Atrium'], array_column($controls['project_id']['options'], 'label'));
            });
    }

    public function test_my_tasks_is_a_button_of_the_task_list(): void
    {
        $this->actingAs($this->admin(), 'admin')->get('/admin/tracker_tasks')
            ->assertInertia(function (AssertableInertia $page): void {
                $links = $page->toArray()['props']['links'];

                $this->assertSame(['my-tasks'], array_column($links, 'name'));
                $this->assertSame('My tasks', $links[0]['label']);
                $this->assertStringEndsWith('/admin/tracker_tasks?mine=1', $links[0]['href']);
            });

        $this->get('/admin/tracker_projects')->assertInertia(fn (AssertableInertia $page) => $page->where('links', []));
    }

    public function test_my_tasks_is_not_offered_when_the_module_is_not_installed(): void
    {
        $this->actingAs($this->admin(), 'admin');
        config(['atrium.modules' => []]);

        $this->assertSame([], Hook::apply('atrium.module.links', [], Task::class));
    }

    public function test_the_computed_columns_have_nothing_to_say_about_another_record(): void
    {
        $foreign = new Comment;
        $computed = array_filter(Task::getAdministrationListColumns(), static fn ($column): bool => $column->isComputed());

        $this->assertCount(4, $computed);

        foreach ($computed as $column) {
            $this->assertSame('', $column->valueOf($foreign), $column->getName());
        }
    }
}
