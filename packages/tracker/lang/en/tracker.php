<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Tracker',
    'my_tasks' => 'My tasks',
    'hours_short' => 'h',
    'thread_empty' => 'Nothing here yet.',

    'projects' => [
        'title' => 'Projects',
        'add' => 'Add project',
        'fields' => [
            'name' => 'Name',
            'key' => 'Key',
            'key_hint' => 'Two to ten capital letters or digits, starting with a letter: the tasks are numbered ATR-1, ATR-2 …',
            'description' => 'Description',
            'lead' => 'Lead',
            'members' => 'Members',
            'members_hint' => 'Administrators who work in the project. A task can be given only to them and the lead; leave it empty to allow everyone.',
            'archived' => 'Archived',
            'archived_hint' => 'No new tasks go into an archived project; its tasks stay.',
        ],
    ],

    'tasks' => [
        'title' => 'Tasks',
        'add' => 'Add task',
        'tabs' => [
            'params' => 'Task',
            'files' => 'Files',
            'activity' => 'Comments and history',
        ],
        'fields' => [
            'code' => 'Task',
            'project' => 'Project',
            'type' => 'Type',
            'title' => 'Title',
            'description' => 'Description',
            'status' => 'Status',
            'priority' => 'Priority',
            'resolution' => 'Resolution',
            'assignee' => 'Assignee',
            'parent' => 'Parent task',
            'parent_hint' => 'A subtask of a task, or a task of an epic, in the same project.',
            'labels' => 'Labels',
            'add_label' => 'Add a label',
            'due_date' => 'Due date',
            'estimate' => 'Estimate',
            'attachments' => 'Attachments',
            'new_comment' => 'New comment',
            'new_comment_hint' => 'Added to the task when you save it.',
            'thread' => 'Comments and history',
        ],
        'filters' => [
            'label' => 'Label',
            'overdue' => 'Overdue',
            'mine' => 'My tasks',
        ],
    ],

    'comments' => [
        'title' => 'Task comments',
        'fields' => [
            'body' => 'Comment',
            'task' => 'Task',
            'author' => 'Author',
            'created' => 'Written',
        ],
    ],

    'terms' => [
        'types' => 'Task types',
        'priorities' => 'Priorities',
        'statuses' => 'Statuses',
        'resolutions' => 'Resolutions',
        'fields' => [
            'code' => 'Code',
            'code_hint' => 'Small letters, digits and _. Tasks keep the code, so it cannot be changed later.',
            'name' => 'Name',
            'name_hint' => 'Empty: the built-in name, in the language of each administrator.',
            'sort' => 'Sort',
            'default' => 'Default',
            'default_hint' => 'A new task gets this one. Without a mark, the first.',
            'category' => 'Category',
            'symbol' => 'Symbol',
            'symbol_hint' => 'Shown before the name, so the priority is not told by colour alone.',
            'top_level' => 'No parent',
            'top_level_hint' => 'Tasks of this type stand at the top, like an epic.',
            'needs_parent' => 'Needs a parent',
            'needs_parent_hint' => 'Tasks of this type always belong to another task, like a sub-task.',
        ],
        'categories' => [
            'todo' => 'To do',
            'active' => 'In progress',
            'done' => 'Done',
        ],
    ],

    'types' => [
        'task' => 'Task',
        'bug' => 'Bug',
        'story' => 'Story',
        'epic' => 'Epic',
        'subtask' => 'Sub-task',
    ],

    'resolutions' => [
        'done' => 'Done',
        'wont_do' => 'Won’t do',
        'duplicate' => 'Duplicate',
        'cannot_reproduce' => 'Cannot reproduce',
    ],

    'priorities' => [
        'lowest' => 'Lowest',
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'highest' => 'Highest',
    ],

    'statuses' => [
        'todo' => 'To do',
        'in_progress' => 'In progress',
        'review' => 'In review',
        'done' => 'Done',
    ],

    'kinds' => [
        'comment' => 'Comment',
        'created' => 'Created',
        'status' => 'Status',
        'priority' => 'Priority',
        'assignee' => 'Assignee',
    ],

    'events' => [
        'created' => 'The task was created.',
        'status' => 'Status: :old → :new',
        'priority' => 'Priority: :old → :new',
        'assignee' => 'Assignee: :old → :new',
    ],

    'errors' => [
        'archived' => 'The project is archived: no tasks can be moved into it.',
        'not_in_project' => 'This administrator does not work in the project.',
        'type_has_no_parent' => 'A task of this type has no parent task.',
        'needs_parent' => 'A task of this type needs a parent task.',
        'in_use' => 'Tasks still use this: change them first.',
        'parent_other_project' => 'The parent task must be in the same project.',
        'parent_loop' => 'A task cannot be a child of itself or of one of its subtasks.',
    ],

    'settings' => [
        'description' => 'The letters about tasks.',
        'notify_assigned' => 'Write to the assignee',
        'notify_assigned_hint' => 'A letter when a task is given to an administrator.',
        'notify_status' => 'Write about a new status',
        'notify_status_hint' => 'A letter to the assignee when the status of their task changes.',
        'disabled_plugins' => 'Switched-off plugins',
        'disabled_plugins_hint' => 'Plugins you tick are ignored; a new plugin is on until you tick it.',
    ],

    'mail' => [
        'assigned_subject' => ':code was given to you: :title',
        'assigned_line' => 'The task :code “:title” was given to you.',
        'status_subject' => ':code is now “:status”',
        'status_line' => 'The status of :code “:title” changed: :old → :new.',
    ],
];
