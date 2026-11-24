<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Трэкер',
    'my_tasks' => 'Мае задачы',
    'hours_short' => 'г',
    'thread_empty' => 'Пакуль нічога няма.',

    'projects' => [
        'title' => 'Праекты',
        'add' => 'Дадаць праект',
        'fields' => [
            'name' => 'Назва',
            'key' => 'Ключ',
            'key_hint' => 'Ад двух да дзесяці вялікіх лацінскіх літар або лічбаў, пачынаючы з літары: задачы нумаруюцца ATR-1, ATR-2 …',
            'description' => 'Апісанне',
            'lead' => 'Кіраўнік',
            'members' => 'Удзельнікі',
            'members_hint' => 'Адміністратары, якія працуюць у праекце. Задачу можна аддаць толькі ім і кіраўніку; пакіньце пустым, каб дазволіць усім.',
            'archived' => 'У архіве',
            'archived_hint' => 'У архіўны праект новыя задачы не трапляюць; яго задачы застаюцца.',
        ],
    ],

    'tasks' => [
        'title' => 'Задачы',
        'add' => 'Дадаць задачу',
        'tabs' => [
            'params' => 'Задача',
            'files' => 'Файлы',
            'activity' => 'Каментарыі і гісторыя',
        ],
        'fields' => [
            'code' => 'Задача',
            'project' => 'Праект',
            'type' => 'Тып',
            'title' => 'Загаловак',
            'description' => 'Апісанне',
            'status' => 'Статус',
            'priority' => 'Прыярытэт',
            'assignee' => 'Выканаўца',
            'parent' => 'Бацькоўская задача',
            'parent_hint' => 'Падзадача іншай задачы або задача эпіка, у тым жа праекце.',
            'labels' => 'Меткі',
            'add_label' => 'Дадаць метку',
            'due_date' => 'Тэрмін',
            'estimate' => 'Ацэнка',
            'attachments' => 'Укладанні',
            'new_comment' => 'Новы каментарый',
            'new_comment_hint' => 'Дадасца да задачы, калі вы яе захаваеце.',
            'thread' => 'Каментарыі і гісторыя',
        ],
        'filters' => [
            'label' => 'Метка',
            'overdue' => 'Пратэрмінаваныя',
            'mine' => 'Мае задачы',
        ],
    ],

    'comments' => [
        'title' => 'Каментарыі да задач',
        'fields' => [
            'body' => 'Каментарый',
            'task' => 'Задача',
            'author' => 'Аўтар',
            'created' => 'Напісаны',
        ],
    ],

    'types' => [
        'task' => 'Задача',
        'bug' => 'Памылка',
        'story' => 'Гісторыя',
        'epic' => 'Эпік',
    ],

    'priorities' => [
        'lowest' => 'Найніжэйшы',
        'low' => 'Нізкі',
        'medium' => 'Сярэдні',
        'high' => 'Высокі',
        'highest' => 'Найвышэйшы',
    ],

    'statuses' => [
        'todo' => 'Да выканання',
        'in_progress' => 'У працы',
        'review' => 'На праверцы',
        'done' => 'Гатова',
    ],

    'kinds' => [
        'comment' => 'Каментарый',
        'created' => 'Створана',
        'status' => 'Статус',
        'priority' => 'Прыярытэт',
        'assignee' => 'Выканаўца',
    ],

    'events' => [
        'created' => 'Задача створана.',
        'status' => 'Статус: :old → :new',
        'priority' => 'Прыярытэт: :old → :new',
        'assignee' => 'Выканаўца: :old → :new',
    ],

    'errors' => [
        'archived' => 'Праект у архіве: пераносіць у яго задачы нельга.',
        'not_in_project' => 'Гэты адміністратар не працуе ў праекце.',
        'epic_has_parent' => 'У эпіка не бывае бацькоўскай задачы.',
        'parent_other_project' => 'Бацькоўская задача павінна быць з таго ж праекта.',
        'parent_loop' => 'Задача не можа быць даччынай самой сабе або сваёй падзадачы.',
    ],

    'settings' => [
        'description' => 'Статусы задач і лісты пра іх.',
        'statuses' => 'Статусы',
        'statuses_hint' => 'Па парадку: новая задача атрымлівае першы, апошні азначае «гатова». Пуста: :default. Калі перайменаваць статус тут, у задач застаецца старая назва — змяніце і ў задачах.',
        'add_status' => 'Дадаць статус',
        'notify_assigned' => 'Пісаць выканаўцу',
        'notify_assigned_hint' => 'Ліст, калі задачу аддаюць адміністратару.',
        'notify_status' => 'Пісаць пра новы статус',
        'notify_status_hint' => 'Ліст выканаўцу, калі мяняецца статус яго задачы.',
    ],

    'mail' => [
        'assigned_subject' => ':code перададзена вам: :title',
        'assigned_line' => 'Вам перададзена задача :code «:title».',
        'status_subject' => ':code цяпер «:status»',
        'status_line' => 'Статус задачы :code «:title» змяніўся: :old → :new.',
    ],
];
