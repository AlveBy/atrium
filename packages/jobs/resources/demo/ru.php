<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Russian: a made-up company in Russia.
 *
 * `country` is used when Russian is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in Russian, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'RU',
        'currency' => 'RUB',
        'domain' => 'example.ru',
        'offices' => [
            ['name' => 'Москва', 'street' => 'ул. Примерная, д. 12, офис 304', 'postal_code' => '123100', 'region' => null],
            ['name' => 'Екатеринбург', 'street' => 'Образцовый пер., д. 7', 'postal_code' => '620014', 'region' => 'Свердловская область'],
        ],
        'contact' => ['name' => 'Марина Козлова', 'email' => 'hr', 'phone' => '+7 495 000-00-42'],
        // Gross. A month is how salaries are stated in Russia (ads often quote
        // the net figure, the demo keeps gross); interns get a monthly stipend.
        'salaries' => [
            'backend' => ['from' => 250000, 'to' => 350000, 'period' => 'month'],
            'designer' => ['from' => 180000, 'to' => 250000, 'period' => 'month'],
            'account' => null,
            'intern' => ['from' => 40000, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => 'Дмитрий Орлов', 'email' => 'd.orlov'],
            ['name' => 'Ирина Волкова', 'email' => 'irina.volkova'],
            ['name' => 'Екатерина Лебедева', 'email' => 'e.lebedeva'],
            ['name' => 'Артём Соколов', 'email' => 'artem.sokolov'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Разработка',
            'design' => 'Дизайн',
            'sales' => 'Продажи',
            'support' => 'Поддержка клиентов',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Backend-разработчик (PHP, Laravel)',
                'code' => 'backend-razrabotchik',
                'preview_text' => 'Разрабатывайте сервисы, на которых держится наш продукт, в небольшой команде с еженедельными релизами.',
                'description' => '<p>В команде восемь разработчиков. Вы будете вести задачи целиком — от базы данных до API.</p>',
                'duties' => '<ul><li>Проектировать и разрабатывать REST API</li><li>Проводить код-ревью коллег</li><li>Следить, чтобы тесты оставались зелёными</li></ul>',
                'requirements' => '<ul><li>Опыт разработки на PHP от 3 лет</li><li>Laravel, SQL, Git</li><li>Грамотная письменная речь</li></ul>',
                'conditions' => '<ul><li>Два дня в неделю в офисе</li><li>ДМС</li><li>Ежегодный бюджет на обучение</li></ul>',
            ],
            'designer' => [
                'name' => 'Продуктовый дизайнер',
                'code' => 'produktovyy-dizayner',
                'preview_text' => 'Определяйте, как клиенты пользуются нашим приложением, — от первого эскиза до релиза.',
                'description' => '<p>В команде два дизайнера, вы будете тесно работать с продакт-менеджерами и разработчиками.</p>',
                'duties' => '<ul><li>Проводить интервью с пользователями и тестировать прототипы</li><li>Поддерживать порядок в дизайн-системе</li></ul>',
                'requirements' => '<ul><li>Портфолио с запущенными продуктами</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Удалённая работа из любого города страны</li><li>Ноутбук и монитор на ваш выбор</li></ul>',
            ],
            'account' => [
                'name' => 'Аккаунт-менеджер',
                'code' => 'akkaunt-menedzher',
                'preview_text' => 'Сопровождайте наших корпоративных клиентов и помогайте им получать от продукта больше.',
                'description' => '<p>За вами будет около тридцати своих клиентов, а за спиной — команда, которая всегда поможет.</p>',
                'duties' => '<ul><li>Подключать новых клиентов</li><li>Встречаться с каждым клиентом хотя бы раз в квартал</li></ul>',
                'requirements' => '<ul><li>Опыт в продажах или клиентском сервисе от года</li><li>Умение писать понятно и доброжелательно</li></ul>',
                'conditions' => '<ul><li>Бонус за продление договоров</li><li>Тихий офис рядом с общественным транспортом</li></ul>',
            ],
            'intern' => [
                'name' => 'Стажёр службы поддержки',
                'code' => 'stazher-podderzhki',
                'preview_text' => 'Три месяца, двадцать часов в неделю, с наставником — хороший старт в IT.',
                'description' => '<p>Вы будете отвечать на вопросы клиентов по почте и в чате и разберётесь, как продукт устроен изнутри.</p>',
                'duties' => '<ul><li>Отвечать клиентам по почте и в чате</li><li>Записывать вопросы, которые задают чаще всего</li></ul>',
                'requirements' => '<ul><li>Рассматриваем студентов</li><li>Терпение и желание помогать</li></ul>',
                'conditions' => '<ul><li>Гибкий график, удобный для учёбы</li><li>Лучшим стажёрам — предложение о работе</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Приглашение на собеседование',
                'subject' => 'Ваш отклик на вакансию «:vacancy»',
                'body' => "Здравствуйте, :name!\n\nСпасибо за отклик на вакансию «:vacancy». Мы хотели бы с вами познакомиться: напишите, пожалуйста, два-три удобных времени на следующей неделе.\n\nС уважением,\n:company",
            ],
            'rejection' => [
                'name' => 'Отказ',
                'subject' => 'Ваш отклик на вакансию «:vacancy»',
                'body' => "Здравствуйте, :name!\n\nСпасибо за интерес к вакансии «:vacancy». В этот раз мы решили продолжить с другими кандидатами. Мы сохраним ваше резюме и свяжемся с вами, если откроется подходящая позиция.\n\nС уважением,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Здравствуйте! У меня пять лет опыта с Laravel, очень хочу в вашу команду.',
            'Шесть лет работаю с PHP и PostgreSQL, в основном над API.',
            'Добрый день! Ссылка на портфолио — в резюме.',
            'Подходящей вакансии пока нет, но очень хочу работать у вас QA-инженером.',
        ],
    ],
];
