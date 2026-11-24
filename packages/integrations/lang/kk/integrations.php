<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Интеграциялар',
    'title_log' => 'Алмасу журналы',

    'status' => [
        'ok' => 'Орындалды',
        'retry' => 'Қате, қайталаймыз',
        'failed' => 'Қате',
    ],
    'direction' => [
        'out' => 'Сайттан',
        'in' => 'Сайтқа',
    ],
    'fields' => [
        'when' => 'Қашан',
        'integration' => 'Интеграция',
        'direction' => 'Бағыты',
        'action' => 'Әрекет',
        'subject' => 'Не туралы',
        'status' => 'Нәтиже',
        'message' => 'Не істелді',
        'error' => 'Қате',
        'attempt' => 'Талпыныс',
        'duration' => 'Уақыт, мс',
    ],

    'settings' => [
        'description' => 'Коннектор (1C, Bitrix24) соңғы талпынысын таусқанда кімге хабарланады және алмасу журналы қанша уақыт сақталады.',
        'alert_emails' => 'Ескерту e-mail мекенжайлары',
        'alert_emails_hint' => 'Бір немесе бірнеше мекенжай, үтірмен немесе жаңа жолдан бөлінген. Бос болса: барлық белсенді суперәкімшілер. Бір интеграцияға сағатына бір хаттан артық емес.',
        'retention_days' => 'Журналды сақтау, күн',
        'retention_days_hint' => 'Ескі жазбалар әр түнде жойылады.',
    ],

    'alert' => [
        'subject' => ':site: :integration интеграциясында ақау',
        'what' => ':integration интеграциясы :action әрекетін орындай алмады және талпынысты тоқтатты.',
        'subject_line' => 'Не туралы: :subject',
        'error' => 'Қате: :error',
        'attempts' => 'Жасалған талпыныс: :count',
    ],
];
