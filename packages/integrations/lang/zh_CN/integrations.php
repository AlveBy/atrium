<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '集成',
    'title_log' => '交换日志',

    'status' => [
        'ok' => '完成',
        'retry' => '失败,将重试',
        'failed' => '失败',
    ],
    'direction' => [
        'out' => '从网站',
        'in' => '到网站',
    ],
    'fields' => [
        'when' => '时间',
        'integration' => '集成',
        'direction' => '方向',
        'action' => '操作',
        'subject' => '涉及',
        'status' => '结果',
        'message' => '执行内容',
        'error' => '错误',
        'attempt' => '尝试次数',
        'duration' => '耗时(ms)',
    ],

    'settings' => [
        'description' => '连接器(1C、Bitrix24)在最后一次尝试后放弃时通知谁,以及交换日志保留多久。',
        'alert_emails' => '警报邮箱',
        'alert_emails_hint' => '一个或多个地址,以逗号或换行分隔。留空:所有启用的超级管理员。每个集成每小时最多一封邮件。',
        'retention_days' => '日志保留天数',
        'retention_days_hint' => '较早的记录每晚删除。',
    ],

    'alert' => [
        'subject' => ':site:集成 :integration 失败',
        'what' => '集成 :integration 无法执行 :action,已停止重试。',
        'subject_line' => '涉及: :subject',
        'error' => '错误: :error',
        'attempts' => '尝试次数: :count',
    ],
];
