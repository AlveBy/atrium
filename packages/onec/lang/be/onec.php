<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1С',
    'title_runs' => 'Абмен з 1С',

    'settings' => [
        'description' => 'Каталог прыходзіць з 1С:Прадпрыемства па стандартным абмене CommerceML. У 1С, у наладах абмену з сайтам, укажыце гэты адрас: :url (канфігурацыя, якая пытае толькі сайт, бярэ :bitrix пасля адраса сайта), а ніжэй лагін і пароль, якія вы задалі тут.',
        'login' => 'Лагін',
        'login_hint' => 'Пад якім імем уваходзіць 1С. Абмен закрыты, пакуль не зададзены і лагін, і пароль.',
        'password' => 'Пароль',
        'password_hint' => 'Захоўваецца ў зашыфраваным выглядзе і больш не паказваецца: каб замяніць, увядзіце новы; пакіньце поле пустым, каб захаваць бягучы.',
    ],

    'fields' => [
        'started_at' => 'Пачата',
        'finished_at' => 'Завершана',
        'file' => 'Файл',
        'status_label' => 'Вынік',
        'sections_created' => 'Новыя раздзелы',
        'sections_updated' => 'Раздзелы абноўлены',
        'products_created' => 'Новыя тавары',
        'products_updated' => 'Тавары абноўлены',
        'offers_updated' => 'Цэны і астаткі абноўлены',
        'skipped' => 'Прапушчаныя прапановы (няма такога тавару)',
        'errors' => 'Памылкі',
        'error' => 'Што пайшло не так',
    ],

    'status' => [
        'running' => 'Выконваецца',
        'done' => 'Гатова',
        'failed' => 'Збой',
    ],

    'reply' => [
        'wrong_login' => 'Няправільны лагін або пароль, або абмен не наладжаны ў Модулі → 1С.',
        'denied' => 'Уваход не выкананы: пачніце абмен нанова.',
        'unknown_type' => 'Невядомы тып абмену: :type.',
        'unknown_mode' => 'Невядомы крок абмену: :mode.',
        'bad_name' => 'Такая назва файла не дапускаецца.',
        'no_file' => 'Файл :file не прыйшоў.',
        'progress' => 'Загружана пазіцый: :count.',
        'summary' => 'Раздзелы: :sections_created новых, :sections_updated абноўлена. Тавары: :products_created новых, :products_updated абноўлена. Цэны і астаткі: :offers. Прапушчана: :skipped. Памылак: :errors.',
        'no_orders' => 'Заказы не абменьваюцца: модуль крамы не ўстаноўлены.',
    ],
];
