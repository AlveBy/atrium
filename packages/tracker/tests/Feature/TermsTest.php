<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Tracker\Models\Event;
use Atrium\Tracker\Models\IssueType;
use Atrium\Tracker\Models\Priority;
use Atrium\Tracker\Models\Resolution;
use Atrium\Tracker\Models\Status;
use Atrium\Tracker\Models\Term;
use Atrium\Tracker\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;

/**
 * Types, priorities, statuses and resolutions are rows an administrator
 * edits, with the built-in ones named in the language of the panel.
 */
final class TermsTest extends TestCase
{
    public function test_the_module_brings_the_built_in_words_in_order(): void
    {
        $this->assertSame(['todo' => 'To do', 'in_progress' => 'In progress', 'review' => 'In review', 'done' => 'Done'], Status::options());
        $this->assertSame(['task' => 'Task', 'bug' => 'Bug', 'story' => 'Story', 'epic' => 'Epic', 'subtask' => 'Sub-task'], IssueType::options());
        $this->assertSame(['lowest' => '⇣ Lowest', 'low' => '↓ Low', 'medium' => '→ Medium', 'high' => '↑ High', 'highest' => '⇡ Highest'], Priority::options());
        $this->assertSame(['done' => 'Done', 'wont_do' => 'Won’t do', 'duplicate' => 'Duplicate', 'cannot_reproduce' => 'Cannot reproduce'], Resolution::options());
    }

    public function test_a_built_in_word_is_named_in_the_language_of_the_panel(): void
    {
        app()->setLocale('ru');

        $this->assertSame('Готово', Status::labelOf('done'));
        $this->assertSame('⇡ Самый высокий', Priority::labelOf('highest'));
    }

    public function test_a_name_of_your_own_is_shown_as_written(): void
    {
        Status::query()->where('code', 'review')->update(['name' => 'Checking']);
        Term::flush();
        app()->setLocale('ru');

        $this->assertSame('Checking', Status::labelOf('review'));
        $this->assertSame('В работе', Status::labelOf('in_progress'));
    }

    public function test_a_code_that_is_gone_is_shown_as_it_was_stored(): void
    {
        $this->assertSame('Gone', Status::labelOf('Gone'));
        $this->assertSame('', Status::labelOf(null));
        $this->assertSame('odd', Priority::labelOf('odd'));
    }

    public function test_a_priority_without_a_symbol_is_only_the_word(): void
    {
        Priority::query()->where('code', 'low')->update(['symbol' => null]);
        Term::flush();

        $this->assertSame('Low', Priority::labelOf('low'));
    }

    public function test_the_default_is_the_marked_one_or_else_the_first(): void
    {
        $this->assertSame(['todo', 'task', 'medium'], [Status::default(), IssueType::default(), Priority::default()]);

        Status::query()->update(['is_default' => false]);
        Status::query()->where('code', 'review')->update(['is_default' => true]);
        Term::flush();
        $this->assertSame('review', Status::default());

        Status::query()->update(['is_default' => false]);
        Term::flush();
        $this->assertSame('todo', Status::default());

        Status::query()->get()->each->delete();
        $this->assertSame('', Status::default());
    }

    public function test_the_order_follows_the_sort_and_then_the_id(): void
    {
        Status::query()->where('code', 'done')->update(['sort' => 1]);
        Status::query()->where('code', 'todo')->update(['sort' => 700]);
        Term::flush();

        $this->assertSame(['done', 'in_progress', 'review', 'todo'], array_keys(Status::options()));
    }

    public function test_the_statuses_that_close_a_task_are_the_ones_of_the_done_category(): void
    {
        $this->assertSame(['done'], Status::doneCodes());
        $this->assertTrue(Status::isDone('done'));
        $this->assertFalse(Status::isDone('review'));
        $this->assertFalse(Status::isDone(null));

        Status::query()->where('code', 'review')->update(['category' => Status::DONE]);
        Term::flush();
        $this->assertSame(['review', 'done'], Status::doneCodes());
    }

    public function test_a_change_is_seen_at_once(): void
    {
        $this->assertArrayNotHasKey('qa', Status::options());

        Status::query()->create(['code' => 'qa', 'sort' => 10, 'category' => Status::ACTIVE]);
        $this->assertArrayHasKey('qa', Status::options());

        Status::query()->where('code', 'qa')->sole()->delete();
        $this->assertArrayNotHasKey('qa', Status::options());
    }

    public function test_a_word_that_tasks_use_cannot_be_deleted(): void
    {
        $this->task(null, ['status' => 'review', 'type' => 'bug', 'priority' => 'high', 'resolution' => 'duplicate']);

        foreach ([Status::class => 'review', IssueType::class => 'bug', Priority::class => 'high', Resolution::class => 'duplicate'] as $class => $code) {
            try {
                $class::query()->where('code', $code)->sole()->delete();
                $this->fail("{$class} {$code} was deleted");
            } catch (ValidationException $e) {
                $this->assertSame(['code' => ['Tasks still use this: change them first.']], $e->errors());
            }

            $this->assertArrayHasKey($code, $class::options());
        }

        $this->assertTrue(Status::query()->where('code', 'done')->sole()->delete());
        $this->assertArrayNotHasKey('done', Status::options());
    }

    public function test_a_line_of_the_history_is_written_in_words(): void
    {
        $event = new Event(['kind' => 'priority', 'old_value' => null, 'new_value' => 'high']);
        $this->assertSame('Priority: — → ↑ High', $event->describe());

        $event = new Event(['kind' => 'priority', 'old_value' => 'low', 'new_value' => null]);
        $this->assertSame('Priority: ↓ Low → —', $event->describe());

        $event = new Event(['kind' => 'assignee', 'old_value' => null, 'new_value' => 'Bob']);
        $this->assertSame('Assignee: — → Bob', $event->describe());

        $event = new Event(['kind' => 'assignee', 'old_value' => 'Bob', 'new_value' => null]);
        $this->assertSame('Assignee: Bob → —', $event->describe());

        $event = new Event(['kind' => 'priority', 'old_value' => 'odd', 'new_value' => 'odder']);
        $this->assertSame('Priority: odd → odder', $event->describe());
    }

    public function test_the_four_lists_are_in_the_admin_with_their_names(): void
    {
        $this->actingAs($this->admin(), 'admin');

        foreach (['tracker_statuses' => 'Statuses', 'tracker_issue_types' => 'Task types', 'tracker_priorities' => 'Priorities', 'tracker_resolutions' => 'Resolutions'] as $path => $title) {
            $this->get("/admin/{$path}")->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
                ->where('title', $title)
                ->where('columns.0.name', 'code'));
        }
    }

    public function test_a_word_is_added_and_its_code_cannot_be_changed_later(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/tracker_statuses', ['code' => 'qa', 'name' => 'QA', 'sort' => 450, 'category' => 'active'])
            ->assertSessionDoesntHaveErrors();
        $status = Status::query()->where('code', 'qa')->sole();
        $this->assertSame(['QA', 450, 'active'], [$status->name, $status->sort, $status->category]);

        $this->put("/admin/tracker_statuses/{$status->id}", ['code' => 'other', 'name' => 'Quality', 'sort' => 450, 'category' => 'active'])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame(['qa', 'Quality'], [$status->fresh()->code, $status->fresh()->name]);
    }

    public function test_a_code_is_small_letters_digits_and_underscores_and_unique(): void
    {
        $this->actingAs($this->admin(), 'admin');

        foreach (['', 'Big', 'with space', 'dash-ed', str_repeat('a', 41), 'todo'] as $code) {
            $this->post('/admin/tracker_statuses', ['code' => $code, 'sort' => 1, 'category' => 'active'])
                ->assertSessionHasErrors(['code']);
        }

        $this->post('/admin/tracker_statuses', ['code' => str_repeat('a', 40), 'sort' => 1, 'category' => 'active'])
            ->assertSessionDoesntHaveErrors();
    }

    public function test_a_status_needs_a_category_of_the_three(): void
    {
        $this->actingAs($this->admin(), 'admin');

        foreach ([null, 'other'] as $category) {
            $this->post('/admin/tracker_statuses', ['code' => 'qa', 'sort' => 1, 'category' => $category])
                ->assertSessionHasErrors(['category']);
        }
    }

    public function test_a_type_can_be_a_top_one_or_need_a_parent(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/tracker_issue_types', ['code' => 'initiative', 'sort' => 1, 'top_level' => true, 'needs_parent' => false])
            ->assertSessionDoesntHaveErrors();
        $type = IssueType::query()->where('code', 'initiative')->sole();
        $this->assertSame([true, false], [$type->top_level, $type->needs_parent]);
        $this->assertTrue(IssueType::terms()['epic']->top_level);
        $this->assertTrue(IssueType::terms()['subtask']->needs_parent);
        $this->assertFalse(IssueType::terms()['task']->top_level);
    }

    public function test_a_priority_keeps_a_short_symbol(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/tracker_priorities', ['code' => 'urgent', 'name' => 'Urgent', 'sort' => 1, 'symbol' => '!!!!'])
            ->assertSessionDoesntHaveErrors();
        $this->post('/admin/tracker_priorities', ['code' => 'urgent2', 'sort' => 1, 'symbol' => '!!!!!'])
            ->assertSessionHasErrors(['symbol']);
        $this->assertSame('!!!! Urgent', Priority::labelOf('urgent'));
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function formOf(string $path): array
    {
        $fields = [];
        $this->actingAs($this->admin(), 'admin')->get("/admin/{$path}/create")->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (&$fields): void {
                $fields = collect($page->toArray()['props']['fields'])->keyBy('name')->all();
            });

        return $fields;
    }

    public function test_every_list_has_the_common_fields_and_its_own(): void
    {
        foreach ([
            'tracker_statuses' => ['category'],
            'tracker_issue_types' => ['top_level', 'needs_parent'],
            'tracker_priorities' => ['symbol'],
            'tracker_resolutions' => [],
        ] as $path => $own) {
            $fields = $this->formOf($path);

            $this->assertSame(['code', 'name', 'sort', 'is_default', ...$own], array_keys($fields), $path);
            $this->assertSame(100, $fields['name']['maxLength'], $path);
            $this->assertSame(500, $fields['sort']['default'], $path);
            $this->assertFalse($fields['is_default']['default'], $path);
        }
    }

    public function test_the_own_fields_of_a_kind(): void
    {
        $this->assertSame([false, false], array_map(fn (string $name) => $this->formOf('tracker_issue_types')[$name]['default'], ['top_level', 'needs_parent']));
        $this->assertSame(4, $this->formOf('tracker_priorities')['symbol']['maxLength']);

        $category = $this->formOf('tracker_statuses')['category'];
        $this->assertSame(['todo', 'active', 'done'], array_column($category['options'], 'value'));
        $this->assertSame(['To do', 'In progress', 'Done'], array_column($category['options'], 'label'));
        $this->assertSame('active', $category['default']);
    }

    public function test_a_name_is_up_to_a_hundred_characters(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/tracker_resolutions', ['code' => 'a', 'name' => str_repeat('n', 101), 'sort' => 1])->assertSessionHasErrors(['name']);
        $this->post('/admin/tracker_resolutions', ['code' => 'b', 'name' => str_repeat('n', 100), 'sort' => 1])->assertSessionDoesntHaveErrors();
    }

    public function test_a_row_holds_its_flags_as_booleans(): void
    {
        $this->assertSame([true, false], [Status::terms()['todo']->is_default, Status::terms()['done']->is_default]);
        $this->assertSame([true, false, true, false], [IssueType::terms()['task']->is_default, IssueType::terms()['epic']->is_default, IssueType::terms()['epic']->top_level, IssueType::terms()['task']->top_level]);
    }

    public function test_the_kinds_are_read_once_until_something_changes(): void
    {
        Status::terms();
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->assertSame(Status::terms(), Status::terms());
        $this->assertSame(0, $queries);
    }

    public function test_the_words_are_public(): void
    {
        $row = new Status;

        $this->assertSame(['todo', 'in_progress', 'review', 'done'], array_keys($row->terms()));
        $this->assertSame(['done'], $row->doneCodes());
        $this->assertTrue($row->isDone('done'));
        $this->assertSame('To do', $row->options()['todo']);
        $this->assertSame('Done', $row->labelOf('done'));
        $this->assertSame('todo', $row->default());
        $this->assertSame('To do', Status::terms()['todo']->label());
    }

    public function test_a_list_is_sorted_by_its_sort_and_imported_by_code(): void
    {
        foreach ([Status::class, IssueType::class, Priority::class, Resolution::class] as $class) {
            $this->assertSame(['sort', 'asc', ['code']], [$class::getDefaultSortColumn(), $class::getDefaultSortDirection(), (new $class)->getImportKeys()], $class);
            $this->assertSame((new $class)->getTable(), $class::getAdminRoutePrefix());
        }
    }

    public function test_a_task_of_a_type_that_is_gone_can_still_have_a_parent(): void
    {
        $parent = $this->task();
        set_error_handler(static function (int $level, string $message): never {
            throw new \ErrorException($message);
        });

        try {
            $data = $this->task($parent->project)->transformAdminData(['type' => 'legacy', 'parent_id' => $parent->id]);
        } finally {
            restore_error_handler();
        }

        $this->assertSame(['type' => 'legacy', 'parent_id' => $parent->id], $data);
    }
}
