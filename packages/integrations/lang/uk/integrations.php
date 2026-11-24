<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Інтеграції',
    'title_log' => 'Журнал обміну',

    'status' => [
        'ok' => 'Виконано',
        'retry' => 'Помилка, повторимо',
        'failed' => 'Помилка',
    ],
    'direction' => [
        'out' => 'З сайту',
        'in' => 'На сайт',
    ],
    'fields' => [
        'when' => 'Коли',
        'integration' => 'Інтеграція',
        'direction' => 'Напрям',
        'action' => 'Дія',
        'subject' => 'Про що',
        'status' => 'Результат',
        'message' => 'Що зроблено',
        'error' => 'Помилка',
        'attempt' => 'Спроба',
        'duration' => 'Час, мс',
    ],

    'settings' => [
        'description' => 'Кого сповіщати, коли конектор (1C, Bitrix24) вичерпав останню спробу, і як довго зберігається журнал обміну.',
        'alert_emails' => 'E-mail для сповіщень',
        'alert_emails_hint' => 'Одна або кілька адрес через кому або з нового рядка. Порожньо: усі активні суперадміністратори. Не більше одного листа на годину на інтеграцію.',
        'retention_days' => 'Зберігати журнал, днів',
        'retention_days_hint' => 'Старі записи видаляються щоночі.',
    ],

    'alert' => [
        'subject' => ':site: збій інтеграції :integration',
        'what' => 'Інтеграція :integration не змогла виконати дію :action і припинила спроби.',
        'subject_line' => 'Про що: :subject',
        'error' => 'Помилка: :error',
        'attempts' => 'Зроблено спроб: :count',
    ],
];
