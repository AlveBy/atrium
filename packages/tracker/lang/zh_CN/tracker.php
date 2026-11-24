<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '任务跟踪',
    'my_tasks' => '我的任务',
    'hours_short' => '小时',
    'thread_empty' => '暂无内容。',

    'projects' => [
        'title' => '项目',
        'add' => '添加项目',
        'fields' => [
            'name' => '名称',
            'key' => '标识',
            'key_hint' => '以字母开头的 2 到 10 位大写拉丁字母或数字：任务编号形如 ATR-1、ATR-2 …',
            'description' => '描述',
            'lead' => '负责人',
            'members' => '成员',
            'members_hint' => '在该项目中工作的管理员。任务只能分配给他们和负责人；留空则允许所有人。',
            'archived' => '已归档',
            'archived_hint' => '已归档的项目不再接收新任务；其中的任务保留。',
        ],
    ],

    'tasks' => [
        'title' => '任务',
        'add' => '添加任务',
        'tabs' => [
            'params' => '任务',
            'files' => '文件',
            'activity' => '评论与历史',
        ],
        'fields' => [
            'code' => '任务',
            'project' => '项目',
            'type' => '类型',
            'title' => '标题',
            'description' => '描述',
            'status' => '状态',
            'priority' => '优先级',
            'assignee' => '经办人',
            'parent' => '父任务',
            'parent_hint' => '同一项目中某个任务的子任务，或史诗的任务。',
            'labels' => '标签',
            'add_label' => '添加标签',
            'due_date' => '截止日期',
            'estimate' => '预估',
            'attachments' => '附件',
            'new_comment' => '新评论',
            'new_comment_hint' => '保存时会添加到任务中。',
            'thread' => '评论与历史',
        ],
        'filters' => [
            'label' => '标签',
            'overdue' => '已逾期',
            'mine' => '我的任务',
        ],
    ],

    'comments' => [
        'title' => '任务评论',
        'fields' => [
            'body' => '评论',
            'task' => '任务',
            'author' => '作者',
            'created' => '写于',
        ],
    ],

    'types' => [
        'task' => '任务',
        'bug' => '缺陷',
        'story' => '故事',
        'epic' => '史诗',
    ],

    'priorities' => [
        'lowest' => '最低',
        'low' => '低',
        'medium' => '中',
        'high' => '高',
        'highest' => '最高',
    ],

    'statuses' => [
        'todo' => '待办',
        'in_progress' => '进行中',
        'review' => '审核中',
        'done' => '已完成',
    ],

    'kinds' => [
        'comment' => '评论',
        'created' => '已创建',
        'status' => '状态',
        'priority' => '优先级',
        'assignee' => '经办人',
    ],

    'events' => [
        'created' => '任务已创建。',
        'status' => '状态：:old → :new',
        'priority' => '优先级：:old → :new',
        'assignee' => '经办人：:old → :new',
    ],

    'errors' => [
        'archived' => '项目已归档：不能再把任务移入。',
        'not_in_project' => '该管理员不在此项目中工作。',
        'epic_has_parent' => '史诗没有父任务。',
        'parent_other_project' => '父任务必须属于同一项目。',
        'parent_loop' => '任务不能成为自己或自己子任务的子任务。',
    ],

    'settings' => [
        'description' => '任务的状态以及相关邮件。',
        'statuses' => '状态',
        'statuses_hint' => '按顺序：新任务取第一个，最后一个表示“已完成”。留空时：:default。在此重命名状态后，任务仍保留旧名称——请同时在任务中修改。',
        'add_status' => '添加状态',
        'notify_assigned' => '给经办人发邮件',
        'notify_assigned_hint' => '任务分配给管理员时发送邮件。',
        'notify_status' => '新状态时发邮件',
        'notify_status_hint' => '经办人的任务状态变化时给其发送邮件。',
    ],

    'mail' => [
        'assigned_subject' => '任务 :code 已分配给您：:title',
        'assigned_line' => '任务 :code“:title”已分配给您。',
        'status_subject' => ':code 现在是“:status”',
        'status_line' => '任务 :code“:title”的状态已变化：:old → :new。',
    ],
];
