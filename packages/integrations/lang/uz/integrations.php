<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Integratsiyalar',
    'title_log' => 'Almashuv jurnali',

    'status' => [
        'ok' => 'Bajarildi',
        'retry' => 'Xato, qayta uriniladi',
        'failed' => 'Xato',
    ],
    'direction' => [
        'out' => 'Saytdan',
        'in' => 'Saytga',
    ],
    'fields' => [
        'when' => 'Qachon',
        'integration' => 'Integratsiya',
        'direction' => 'Yo‘nalish',
        'action' => 'Amal',
        'subject' => 'Nima haqida',
        'status' => 'Natija',
        'message' => 'Nima qilindi',
        'error' => 'Xato',
        'attempt' => 'Urinish',
        'duration' => 'Vaqt, ms',
    ],

    'settings' => [
        'description' => 'Konnektor (1C, Bitrix24) oxirgi urinishdan keyin taslim bo‘lganda kimga xabar beriladi va almashuv jurnali qancha saqlanadi.',
        'alert_emails' => 'Ogohlantirish e-mail manzillari',
        'alert_emails_hint' => 'Bir yoki bir nechta manzil, vergul yoki yangi qator bilan ajratiladi. Bo‘sh bo‘lsa: barcha faol superadministratorlar. Har bir integratsiya uchun soatiga bittadan ko‘p xat yuborilmaydi.',
        'retention_days' => 'Jurnalni saqlash, kun',
        'retention_days_hint' => 'Eski yozuvlar har kecha o‘chiriladi.',
    ],

    'alert' => [
        'subject' => ':site: :integration integratsiyasida nosozlik',
        'what' => ':integration integratsiyasi :action amalini bajara olmadi va urinishni to‘xtatdi.',
        'subject_line' => 'Nima haqida: :subject',
        'error' => 'Xato: :error',
        'attempts' => 'Urinishlar soni: :count',
    ],
];
