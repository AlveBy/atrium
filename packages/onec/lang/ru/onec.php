<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1С',
    'title_runs' => 'Обмен с 1С',

    'settings' => [
        'description' => 'Каталог приходит из 1С:Предприятия по стандартному обмену CommerceML. В 1С, в обмене с сайтом, укажите этот адрес: :url (конфигурация, которая спрашивает только сайт, после адреса сайта берёт :bitrix), а ниже логин и пароль, заданные здесь.',
        'login' => 'Логин',
        'login_hint' => 'С чем 1С входит на сайт. Пока не заданы и логин, и пароль, обмен закрыт.',
        'password' => 'Пароль',
        'password_hint' => 'Хранится зашифрованным и больше не показывается: чтобы поменять, введите новый; пустое поле оставит текущий.',
    ],

    'fields' => [
        'started_at' => 'Начало',
        'finished_at' => 'Конец',
        'file' => 'Файл',
        'status_label' => 'Результат',
        'sections_created' => 'Новых разделов',
        'sections_updated' => 'Разделов обновлено',
        'products_created' => 'Новых товаров',
        'products_updated' => 'Товаров обновлено',
        'offers_updated' => 'Цен и остатков обновлено',
        'skipped' => 'Пропущено предложений (нет такого товара)',
        'errors' => 'Ошибок',
        'error' => 'Что пошло не так',
    ],

    'status' => [
        'running' => 'Идёт',
        'done' => 'Выполнено',
        'failed' => 'Ошибка',
    ],

    'reply' => [
        'wrong_login' => 'Неверный логин или пароль, либо обмен не настроен в Модули → 1С.',
        'denied' => 'Вход не выполнен: начните обмен заново.',
        'unknown_type' => 'Неизвестный тип обмена: :type.',
        'unknown_mode' => 'Неизвестный шаг обмена: :mode.',
        'bad_name' => 'Такое имя файла не допускается.',
        'no_file' => 'Файл :file не пришёл.',
        'progress' => 'Загружено позиций: :count.',
        'summary' => 'Разделы: :sections_created новых, :sections_updated обновлено. Товары: :products_created новых, :products_updated обновлено. Цены и остатки: :offers. Пропущено: :skipped. Ошибок: :errors.',
        'no_orders' => 'Заказы не обмениваются: модуль магазина не установлен.',
    ],
];
