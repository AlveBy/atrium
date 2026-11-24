<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1С',
    'title_runs' => 'Обмін з 1С',

    'settings' => [
        'description' => 'Каталог надходить із 1С:Підприємство за стандартним обміном CommerceML. У 1С, у налаштуваннях обміну із сайтом, вкажіть цю адресу: :url (конфігурація, яка питає лише сайт, бере :bitrix після адреси сайту), а нижче логін і пароль, які ви задали тут.',
        'login' => 'Логін',
        'login_hint' => 'Під яким іменем входить 1С. Обмін закритий, доки не задано і логін, і пароль.',
        'password' => 'Пароль',
        'password_hint' => 'Зберігається в зашифрованому вигляді й більше не показується: щоб замінити, введіть новий; залиште поле порожнім, щоб зберегти поточний.',
    ],

    'fields' => [
        'started_at' => 'Початок',
        'finished_at' => 'Завершення',
        'file' => 'Файл',
        'status_label' => 'Результат',
        'sections_created' => 'Нові розділи',
        'sections_updated' => 'Розділи оновлено',
        'products_created' => 'Нові товари',
        'products_updated' => 'Товари оновлено',
        'offers_updated' => 'Ціни та залишки оновлено',
        'skipped' => 'Пропущені пропозиції (немає такого товару)',
        'errors' => 'Помилки',
        'error' => 'Що пішло не так',
    ],

    'status' => [
        'running' => 'Виконується',
        'done' => 'Готово',
        'failed' => 'Збій',
    ],

    'reply' => [
        'wrong_login' => 'Невірний логін або пароль, або обмін не налаштовано в Модулі → 1С.',
        'denied' => 'Вхід не виконано: почніть обмін наново.',
        'unknown_type' => 'Невідомий тип обміну: :type.',
        'unknown_mode' => 'Невідомий крок обміну: :mode.',
        'bad_name' => 'Така назва файлу не допускається.',
        'no_file' => 'Файл :file не надійшов.',
        'progress' => 'Завантажено позицій: :count.',
        'summary' => 'Розділи: :sections_created нових, :sections_updated оновлено. Товари: :products_created нових, :products_updated оновлено. Ціни та залишки: :offers. Пропущено: :skipped. Помилок: :errors.',
        'no_orders' => 'Замовлення не обмінюються: модуль магазину не встановлено.',
    ],
];
