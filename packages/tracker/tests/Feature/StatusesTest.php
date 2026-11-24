<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Tracker\Enums\Priority;
use Atrium\Tracker\Enums\TaskType;
use Atrium\Tracker\Models\Event;
use Atrium\Tracker\Support\Statuses;
use Atrium\Tracker\Tests\TestCase;

/**
 * The statuses are four of the module until the settings list others; the
 * last one closes the task.
 */
final class StatusesTest extends TestCase
{
    public function test_the_module_brings_four_statuses_in_words(): void
    {
        $this->assertSame(['todo' => 'To do', 'in_progress' => 'In progress', 'review' => 'In review', 'done' => 'Done'], Statuses::options());
        $this->assertSame('todo', Statuses::first());
        $this->assertSame('done', Statuses::done());
    }

    public function test_the_statuses_of_the_settings_replace_them_trimmed_and_without_repeats(): void
    {
        $this->settings(['statuses' => ['  Backlog ', 'Doing', '', 'Doing', 'Shipped']]);

        $this->assertSame(['Backlog' => 'Backlog', 'Doing' => 'Doing', 'Shipped' => 'Shipped'], Statuses::options());
        $this->assertSame('Backlog', Statuses::first());
        $this->assertSame('Shipped', Statuses::done());
    }

    public function test_an_empty_or_odd_list_leaves_the_four(): void
    {
        foreach ([[], [''], ['  '], [1, null, ['x']], null] as $value) {
            $this->settings(['statuses' => $value]);

            $this->assertSame(['todo', 'in_progress', 'review', 'done'], array_keys(Statuses::options()), json_encode($value) ?: '');
        }
    }

    public function test_a_status_of_the_module_is_named_in_the_language_of_the_panel(): void
    {
        app()->setLocale('ru');

        $this->assertSame('Готово', Statuses::label('done'));
        $this->assertSame('Custom', Statuses::label('Custom'));
    }

    public function test_the_types_and_priorities_are_named_in_words(): void
    {
        $this->assertSame(['task' => 'Task', 'bug' => 'Bug', 'story' => 'Story', 'epic' => 'Epic'], TaskType::options());
        $this->assertSame(['lowest' => '⇣ Lowest', 'low' => '↓ Low', 'medium' => '→ Medium', 'high' => '↑ High', 'highest' => '⇡ Highest'], Priority::options());
        $this->assertSame('⇡', Priority::Highest->symbol());
    }

    public function test_a_line_of_the_history_is_written_in_words(): void
    {
        $event = new Event(['kind' => 'priority', 'old_value' => null, 'new_value' => 'high']);
        $this->assertSame('Priority: — → ↑ High', $event->describe());

        $event = new Event(['kind' => 'assignee', 'old_value' => null, 'new_value' => 'Bob']);
        $this->assertSame('Assignee: — → Bob', $event->describe());

        $event = new Event(['kind' => 'assignee', 'old_value' => 'Bob', 'new_value' => null]);
        $this->assertSame('Assignee: Bob → —', $event->describe());

        $event = new Event(['kind' => 'priority', 'old_value' => 'odd', 'new_value' => 'odder']);
        $this->assertSame('Priority: — → —', $event->describe());
    }
}
