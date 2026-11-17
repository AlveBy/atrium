<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Ukrainian: a made-up company in Ukraine.
 *
 * `country` is used when Ukrainian is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in Ukrainian, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'UA',
        'currency' => 'UAH',
        'domain' => 'example.com.ua',
        'offices' => [
            ['name' => 'Київ', 'street' => 'вул. Зразкова, 12, офіс 304', 'postal_code' => '01001', 'region' => null],
            ['name' => 'Львів', 'street' => 'вул. Прикладна, 7', 'postal_code' => '79000', 'region' => 'Львівська область'],
        ],
        'contact' => ['name' => 'Олена Коваль', 'email' => 'hr', 'phone' => '+380 44 000 00 42'],
        // Gross. A month is how salaries are stated in Ukraine (IT offers are
        // often quoted in US dollars, the demo keeps the local currency);
        // interns get a monthly stipend.
        'salaries' => [
            'backend' => ['from' => 100000, 'to' => 130000, 'period' => 'month'],
            'designer' => ['from' => 70000, 'to' => 95000, 'period' => 'month'],
            'account' => null,
            'intern' => ['from' => 15000, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => 'Андрій Мельник', 'email' => 'andrii.melnyk'],
            ['name' => 'Оксана Бондар', 'email' => 'o.bondar'],
            ['name' => 'Юлія Шевчук', 'email' => 'yulia.shevchuk'],
            ['name' => 'Тарас Кравець', 'email' => 'taras.kravets'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Розробка',
            'design' => 'Дизайн',
            'sales' => 'Продажі',
            'support' => 'Підтримка клієнтів',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Backend-розробник (PHP, Laravel)',
                'code' => 'backend-rozrobnyk',
                'preview_text' => 'Створюйте сервіси, на яких тримається наш продукт, у невеликій команді з щотижневими релізами.',
                'description' => '<p>У команді вісім розробників. Ви вестимете задачі повністю — від бази даних до API.</p>',
                'duties' => '<ul><li>Проєктувати й розробляти REST API</li><li>Робити код-рев’ю колегам</li><li>Стежити, щоб тести лишалися зеленими</li></ul>',
                'requirements' => '<ul><li>Досвід розробки на PHP від 3 років</li><li>Laravel, SQL, Git</li><li>Грамотна письмова мова</li></ul>',
                'conditions' => '<ul><li>Два дні на тиждень в офісі</li><li>Медичне страхування</li><li>Щорічний бюджет на навчання</li></ul>',
            ],
            'designer' => [
                'name' => 'Продуктовий дизайнер',
                'code' => 'produktovyi-dyzainer',
                'preview_text' => 'Визначайте, як клієнти користуються нашим застосунком, — від першого ескізу до релізу.',
                'description' => '<p>У команді два дизайнери, ви тісно працюватимете з продакт-менеджерами та розробниками.</p>',
                'duties' => '<ul><li>Проводити інтерв’ю з користувачами й тестувати прототипи</li><li>Підтримувати лад у дизайн-системі</li></ul>',
                'requirements' => '<ul><li>Портфоліо із запущеними продуктами</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Віддалена робота з будь-якого міста країни</li><li>Ноутбук і монітор на ваш вибір</li></ul>',
            ],
            'account' => [
                'name' => 'Акаунт-менеджер',
                'code' => 'akaunt-menedzher',
                'preview_text' => 'Супроводжуйте наших корпоративних клієнтів і допомагайте їм отримувати від продукту більше.',
                'description' => '<p>У вас буде близько тридцяти власних клієнтів і команда, яка завжди підтримає.</p>',
                'duties' => '<ul><li>Підключати нових клієнтів</li><li>Зустрічатися з кожним клієнтом щонайменше раз на квартал</li></ul>',
                'requirements' => '<ul><li>Досвід у продажах або клієнтському сервісі від року</li><li>Уміння писати зрозуміло й доброзичливо</li></ul>',
                'conditions' => '<ul><li>Бонус за продовження договорів</li><li>Тихий офіс поруч із громадським транспортом</li></ul>',
            ],
            'intern' => [
                'name' => 'Стажист служби підтримки',
                'code' => 'stazhyst-pidtrymky',
                'preview_text' => 'Три місяці, двадцять годин на тиждень, із наставником — добрий старт в IT.',
                'description' => '<p>Ви відповідатимете на запитання клієнтів поштою та в чаті й розберетеся, як продукт влаштований зсередини.</p>',
                'duties' => '<ul><li>Відповідати клієнтам поштою та в чаті</li><li>Записувати запитання, які ставлять найчастіше</li></ul>',
                'requirements' => '<ul><li>Запрошуємо студентів</li><li>Терплячість і бажання допомагати</li></ul>',
                'conditions' => '<ul><li>Гнучкий графік, зручний для навчання</li><li>Найкращим стажистам — пропозиція роботи</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Запрошення на співбесіду',
                'subject' => 'Ваш відгук на вакансію «:vacancy»',
                'body' => "Добрий день, :name!\n\nДякуємо за відгук на вакансію «:vacancy». Ми хотіли б із вами познайомитися: напишіть, будь ласка, два-три зручні для вас часи наступного тижня.\n\nЗ повагою,\n:company",
            ],
            'rejection' => [
                'name' => 'Відмова',
                'subject' => 'Ваш відгук на вакансію «:vacancy»',
                'body' => "Добрий день, :name!\n\nДякуємо за інтерес до вакансії «:vacancy». Цього разу ми вирішили продовжити з іншими кандидатами. Ми збережемо ваше резюме й зв’яжемося з вами, якщо відкриється відповідна позиція.\n\nЗ повагою,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Вітаю! Маю п’ять років досвіду з Laravel і дуже хочу до вашої команди.',
            'Шість років працюю з PHP і PostgreSQL, здебільшого над API.',
            'Добрий день! Посилання на портфоліо — у резюме.',
            'Відповідної вакансії поки немає, але дуже хочу працювати у вас QA-інженером.',
        ],
    ],
];
