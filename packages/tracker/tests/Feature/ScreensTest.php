<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests\Feature;

use Atrium\Tracker\Models\Comment;
use Atrium\Tracker\Tests\TestCase;
use Inertia\Testing\AssertableInertia;

/**
 * What each screen of the module offers: the fields with their types, limits
 * and defaults, the columns of the lists and the filters above them. The
 * texts are not pinned here, only that they were translated.
 */
final class ScreensTest extends TestCase
{
    private const KEYS = ['name', 'type', 'required', 'readonly', 'default', 'tab', 'options', 'maxLength', 'rows', 'itemType', 'maxItems', 'maxFiles', 'addLabel', 'min', 'max', 'step', 'decimals', 'suffix', 'directory', 'maxSize', 'acceptedTypes', 'displayColumn', 'modelClass', 'multiple'];

    private const COLUMN_KEYS = ['name', 'sortable', 'searchable', 'width', 'limit'];

    private const TASK_FIELDS = [
        'assignee_id' => [
            'default' => null,
            'displayColumn' => 'name',
            'modelClass' => 'Atrium\\Admin\\Models\\AdminUser',
            'name' => 'assignee_id',
            'readonly' => false,
            'required' => false,
            'tab' => 'params',
            'type' => 'belongsTo',
        ],
        'attachments' => [
            'acceptedTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'text/plain', 'text/csv', 'application/zip', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'default' => null,
            'directory' => 'tracker',
            'maxFiles' => 20,
            'maxSize' => 20480,
            'multiple' => true,
            'name' => 'attachments',
            'readonly' => false,
            'required' => false,
            'tab' => 'files',
            'type' => 'gallery',
        ],
        'description' => [
            'default' => null,
            'name' => 'description',
            'readonly' => false,
            'required' => false,
            'rows' => 6,
            'tab' => 'params',
            'type' => 'content',
        ],
        'due_date' => [
            'default' => null,
            'name' => 'due_date',
            'readonly' => false,
            'required' => false,
            'tab' => 'params',
            'type' => 'date',
        ],
        'estimate_hours' => [
            'decimals' => 2,
            'default' => null,
            'max' => 99999,
            'min' => 0,
            'name' => 'estimate_hours',
            'readonly' => false,
            'required' => false,
            'step' => 0.01,
            'suffix' => 'h',
            'tab' => 'params',
            'type' => 'number',
        ],
        'labels' => [
            'addLabel' => 'Add a label',
            'default' => null,
            'itemType' => 'text',
            'maxItems' => 20,
            'name' => 'labels',
            'readonly' => false,
            'required' => false,
            'tab' => 'params',
            'type' => 'repeatable',
        ],
        'new_comment' => [
            'default' => null,
            'maxLength' => 20000,
            'name' => 'new_comment',
            'readonly' => false,
            'required' => false,
            'rows' => 4,
            'tab' => 'activity',
            'type' => 'textarea',
        ],
        'parent_id' => [
            'default' => null,
            'displayColumn' => 'title',
            'modelClass' => 'Atrium\\Tracker\\Models\\Task',
            'name' => 'parent_id',
            'readonly' => false,
            'required' => false,
            'tab' => 'params',
            'type' => 'belongsTo',
        ],
        'priority' => [
            'default' => 'medium',
            'name' => 'priority',
            'options' => [
                [
                    'label' => '⇣ Lowest',
                    'value' => 'lowest',
                ],
                [
                    'label' => '↓ Low',
                    'value' => 'low',
                ],
                [
                    'label' => '→ Medium',
                    'value' => 'medium',
                ],
                [
                    'label' => '↑ High',
                    'value' => 'high',
                ],
                [
                    'label' => '⇡ Highest',
                    'value' => 'highest',
                ],
            ],
            'readonly' => false,
            'required' => true,
            'tab' => 'params',
            'type' => 'select',
        ],
        'project_id' => [
            'default' => null,
            'displayColumn' => 'name',
            'modelClass' => 'Atrium\\Tracker\\Models\\Project',
            'name' => 'project_id',
            'readonly' => false,
            'required' => true,
            'tab' => 'params',
            'type' => 'belongsTo',
        ],
        'resolution' => [
            'default' => null,
            'name' => 'resolution',
            'options' => [
                [
                    'label' => 'Done',
                    'value' => 'done',
                ],
                [
                    'label' => 'Won’t do',
                    'value' => 'wont_do',
                ],
                [
                    'label' => 'Duplicate',
                    'value' => 'duplicate',
                ],
                [
                    'label' => 'Cannot reproduce',
                    'value' => 'cannot_reproduce',
                ],
            ],
            'readonly' => false,
            'required' => false,
            'tab' => 'params',
            'type' => 'select',
        ],
        'status' => [
            'default' => 'todo',
            'name' => 'status',
            'options' => [
                [
                    'label' => 'To do',
                    'value' => 'todo',
                ],
                [
                    'label' => 'In progress',
                    'value' => 'in_progress',
                ],
                [
                    'label' => 'In review',
                    'value' => 'review',
                ],
                [
                    'label' => 'Done',
                    'value' => 'done',
                ],
            ],
            'readonly' => false,
            'required' => true,
            'tab' => 'params',
            'type' => 'select',
        ],
        'thread' => [
            'default' => null,
            'name' => 'thread',
            'readonly' => false,
            'required' => false,
            'tab' => 'activity',
            'type' => 'display',
        ],
        'title' => [
            'default' => null,
            'maxLength' => 255,
            'name' => 'title',
            'readonly' => false,
            'required' => true,
            'suffix' => null,
            'tab' => 'params',
            'type' => 'text',
        ],
        'type' => [
            'default' => 'task',
            'name' => 'type',
            'options' => [
                [
                    'label' => 'Task',
                    'value' => 'task',
                ],
                [
                    'label' => 'Bug',
                    'value' => 'bug',
                ],
                [
                    'label' => 'Story',
                    'value' => 'story',
                ],
                [
                    'label' => 'Epic',
                    'value' => 'epic',
                ],
                [
                    'label' => 'Sub-task',
                    'value' => 'subtask',
                ],
            ],
            'readonly' => false,
            'required' => true,
            'tab' => 'params',
            'type' => 'select',
        ],
    ];

    private const PROJECT_FIELDS = [
        'description' => [
            'default' => null,
            'maxLength' => 5000,
            'name' => 'description',
            'readonly' => false,
            'required' => false,
            'rows' => 5,
            'tab' => 'params',
            'type' => 'textarea',
        ],
        'is_archived' => [
            'default' => false,
            'name' => 'is_archived',
            'readonly' => false,
            'required' => false,
            'tab' => 'params',
            'type' => 'checkbox',
        ],
        'key' => [
            'default' => null,
            'maxLength' => 255,
            'name' => 'key',
            'readonly' => false,
            'required' => true,
            'suffix' => null,
            'tab' => 'params',
            'type' => 'text',
        ],
        'lead_id' => [
            'default' => null,
            'displayColumn' => 'name',
            'modelClass' => 'Atrium\\Admin\\Models\\AdminUser',
            'name' => 'lead_id',
            'readonly' => false,
            'required' => false,
            'tab' => 'params',
            'type' => 'belongsTo',
        ],
        'members' => [
            'default' => null,
            'displayColumn' => 'name',
            'maxItems' => 0,
            'modelClass' => 'Atrium\\Admin\\Models\\AdminUser',
            'multiple' => true,
            'name' => 'members',
            'readonly' => false,
            'required' => false,
            'tab' => 'params',
            'type' => 'belongsToMany',
        ],
        'name' => [
            'default' => null,
            'maxLength' => 150,
            'name' => 'name',
            'readonly' => false,
            'required' => true,
            'suffix' => null,
            'tab' => 'params',
            'type' => 'text',
        ],
    ];

    private const SETTINGS_FIELDS = [
        'notify_assigned' => [
            'default' => true,
            'name' => 'notify_assigned',
            'readonly' => false,
            'required' => false,
            'tab' => 'params',
            'type' => 'checkbox',
        ],
        'notify_status' => [
            'default' => true,
            'name' => 'notify_status',
            'readonly' => false,
            'required' => false,
            'tab' => 'params',
            'type' => 'checkbox',
        ],
    ];

    private const COMMENT_FIELDS = [
        'body' => [
            'default' => null,
            'maxLength' => null,
            'name' => 'body',
            'readonly' => true,
            'required' => false,
            'rows' => 8,
            'tab' => 'params',
            'type' => 'textarea',
        ],
    ];

    private const TASK_COLUMNS = [
        'assignee.name' => [
            'limit' => null,
            'name' => 'assignee.name',
            'searchable' => false,
            'sortable' => false,
            'width' => null,
        ],
        'code' => [
            'limit' => null,
            'name' => 'code',
            'searchable' => false,
            'sortable' => false,
            'width' => '7rem',
        ],
        'due_date' => [
            'name' => 'due_date',
            'searchable' => false,
            'sortable' => true,
            'width' => null,
        ],
        'priority' => [
            'limit' => null,
            'name' => 'priority',
            'searchable' => false,
            'sortable' => false,
            'width' => null,
        ],
        'status' => [
            'limit' => null,
            'name' => 'status',
            'searchable' => false,
            'sortable' => true,
            'width' => null,
        ],
        'title' => [
            'limit' => null,
            'name' => 'title',
            'searchable' => true,
            'sortable' => true,
            'width' => null,
        ],
        'type' => [
            'limit' => null,
            'name' => 'type',
            'searchable' => false,
            'sortable' => false,
            'width' => null,
        ],
    ];

    private const PROJECT_COLUMNS = [
        'is_archived' => [
            'name' => 'is_archived',
            'searchable' => false,
            'sortable' => false,
            'width' => null,
        ],
        'key' => [
            'limit' => null,
            'name' => 'key',
            'searchable' => true,
            'sortable' => true,
            'width' => null,
        ],
        'lead.name' => [
            'limit' => null,
            'name' => 'lead.name',
            'searchable' => false,
            'sortable' => false,
            'width' => null,
        ],
        'name' => [
            'limit' => null,
            'name' => 'name',
            'searchable' => true,
            'sortable' => true,
            'width' => null,
        ],
    ];

    private const COMMENT_COLUMNS = [
        'author.name' => [
            'limit' => null,
            'name' => 'author.name',
            'searchable' => false,
            'sortable' => false,
            'width' => null,
        ],
        'body' => [
            'limit' => 100,
            'name' => 'body',
            'searchable' => true,
            'sortable' => false,
            'width' => null,
        ],
        'created_at' => [
            'name' => 'created_at',
            'searchable' => false,
            'sortable' => true,
            'width' => null,
        ],
        'task.title' => [
            'limit' => null,
            'name' => 'task.title',
            'searchable' => false,
            'sortable' => false,
            'width' => null,
        ],
    ];

    private const TASK_FILTERS = [
        [
            'name' => 'project_id',
            'type' => 'select',
        ],
        [
            'name' => 'status',
            'type' => 'select',
        ],
        [
            'name' => 'assignee_id',
            'type' => 'select',
        ],
        [
            'name' => 'type',
            'type' => 'select',
        ],
        [
            'name' => 'label',
            'type' => 'select',
        ],
        [
            'name' => 'overdue',
            'type' => 'boolean',
        ],
        [
            'name' => 'mine',
            'type' => 'boolean',
        ],
    ];

    private const PROJECT_FILTERS = [
        [
            'name' => 'is_archived',
            'type' => 'boolean',
        ],
    ];

    /**
     * @param  list<array<string,mixed>>  $items
     * @param  list<string>  $keys
     * @return array<string,array<string,mixed>>
     */
    private function shape(array $items, array $keys): array
    {
        $shape = [];

        foreach ($items as $item) {
            $this->assertStringNotContainsString('atrium-tracker::', json_encode([$item['label'] ?? '', $item['hint'] ?? '']) ?: '', $item['name']);
            $one = array_intersect_key($item, array_flip($keys));

            // Who and what can be chosen depends on the data, not on the module.
            if (in_array($item['name'], ['project_id', 'assignee_id', 'parent_id', 'lead_id', 'members'], true)) {
                unset($one['options']);
            }

            $shape[$item['name']] = $this->sorted($one);
        }

        ksort($shape);

        return $shape;
    }

    /**
     * @param  array<array-key,mixed>  $items
     * @return array<array-key,mixed>
     */
    private function sorted(array $items): array
    {
        foreach ($items as $key => $value) {
            if (is_array($value)) {
                $items[$key] = $this->sorted($value);
            }
        }

        if (! array_is_list($items)) {
            ksort($items);
        }

        return $items;
    }

    /**
     * @return array<string,mixed>
     */
    private function props(string $url): array
    {
        $props = [];
        $this->actingAs($this->admin(), 'admin')->get('/admin/'.$url)->assertOk()->assertInertia(function (AssertableInertia $page) use (&$props): void {
            $props = $page->toArray()['props'];
        });

        return $props;
    }

    /**
     * @param  array<string,mixed>  $props
     * @return list<array<string,mixed>>
     */
    private function fieldsOf(array $props): array
    {
        return $props['fields'];
    }

    public function test_the_task_form_offers_these_fields(): void
    {
        $task = $this->task();

        $props = $this->props("tracker_tasks/{$task->id}/edit");

        $this->assertSame(self::TASK_FIELDS, $this->shape($this->fieldsOf($props), self::KEYS));
        $this->assertSame(['params', 'files', 'activity'], array_column($props['tabs'], 'name'));
    }

    public function test_the_project_form_offers_these_fields(): void
    {
        $this->assertSame(self::PROJECT_FIELDS, $this->shape($this->fieldsOf($this->props('tracker_projects/create')), self::KEYS));
    }

    public function test_the_settings_page_offers_these_fields(): void
    {
        $this->assertSame(self::SETTINGS_FIELDS, $this->shape($this->fieldsOf($this->props('modules/tracker/settings')), self::KEYS));
    }

    public function test_a_comment_is_shown_read_only(): void
    {
        $comment = Comment::query()->create(['task_id' => $this->task()->id, 'body' => 'x']);

        $this->assertSame(self::COMMENT_FIELDS, $this->shape($this->fieldsOf($this->props("tracker_comments/{$comment->id}/edit")), self::KEYS));
    }

    public function test_the_lists_have_these_columns(): void
    {
        $this->task();
        $this->assertSame(self::TASK_COLUMNS, $this->shape($this->props('tracker_tasks')['columns'], self::COLUMN_KEYS));
        $this->assertSame(self::PROJECT_COLUMNS, $this->shape($this->props('tracker_projects')['columns'], self::COLUMN_KEYS));
        $this->assertSame(self::COMMENT_COLUMNS, $this->shape($this->props('tracker_comments')['columns'], self::COLUMN_KEYS));
    }

    public function test_the_lists_have_these_filters(): void
    {
        $this->assertSame(self::TASK_FILTERS, array_map(static fn (array $filter): array => ['name' => $filter['name'], 'type' => $filter['type']], $this->props('tracker_tasks')['filterControls']));
        $this->assertSame(self::PROJECT_FILTERS, array_map(static fn (array $filter): array => ['name' => $filter['name'], 'type' => $filter['type']], $this->props('tracker_projects')['filterControls']));
    }
}
