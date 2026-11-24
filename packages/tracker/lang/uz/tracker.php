<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Trekker',
    'my_tasks' => 'Mening vazifalarim',
    'hours_short' => 'soat',
    'thread_empty' => 'Hozircha hech narsa yoʻq.',

    'projects' => [
        'title' => 'Loyihalar',
        'add' => 'Loyiha qoʻshish',
        'fields' => [
            'name' => 'Nomi',
            'key' => 'Kalit',
            'key_hint' => 'Harfdan boshlanadigan ikkidan oʻngacha bosh lotin harfi yoki raqam: vazifalar ATR-1, ATR-2 … deb raqamlanadi',
            'description' => 'Tavsif',
            'lead' => 'Rahbar',
            'members' => 'Ishtirokchilar',
            'members_hint' => 'Loyihada ishlaydigan administratorlar. Vazifani faqat ularga va rahbarga berish mumkin; hammaga ruxsat berish uchun boʻsh qoldiring.',
            'archived' => 'Arxivda',
            'archived_hint' => 'Arxivdagi loyihaga yangi vazifalar tushmaydi; uning vazifalari qoladi.',
        ],
    ],

    'tasks' => [
        'title' => 'Vazifalar',
        'add' => 'Vazifa qoʻshish',
        'tabs' => [
            'params' => 'Vazifa',
            'files' => 'Fayllar',
            'activity' => 'Izohlar va tarix',
        ],
        'fields' => [
            'code' => 'Vazifa',
            'project' => 'Loyiha',
            'type' => 'Turi',
            'title' => 'Sarlavha',
            'description' => 'Tavsif',
            'status' => 'Holati',
            'priority' => 'Ustuvorlik',
            'assignee' => 'Ijrochi',
            'parent' => 'Asosiy vazifa',
            'parent_hint' => 'Boshqa vazifaning kichik vazifasi yoki epikning vazifasi, shu loyihada.',
            'labels' => 'Yorliqlar',
            'add_label' => 'Yorliq qoʻshish',
            'due_date' => 'Muddat',
            'estimate' => 'Baho',
            'attachments' => 'Ilovalar',
            'new_comment' => 'Yangi izoh',
            'new_comment_hint' => 'Vazifani saqlaganingizda unga qoʻshiladi.',
            'thread' => 'Izohlar va tarix',
        ],
        'filters' => [
            'label' => 'Yorliq',
            'overdue' => 'Muddati oʻtgan',
            'mine' => 'Mening vazifalarim',
        ],
    ],

    'comments' => [
        'title' => 'Vazifa izohlari',
        'fields' => [
            'body' => 'Izoh',
            'task' => 'Vazifa',
            'author' => 'Muallif',
            'created' => 'Yozilgan vaqti',
        ],
    ],

    'types' => [
        'task' => 'Vazifa',
        'bug' => 'Xato',
        'story' => 'Hikoya',
        'epic' => 'Epik',
    ],

    'priorities' => [
        'lowest' => 'Eng past',
        'low' => 'Past',
        'medium' => 'Oʻrtacha',
        'high' => 'Yuqori',
        'highest' => 'Eng yuqori',
    ],

    'statuses' => [
        'todo' => 'Bajarilishi kerak',
        'in_progress' => 'Jarayonda',
        'review' => 'Tekshiruvda',
        'done' => 'Tayyor',
    ],

    'kinds' => [
        'comment' => 'Izoh',
        'created' => 'Yaratildi',
        'status' => 'Holati',
        'priority' => 'Ustuvorlik',
        'assignee' => 'Ijrochi',
    ],

    'events' => [
        'created' => 'Vazifa yaratildi.',
        'status' => 'Holati: :old → :new',
        'priority' => 'Ustuvorlik: :old → :new',
        'assignee' => 'Ijrochi: :old → :new',
    ],

    'errors' => [
        'archived' => 'Loyiha arxivda: unga vazifalarni koʻchirib boʻlmaydi.',
        'not_in_project' => 'Bu administrator loyihada ishlamaydi.',
        'epic_has_parent' => 'Epikning asosiy vazifasi boʻlmaydi.',
        'parent_other_project' => 'Asosiy vazifa shu loyihadan boʻlishi kerak.',
        'parent_loop' => 'Vazifa oʻziga yoki oʻzining kichik vazifasiga bola boʻla olmaydi.',
    ],

    'settings' => [
        'description' => 'Vazifa holatlari va ular haqidagi xatlar.',
        'statuses' => 'Holatlar',
        'statuses_hint' => 'Tartib boʻyicha: yangi vazifa birinchisini oladi, oxirgisi «tayyor» degani. Boʻsh boʻlsa: :default. Holatni bu yerda qayta nomlasangiz, vazifalarda eski nom qoladi — vazifalarda ham oʻzgartiring.',
        'add_status' => 'Holat qoʻshish',
        'notify_assigned' => 'Ijrochiga yozish',
        'notify_assigned_hint' => 'Vazifa administratorga berilganda xat.',
        'notify_status' => 'Yangi holat haqida yozish',
        'notify_status_hint' => 'Ijrochining vazifasi holati oʻzgarganda unga xat.',
    ],

    'mail' => [
        'assigned_subject' => ':code sizga berildi: :title',
        'assigned_line' => 'Sizga :code «:title» vazifasi berildi.',
        'status_subject' => ':code endi «:status»',
        'status_line' => ':code «:title» vazifasining holati oʻzgardi: :old → :new.',
    ],
];
