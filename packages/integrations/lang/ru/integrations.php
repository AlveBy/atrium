<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Интеграции',
    'title_log' => 'Журнал обмена',

    'status' => [
        'ok' => 'Выполнено',
        'retry' => 'Ошибка, повторим',
        'failed' => 'Ошибка',
    ],
    'direction' => [
        'out' => 'С сайта',
        'in' => 'На сайт',
    ],
    'fields' => [
        'when' => 'Когда',
        'integration' => 'Интеграция',
        'direction' => 'Направление',
        'action' => 'Действие',
        'subject' => 'О чём',
        'status' => 'Результат',
        'message' => 'Что сделано',
        'error' => 'Ошибка',
        'attempt' => 'Попытка',
        'duration' => 'Время, мс',
    ],

    'settings' => [
        'description' => 'Кого уведомлять, когда коннектор (1C, Bitrix24) исчерпал последнюю попытку, и как долго хранится журнал обмена.',
        'alert_emails' => 'E-mail для оповещений',
        'alert_emails_hint' => 'Один или несколько адресов через запятую или с новой строки. Пусто: все активные суперадминистраторы. Не больше одного письма в час на интеграцию.',
        'retention_days' => 'Хранить журнал, дней',
        'retention_days_hint' => 'Старые записи удаляются каждую ночь.',
    ],

    'alert' => [
        'subject' => ':site: сбой интеграции :integration',
        'what' => 'Интеграция :integration не смогла выполнить действие :action и прекратила попытки.',
        'subject_line' => 'О чём: :subject',
        'error' => 'Ошибка: :error',
        'attempts' => 'Сделано попыток: :count',
    ],
];
