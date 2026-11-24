<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Admin\Facades\Hook;
use Atrium\Tracker\Mail\TaskMail;
use Atrium\Tracker\Models\Comment;
use Atrium\Tracker\Models\Event;
use Atrium\Tracker\Models\Project;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Tests\TestCase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What the models offer to the rest of the application: the types of their
 * columns, their relations and the calls the panel makes on them.
 */
final class ModelContractTest extends TestCase
{
    public function test_the_columns_are_cast(): void
    {
        $this->assertSame(['id' => 'int', 'project_id' => 'integer', 'number' => 'integer', 'parent_id' => 'integer', 'assignee_id' => 'integer', 'author_id' => 'integer', 'labels' => 'array', 'attachments' => 'array', 'due_date' => 'date', 'estimate_hours' => 'decimal:2', 'sort' => 'integer', 'completed_at' => 'datetime'], (new Task)->getCasts());
        $this->assertSame(['id' => 'int', 'lead_id' => 'integer', 'is_archived' => 'boolean', 'task_counter' => 'integer'], (new Project)->getCasts());
        $this->assertSame(['id' => 'int', 'task_id' => 'integer', 'author_id' => 'integer'], (new Comment)->getCasts());
        $this->assertSame(['id' => 'int', 'task_id' => 'integer', 'actor_id' => 'integer', 'created_at' => 'datetime'], (new Event)->getCasts());
    }

    public function test_the_relations_are_open_to_other_code(): void
    {
        $task = $this->task();
        $project = $task->project;

        $this->assertInstanceOf(BelongsTo::class, $task->project());
        $this->assertInstanceOf(BelongsTo::class, $task->parent());
        $this->assertInstanceOf(HasMany::class, $task->children());
        $this->assertInstanceOf(BelongsTo::class, $task->assignee());
        $this->assertInstanceOf(BelongsTo::class, $task->author());
        $this->assertInstanceOf(HasMany::class, $task->comments());
        $this->assertInstanceOf(HasMany::class, $task->events());
        $this->assertInstanceOf(BelongsTo::class, $project->lead());
        $this->assertInstanceOf(BelongsToMany::class, $project->members());
        $this->assertInstanceOf(HasMany::class, $project->tasks());
        $this->assertInstanceOf(BelongsTo::class, Comment::query()->make()->task());
        $this->assertInstanceOf(BelongsTo::class, Comment::query()->make()->author());
    }

    public function test_the_panel_asks_the_models_these_questions(): void
    {
        $this->assertSame('tracker_tasks', Task::getAdminRoutePrefix());
        $this->assertSame('tracker_projects', Project::getAdminRoutePrefix());
        $this->assertSame('tracker_comments', Comment::getAdminRoutePrefix());
        $this->assertTrue(Task::usesTabs());
        $this->assertFalse(Comment::isCreatableInAdmin());
        $this->assertFalse(Comment::isEditableInAdmin());
        $this->assertFalse(Comment::isImportableInAdmin());
        $this->assertFalse(Comment::isContentModule());
        $this->assertSame(['id', 'key'], Project::getImportKeys());
        $this->assertSame(['id'], Task::getImportKeys());
        $this->assertSame('name', Project::getDefaultSortColumn());
        $this->assertSame('asc', Project::getDefaultSortDirection());
        $this->assertSame($this->project()->id, Project::query()->sole()->tasks()->getParent()->id);
        $this->assertCount(3, Task::getAdminTabs());
        $this->assertCount(7, Task::getAdministrationListColumns());
        $this->assertCount(7, Task::getAdministrationFilters());
    }

    public function test_the_letter_reads_the_task_it_is_about(): void
    {
        $task = $this->task(null, ['title' => 'Write the docs']);
        $mail = new TaskMail($task, TaskMail::STATUS, 'todo');

        $this->assertSame('ATR-1 is now “To do”', $mail->envelope()->subject);
        $content = $mail->content();
        $this->assertSame('atrium-tracker::mail.task', $content->text);
        $this->assertSame(['The status of ATR-1 “Write the docs” changed: To do → To do.'], $content->with['lines']);
        $this->assertSame(route('admin.tracker_tasks.edit', $task->id), $content->with['url']);

        $assigned = (new TaskMail($task, TaskMail::ASSIGNED))->content();
        $this->assertSame(['The task ATR-1 “Write the docs” was given to you.'], $assigned->with['lines']);
    }

    public function test_a_priority_that_changed_is_written_with_both_names(): void
    {
        $this->assertSame('Priority: ↓ Low → ↑ High', (new Event(['kind' => 'priority', 'old_value' => 'low', 'new_value' => 'high']))->describe());
        $this->assertSame('Status: To do → Done', (new Event(['kind' => 'status', 'old_value' => 'todo', 'new_value' => 'done']))->describe());
    }

    public function test_the_my_tasks_button_joins_the_buttons_of_others(): void
    {
        Hook::filter('atrium.module.links', static fn (array $links, string $model): array => [...$links, ['name' => 'other', 'label' => 'Other', 'href' => '/other']], 5);

        $ofTasks = Hook::apply('atrium.module.links', [], Task::class);
        $ofProjects = Hook::apply('atrium.module.links', [['name' => 'mine', 'label' => 'Mine', 'href' => '/mine'], ['name' => 'second', 'label' => 'Second', 'href' => '/second']], Project::class);

        $this->assertSame(['other', 'my-tasks'], array_column($ofTasks, 'name'));
        $this->assertSame(['mine', 'second', 'other'], array_column($ofProjects, 'name'));
    }
}
