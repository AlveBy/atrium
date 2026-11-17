<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Kazakh: a made-up company in Kazakhstan.
 *
 * `country` is used when Kazakh is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in Kazakh, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'KZ',
        'currency' => 'KZT',
        'domain' => 'example.kz',
        'offices' => [
            ['name' => 'Алматы', 'street' => 'Үлгі көшесі, 12, 304-кеңсе', 'postal_code' => 'A05C9Y0', 'region' => null],
            ['name' => 'Астана', 'street' => 'Мысал даңғылы, 7', 'postal_code' => 'Z05T3E2', 'region' => null],
        ],
        'contact' => ['name' => 'Айгерім Сейітова', 'email' => 'hr', 'phone' => '+7 727 000 00 42'],
        // Gross. A month is how salaries are stated in Kazakhstan; interns get
        // a monthly stipend.
        'salaries' => [
            'backend' => ['from' => 900000, 'to' => 1200000, 'period' => 'month'],
            'designer' => ['from' => 650000, 'to' => 850000, 'period' => 'month'],
            'account' => null,
            'intern' => ['from' => 200000, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => 'Нұрлан Әбенов', 'email' => 'nurlan.abenov'],
            ['name' => 'Динара Жұмабаева', 'email' => 'd.zhumabayeva'],
            ['name' => 'Әсем Тоқтарова', 'email' => 'asem.toktarova'],
            ['name' => 'Ерлан Сәрсенов', 'email' => 'erlan.sarsenov'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Әзірлеу',
            'design' => 'Дизайн',
            'sales' => 'Сату',
            'support' => 'Клиенттерді қолдау',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Backend-әзірлеуші (PHP, Laravel)',
                'code' => 'backend-azirleushi',
                'preview_text' => 'Өніміміздің негізіндегі сервистерді апта сайын релиз шығаратын шағын командада жасаңыз.',
                'description' => '<p>Командамызда сегіз әзірлеуші бар. Сіз міндеттерді деректер базасынан API-ге дейін толық жүргізесіз.</p>',
                'duties' => '<ul><li>REST API жобалау және әзірлеу</li><li>Әріптестердің кодын тексеру</li><li>Тесттердің үнемі жасыл болуын қадағалау</li></ul>',
                'requirements' => '<ul><li>PHP-де кемінде 3 жыл тәжірибе</li><li>Laravel, SQL, Git</li><li>Сауатты жаза білу</li></ul>',
                'conditions' => '<ul><li>Аптасына екі күн кеңседе</li><li>Медициналық сақтандыру</li><li>Жыл сайынғы оқу бюджеті</li></ul>',
            ],
            'designer' => [
                'name' => 'Өнім дизайнері',
                'code' => 'onim-dizaineri',
                'preview_text' => 'Клиенттердің қосымшамызды қалай қолданатынын алғашқы эскизден релизге дейін өзіңіз айқындаңыз.',
                'description' => '<p>Командада екі дизайнер бар, сіз өнім менеджерлерімен және әзірлеушілермен тығыз жұмыс істейсіз.</p>',
                'duties' => '<ul><li>Пайдаланушылармен сұхбат жүргізу және прототиптерді тестілеу</li><li>Дизайн-жүйені ретке келтіріп отыру</li></ul>',
                'requirements' => '<ul><li>Іске қосылған өнімдері бар портфолио</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Елдің кез келген қаласынан қашықтан жұмыс</li><li>Өзіңіз таңдаған ноутбук пен монитор</li></ul>',
            ],
            'account' => [
                'name' => 'Аккаунт-менеджер',
                'code' => 'akkaunt-menedzher',
                'preview_text' => 'Корпоративтік клиенттерімізді сүйемелдеп, олардың өнімнен көбірек пайда алуына көмектесіңіз.',
                'description' => '<p>Сізде отызға жуық өз клиентіңіз және әрдайым көмектесетін команда болады.</p>',
                'duties' => '<ul><li>Жаңа клиенттерді қосу</li><li>Әр клиентпен тоқсанына кемінде бір рет кездесу</li></ul>',
                'requirements' => '<ul><li>Сату немесе клиенттік сервис саласында бір жылдан астам тәжірибе</li><li>Түсінікті әрі жылы жаза білу</li></ul>',
                'conditions' => '<ul><li>Келісімшарт ұзартылғаны үшін бонус</li><li>Қоғамдық көлікке жақын тыныш кеңсе</li></ul>',
            ],
            'intern' => [
                'name' => 'Қолдау қызметінің тағылымдамашысы',
                'code' => 'qoldau-tagylymdamashy',
                'preview_text' => 'Үш ай, аптасына жиырма сағат, тәлімгермен бірге — IT саласына жақсы бастама.',
                'description' => '<p>Сіз клиенттердің сұрақтарына пошта мен чат арқылы жауап беріп, өнімнің ішкі құрылысымен танысасыз.</p>',
                'duties' => '<ul><li>Клиенттерге пошта мен чат арқылы жауап беру</li><li>Жиі қойылатын сұрақтарды жазып отыру</li></ul>',
                'requirements' => '<ul><li>Студенттерді шақырамыз</li><li>Шыдамдылық және көмектесуге ниет</li></ul>',
                'conditions' => '<ul><li>Оқуға ыңғайлы икемді кесте</li><li>Үздік тағылымдамашыларға жұмыс ұсынысы</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Сұхбатқа шақыру',
                'subject' => '«:vacancy» вакансиясына өтінішіңіз',
                'body' => "Сәлеметсіз бе, :name!\n\n«:vacancy» вакансиясына өтініш бергеніңіз үшін рахмет. Сізбен танысқымыз келеді: келесі аптада өзіңізге ыңғайлы екі-үш уақытты жазып жіберіңізші.\n\nҚұрметпен,\n:company",
            ],
            'rejection' => [
                'name' => 'Бас тарту',
                'subject' => '«:vacancy» вакансиясына өтінішіңіз',
                'body' => "Сәлеметсіз бе, :name!\n\n«:vacancy» вакансиясына қызығушылық танытқаныңыз үшін рахмет. Бұл жолы басқа үміткерлермен жалғастыруды шештік. Түйіндемеңізді сақтап қоямыз және лайықты орын ашылса, сізбен хабарласамыз.\n\nҚұрметпен,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Сәлеметсіз бе! Laravel-мен бес жылдық тәжірибем бар, командаңызға қосылғым келеді.',
            'Алты жылдан бері PHP және PostgreSQL-мен, негізінен API бойынша жұмыс істеймін.',
            'Қайырлы күн! Портфолиоға сілтеме түйіндемеде.',
            'Әзірге лайықты вакансия жоқ, бірақ сіздерде QA инженері болып жұмыс істегім келеді.',
        ],
    ],
];
