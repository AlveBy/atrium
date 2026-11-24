<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Tracker\Models\Comment;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * What the task refuses when its form is saved, checked on the model itself:
 * the values a form posts are strings, and data from elsewhere can be odd.
 */
final class TaskRulesTest extends TestCase
{
    public function test_the_ids_of_a_form_are_strings_and_still_the_same_project(): void
    {
        $task = $this->task();
        $task->project->update(['is_archived' => true]);

        $data = $task->transformAdminData(['project_id' => (string) $task->project_id, 'title' => 'Same']);

        $this->assertSame(['project_id' => (string) $task->project_id, 'title' => 'Same'], $data);
    }

    public function test_a_project_that_does_not_exist_is_left_to_the_form_rules(): void
    {
        $task = new Task;

        $this->assertSame(['project_id' => 999], $task->transformAdminData(['project_id' => 999]));
    }

    public function test_a_task_without_a_project_yet_takes_the_one_of_the_form(): void
    {
        $archived = $this->project(['is_archived' => true]);

        $this->expectException(ValidationException::class);

        (new Task)->transformAdminData(['project_id' => (string) $archived->id]);
    }

    public function test_the_type_is_the_one_of_the_form_or_else_the_one_the_task_has(): void
    {
        $parent = $this->task();
        $epic = $this->task($parent->project, ['type' => 'epic']);

        // The form turns a task into an epic: it cannot keep its parent.
        $child = $this->task($parent->project, ['parent_id' => $parent->id]);
        try {
            $child->transformAdminData(['type' => 'epic']);
            $this->fail('An epic with a parent was allowed.');
        } catch (ValidationException $e) {
            $this->assertSame(['parent_id'], array_keys($e->errors()));
        }

        // An epic that is saved without its type being posted is still one.
        try {
            $epic->transformAdminData(['parent_id' => $parent->id]);
            $this->fail('An epic with a parent was allowed.');
        } catch (ValidationException $e) {
            $this->assertSame(['parent_id'], array_keys($e->errors()));
        }

        // The form turns an epic into a task: it may have a parent.
        $this->assertSame(['type' => 'task', 'parent_id' => $parent->id], $epic->transformAdminData(['type' => 'task', 'parent_id' => $parent->id]));
    }

    public function test_a_comment_is_trimmed_and_kept_after_the_save(): void
    {
        $task = $this->task();

        $data = $task->transformAdminData(['new_comment' => "  \n Looks good.\n  ", 'title' => 'T']);
        $this->assertSame(['title' => 'T'], $data, 'The comment is not a column.');
        $task->afterAdminSave($data);

        $this->assertSame(['Looks good.'], Comment::query()->pluck('body')->all());
    }

    public function test_a_save_without_a_comment_keeps_the_pending_one_only_once(): void
    {
        $task = $this->task();
        $task->transformAdminData(['new_comment' => 'Once']);
        $task->afterAdminSave([]);
        $task->afterAdminSave([]);
        $task->transformAdminData([]);
        $task->afterAdminSave([]);

        $this->assertSame(1, Comment::query()->count());
    }

    public function test_a_task_made_by_a_script_for_someone_keeps_that_author(): void
    {
        $alice = $this->admin(name: 'Alice');
        $this->actingAs($this->admin(name: 'Bob'), 'admin');

        $task = (new Task)->forceFill(['project_id' => $this->project()->id, 'title' => 'Imported', 'author_id' => $alice->id]);
        $task->save();

        $this->assertSame($alice->id, $task->fresh()->author_id);
    }

    public function test_a_long_chain_of_parents_is_followed_to_its_top(): void
    {
        $top = $this->task();
        $tail = $top;

        for ($i = 0; $i < 60; $i++) {
            $tail = $this->task($top->project, ['parent_id' => $tail->id]);
        }

        $this->expectException(ValidationException::class);
        $top->transformAdminData(['parent_id' => $tail->id]);
    }

    public function test_a_chain_of_parents_that_loops_is_not_followed_forever(): void
    {
        $a = $this->task();
        $b = $this->task($a->project, ['parent_id' => $a->id]);
        DB::table('tracker_tasks')->where('id', $a->id)->update(['parent_id' => $b->id]);
        $outsider = $this->task($a->project);

        $this->assertSame(['parent_id' => $a->id], $outsider->transformAdminData(['parent_id' => $a->id]));

        $this->expectException(ValidationException::class);
        $b->transformAdminData(['parent_id' => $a->id]);
    }

    public function test_a_comment_without_a_date_goes_below_the_dated_ones(): void
    {
        $task = $this->task();
        DB::table('tracker_comments')->insert(['task_id' => $task->id, 'body' => 'Undated']);
        DB::table('tracker_comments')->insert(['task_id' => $task->id, 'body' => 'Also undated']);
        Comment::query()->create(['task_id' => $task->id, 'body' => 'Dated']);

        $thread = $task->thread();

        $this->assertSame(['Dated', 'The task was created.', 'Also undated', 'Undated'], array_column($thread, 'text'));
        $this->assertNull($thread[2]['at']);
    }

    public function test_the_thread_field_draws_only_a_task(): void
    {
        $field = collect(Task::getAdministrationFields())->first(fn ($field): bool => $field->getName() === 'thread');

        $this->assertSame('', $field->render(new Comment));
        $this->assertStringContainsString('data-tracker-thread', $field->render($this->task()));
    }
}
