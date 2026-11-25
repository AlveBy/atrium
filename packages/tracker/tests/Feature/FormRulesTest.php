<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Admin\Fields\Field;
use Atrium\Tracker\Models\Comment;
use Atrium\Tracker\Models\Project;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;

/**
 * What the fields of the forms accept, checked rule by rule, and which of
 * the model methods the panel is allowed to call.
 */
final class FormRulesTest extends TestCase
{
    /**
     * @param  list<Field>  $fields
     * @param  array<string,mixed>  $input
     * @return list<string> the names of the fields that were refused
     */
    private function refused(array $fields, array $input): array
    {
        $rules = [];
        foreach ($fields as $field) {
            $rules[$field->getName()] = $field->getValidationRules();
        }

        return array_keys(Validator::make($input, array_intersect_key($rules, $input))->errors()->toArray());
    }

    /**
     * @return list<Field>
     */
    private function named(array $fields, string $name): array
    {
        return array_values(array_filter($fields, static fn (Field $field): bool => $field->getName() === $name));
    }

    public function test_a_task_refuses_values_of_the_wrong_kind(): void
    {
        $fields = Task::getAdministrationFields();
        $project = $this->project();

        foreach ([
            'title' => [['x'], str_repeat('a', 256), ''],
            'project_id' => ['1.0x', ['1'], '', 999],
            'assignee_id' => ['1.0', ['1'], 999],
            'parent_id' => ['1.0', ['1'], 999],
            'labels' => ['text', range(1, 21)],
            'new_comment' => [['x'], str_repeat('a', 20001)],
        ] as $name => $values) {
            foreach ($values as $value) {
                $this->assertSame([$name], $this->refused($fields, [$name => $value]), $name.' = '.json_encode($value));
            }
        }

        foreach ([
            'title' => ['Fine', str_repeat('a', 255)],
            'project_id' => [$project->id, (string) $project->id],
            'assignee_id' => [null],
            'parent_id' => [null],
            'labels' => [null, [], ['a'], range(1, 20)],
            'new_comment' => [null, 'Fine', str_repeat('a', 20000)],
        ] as $name => $values) {
            foreach ($values as $value) {
                $this->assertSame([], $this->refused($fields, [$name => $value]), $name.' = '.json_encode($value));
            }
        }
    }

    public function test_a_project_refuses_values_of_the_wrong_kind(): void
    {
        $fields = Project::getAdministrationFields();

        foreach ([
            'name' => [['x'], str_repeat('a', 151), ''],
            'key' => [['AB'], 'a', 'A', 'ABCDEFGHIJK', 'AB-C', '1AB', 12],
            'description' => [['x'], str_repeat('a', 5001)],
            'lead_id' => ['1.0', ['1'], 999],
        ] as $name => $values) {
            foreach ($values as $value) {
                $this->assertSame([$name], $this->refused($fields, [$name => $value]), $name.' = '.json_encode($value));
            }
        }

        foreach ([
            'name' => ['Shop', str_repeat('a', 150)],
            'key' => ['AB', 'A1', 'ABCDEFGHIJ'],
            'description' => [null, 'Fine', str_repeat('a', 5000)],
            'lead_id' => [null],
        ] as $name => $values) {
            foreach ($values as $value) {
                $this->assertSame([], $this->refused($fields, [$name => $value]), $name.' = '.json_encode($value));
            }
        }
    }

    public function test_the_ids_of_the_forms_must_be_whole_numbers_of_something_that_exists(): void
    {
        $rules = static fn (array $fields, string $name): array => $fields[array_search($name, array_map(static fn (Field $field): string => $field->getName(), $fields), true)]->getValidationRules();
        $task = Task::getAdministrationFields();
        $project = Project::getAdministrationFields();

        $this->assertSame(['required', 'integer', 'exists:tracker_projects,id'], $rules($task, 'project_id'));
        $this->assertSame(['nullable', 'integer', 'exists:admin_users,id'], $rules($task, 'assignee_id'));
        $this->assertSame(['nullable', 'integer', 'exists:tracker_tasks,id'], $rules($task, 'parent_id'));
        $this->assertSame(['nullable', 'integer', 'exists:admin_users,id'], $rules($project, 'lead_id'));
    }

    public function test_the_panel_may_call_these_methods(): void
    {
        $comment = new Comment;
        $this->assertFalse($comment->isCreatableInAdmin());
        $this->assertFalse($comment->isEditableInAdmin());
        $this->assertFalse($comment->isImportableInAdmin());
        $this->assertFalse($comment->isContentModule());
        $this->assertSame(['id', 'key'], (new Project)->getImportKeys());
        $this->assertSame(['id'], (new Task)->getImportKeys());
        $this->assertCount(3, (new Task)->getAdminTabs());
        $this->assertSame(1, (new Project)->nextNumber($this->project()->id));

        foreach ([
            [Comment::class, 'isCreatableInAdmin'], [Comment::class, 'isEditableInAdmin'],
            [Comment::class, 'isImportableInAdmin'], [Comment::class, 'isContentModule'],
            [Project::class, 'nextNumber'], [Project::class, 'getImportKeys'],
            [Task::class, 'getImportKeys'], [Task::class, 'getAdminTabs'], [Task::class, 'getCodeAttribute'],
        ] as [$class, $method]) {
            $this->assertTrue((new ReflectionMethod($class, $method))->isPublic(), $class.'::'.$method);
        }

        $task = $this->task();
        $this->assertSame('ATR-'.$task->number, $task->getCodeAttribute());
    }
}
