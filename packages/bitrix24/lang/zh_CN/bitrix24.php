<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => '站点表单：Bitrix24',

    'settings' => [
        'description' => '表单提交的内容会作为线索进入您的 Bitrix24，Bitrix24 的聊天小部件也会显示在网站上。请在 Bitrix24 中创建一个具有 CRM 权限的传入 Webhook（开发者资源 → 其他 → 传入 Webhook），并把它的地址粘贴到这里。',
        'webhook' => '传入 Webhook',
        'webhook_hint' => 'Bitrix24 显示的地址，形如 https://portal.bitrix24.com/rest/1/code/。该地址以加密形式保存，之后不会再显示：要更换，请粘贴新的地址;留空则保留当前地址。',
        'send_forms' => '把表单提交的内容发送到 Bitrix24',
        'send_forms_hint' => '下面没有设置关联的表单会作为线索发送，各字段按类型归位。',
        'widget_code' => '聊天小部件代码',
        'widget_code_hint' => 'Bitrix24 中开放线路或聊天小部件的代码（CRM → 联络中心 → 在线聊天 → 小部件）。它会放在网站每个页面的末尾;留空则不显示小部件。',
        'check' => '检查连接',
        'connected' => '已连接：:portal，用户 :user。',
    ],

    'fields' => [
        'form' => '表单',
        'mode' => '发送为',
        'active' => '把此表单发送到 Bitrix24',
        'mapping' => '字段',
        'mapping_hint' => '每个字段一行："表单字段 = Bitrix24 字段"，例如 "phone = PHONE" 或 "message = COMMENTS"。没有对应行的字段按类型归位（邮箱到 EMAIL，电话到 PHONE）;无处安放的内容放入备注。以 # 开头的行会被忽略。',
    ],

    'modes' => [
        'lead' => '线索',
        'deal' => '交易（含联系人）',
    ],

    'errors' => [
        'no_webhook' => '未设置 Bitrix24 Webhook：请在"模块 → Bitrix24"中粘贴。',
        'unreachable' => 'Bitrix24 没有响应。请检查 Webhook 中的门户地址。',
        'portal' => 'Bitrix24 拒绝了请求：:error',
        'mapping_line' => '第 :line 行不符合"表单字段 = Bitrix24 字段"的格式。',
    ],

    'payload' => [
        'subject' => '表单提交 #:id',
        'title' => '网站：:form',
        'visitor' => '网站访客',
        'yes' => '是',
        'lead' => '已创建线索 #:id。',
        'deal' => '已创建交易 #:deal，联系人 #:contact。',
        'gone' => '该提交在发送前已被删除。',
    ],
];
