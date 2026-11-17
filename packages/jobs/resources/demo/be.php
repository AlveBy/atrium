<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Belarusian: a made-up company in Belarus.
 *
 * `country` is used when Belarusian is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in Belarusian, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'BY',
        'currency' => 'BYN',
        'domain' => 'example.by',
        'offices' => [
            ['name' => 'Мінск', 'street' => 'вул. Прыкладная, 12, оф. 304', 'postal_code' => '220030', 'region' => null],
            ['name' => 'Гродна', 'street' => 'вул. Узорная, 7', 'postal_code' => '230025', 'region' => 'Гродзенская вобласць'],
        ],
        'contact' => ['name' => 'Алеся Карповіч', 'email' => 'hr', 'phone' => '+375 17 000-00-42'],
        // Gross. A month is how salaries are stated in Belarus; interns get a
        // monthly stipend.
        'salaries' => [
            'backend' => ['from' => 6000, 'to' => 8000, 'period' => 'month'],
            'designer' => ['from' => 4500, 'to' => 6000, 'period' => 'month'],
            'account' => null,
            'intern' => ['from' => 1200, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => 'Павел Жук', 'email' => 'pavel.zhuk'],
            ['name' => 'Наталля Васілевіч', 'email' => 'n.vasilevich'],
            ['name' => 'Ганна Лукашэвіч', 'email' => 'hanna.lukashevich'],
            ['name' => 'Максім Дашкевіч', 'email' => 'maksim.dashkevich'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Распрацоўка',
            'design' => 'Дызайн',
            'sales' => 'Продажы',
            'support' => 'Падтрымка кліентаў',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Backend-распрацоўшчык (PHP, Laravel)',
                'code' => 'backend-raspratsoushchyk',
                'preview_text' => 'Стварайце сэрвісы, на якіх трымаецца наш прадукт, у невялікай камандзе з штотыднёвымі рэлізамі.',
                'description' => '<p>У камандзе восем распрацоўшчыкаў. Вы будзеце весці задачы цалкам — ад базы даных да API.</p>',
                'duties' => '<ul><li>Праектаваць і распрацоўваць REST API</li><li>Рабіць код-рэв’ю калегам</li><li>Сачыць, каб тэсты заставаліся зялёнымі</li></ul>',
                'requirements' => '<ul><li>Досвед распрацоўкі на PHP ад 3 гадоў</li><li>Laravel, SQL, Git</li><li>Уменне граматна пісаць</li></ul>',
                'conditions' => '<ul><li>Два дні на тыдзень у офісе</li><li>Медыцынскае страхаванне</li><li>Штогадовы бюджэт на навучанне</li></ul>',
            ],
            'designer' => [
                'name' => 'Прадуктовы дызайнер',
                'code' => 'praduktovy-dyzainer',
                'preview_text' => 'Вызначайце, як кліенты карыстаюцца нашай праграмай, — ад першага накіду да рэлізу.',
                'description' => '<p>У камандзе два дызайнеры, вы будзеце цесна працаваць з прадакт-менеджарамі і распрацоўшчыкамі.</p>',
                'duties' => '<ul><li>Праводзіць інтэрв’ю з карыстальнікамі і тэставаць прататыпы</li><li>Падтрымліваць парадак у дызайн-сістэме</li></ul>',
                'requirements' => '<ul><li>Партфоліа з запушчанымі прадуктамі</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Аддаленая праца з любога горада краіны</li><li>Ноўтбук і манітор на ваш выбар</li></ul>',
            ],
            'account' => [
                'name' => 'Акаўнт-менеджар',
                'code' => 'akaunt-menedzhar',
                'preview_text' => 'Суправаджайце нашых карпаратыўных кліентаў і дапамагайце ім атрымліваць ад прадукту больш.',
                'description' => '<p>У вас будзе каля трыццаці ўласных кліентаў і каманда, якая заўсёды падтрымае.</p>',
                'duties' => '<ul><li>Падключаць новых кліентаў</li><li>Сустракацца з кожным кліентам хаця б раз на квартал</li></ul>',
                'requirements' => '<ul><li>Досвед у продажах або кліенцкім сэрвісе ад году</li><li>Уменне пісаць зразумела і прыязна</li></ul>',
                'conditions' => '<ul><li>Бонус за падаўжэнне дагавораў</li><li>Ціхі офіс побач з грамадскім транспартам</li></ul>',
            ],
            'intern' => [
                'name' => 'Стажор службы падтрымкі',
                'code' => 'stazhor-padtrymki',
                'preview_text' => 'Тры месяцы, дваццаць гадзін на тыдзень, з настаўнікам — добры старт у IT.',
                'description' => '<p>Вы будзеце адказваць на пытанні кліентаў па пошце і ў чаце і разбярэцеся, як прадукт уладкаваны знутры.</p>',
                'duties' => '<ul><li>Адказваць кліентам па пошце і ў чаце</li><li>Запісваць пытанні, якія задаюць найчасцей</li></ul>',
                'requirements' => '<ul><li>Запрашаем студэнтаў</li><li>Цярплівасць і жаданне дапамагаць</li></ul>',
                'conditions' => '<ul><li>Гнуткі графік, зручны для вучобы</li><li>Лепшым стажорам — прапанова працы</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Запрашэнне на сумоўе',
                'subject' => 'Ваш водгук на вакансію «:vacancy»',
                'body' => "Добры дзень, :name!\n\nДзякуй за водгук на вакансію «:vacancy». Мы хацелі б з вамі пазнаёміцца: напішыце, калі ласка, два-тры зручныя для вас часы на наступным тыдні.\n\nЗ павагай,\n:company",
            ],
            'rejection' => [
                'name' => 'Адмова',
                'subject' => 'Ваш водгук на вакансію «:vacancy»',
                'body' => "Добры дзень, :name!\n\nДзякуй за цікавасць да вакансіі «:vacancy». Гэтым разам мы вырашылі працягнуць з іншымі кандыдатамі. Мы захаваем ваша рэзюмэ і звяжамся з вамі, калі адкрыецца адпаведная пазіцыя.\n\nЗ павагай,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Вітаю! У мяне пяць гадоў досведу з Laravel, вельмі хачу ў вашу каманду.',
            'Шэсць гадоў працую з PHP і PostgreSQL, у асноўным над API.',
            'Добры дзень! Спасылка на партфоліа — у рэзюмэ.',
            'Падыходнай вакансіі пакуль няма, але вельмі хачу працаваць у вас QA-інжынерам.',
        ],
    ],
];
