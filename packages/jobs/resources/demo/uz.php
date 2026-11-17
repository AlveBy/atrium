<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Uzbek: a made-up company in Uzbekistan.
 *
 * `country` is used when Uzbek is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in Uzbek (Latin script), used whatever
 * the main country is, so they name no city, currency or law. Everything
 * is fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'UZ',
        'currency' => 'UZS',
        'domain' => 'example.uz',
        'offices' => [
            ['name' => 'Toshkent', 'street' => 'Namuna koʻchasi, 12-uy, 304-xona', 'postal_code' => '100000', 'region' => null],
            ['name' => 'Samarqand', 'street' => 'Misol koʻchasi, 7-uy', 'postal_code' => '140100', 'region' => 'Samarqand viloyati'],
        ],
        'contact' => ['name' => 'Dilnoza Karimova', 'email' => 'hr', 'phone' => '+998 71 000-00-42'],
        // Gross. A month is how salaries are stated in Uzbekistan; interns get
        // a monthly stipend.
        'salaries' => [
            'backend' => ['from' => 20000000, 'to' => 28000000, 'period' => 'month'],
            'designer' => ['from' => 14000000, 'to' => 20000000, 'period' => 'month'],
            'account' => null,
            'intern' => ['from' => 4000000, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => 'Jasur Toshmatov', 'email' => 'jasur.toshmatov'],
            ['name' => 'Malika Yusupova', 'email' => 'm.yusupova'],
            ['name' => 'Nodira Rahimova', 'email' => 'nodira.rahimova'],
            ['name' => 'Sardor Alimov', 'email' => 'sardor.alimov'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Dasturlash',
            'design' => 'Dizayn',
            'sales' => 'Savdo',
            'support' => 'Mijozlarni qoʻllab-quvvatlash',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Backend dasturchi (PHP, Laravel)',
                'code' => 'backend-dasturchi',
                'preview_text' => 'Mahsulotimiz tayanadigan servislarni har hafta reliz chiqaradigan kichik jamoada yarating.',
                'description' => '<p>Jamoamizda sakkiz nafar dasturchi bor. Vazifalarni maʼlumotlar bazasidan API gacha toʻliq oʻzingiz olib borasiz.</p>',
                'duties' => '<ul><li>REST API loyihalash va ishlab chiqish</li><li>Hamkasblar kodini koʻrib chiqish</li><li>Testlar doim yashil boʻlishini kuzatib borish</li></ul>',
                'requirements' => '<ul><li>PHP boʻyicha kamida 3 yillik tajriba</li><li>Laravel, SQL, Git</li><li>Savodli yoza olish</li></ul>',
                'conditions' => '<ul><li>Haftada ikki kun ofisda</li><li>Tibbiy sugʻurta</li><li>Har yili oʻqish uchun byudjet</li></ul>',
            ],
            'designer' => [
                'name' => 'Mahsulot dizayneri',
                'code' => 'mahsulot-dizayneri',
                'preview_text' => 'Mijozlar ilovamizdan qanday foydalanishini birinchi eskizdan relizgacha belgilang.',
                'description' => '<p>Jamoada ikki dizayner bor, siz mahsulot menejerlari va dasturchilar bilan yaqin hamkorlikda ishlaysiz.</p>',
                'duties' => '<ul><li>Foydalanuvchilar bilan suhbat oʻtkazish va prototiplarni sinash</li><li>Dizayn tizimini tartibda saqlash</li></ul>',
                'requirements' => '<ul><li>Ishga tushirilgan mahsulotlar bilan portfolio</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Mamlakatning istalgan shahridan masofadan ishlash</li><li>Oʻzingiz tanlagan noutbuk va monitor</li></ul>',
            ],
            'account' => [
                'name' => 'Akkaunt-menejer',
                'code' => 'akkaunt-menejer',
                'preview_text' => 'Korporativ mijozlarimizga xizmat koʻrsating va ularga mahsulotdan koʻproq naf olishda yordam bering.',
                'description' => '<p>Sizda oʻttizga yaqin oʻz mijozingiz va har doim yordam beradigan jamoa boʻladi.</p>',
                'duties' => '<ul><li>Yangi mijozlarni ulash</li><li>Har bir mijoz bilan choragiga kamida bir marta uchrashish</li></ul>',
                'requirements' => '<ul><li>Savdo yoki mijozlarga xizmat koʻrsatishda bir yildan ortiq tajriba</li><li>Tushunarli va samimiy yoza olish</li></ul>',
                'conditions' => '<ul><li>Shartnomalar uzaytirilgani uchun bonus</li><li>Jamoat transporti yaqinidagi sokin ofis</li></ul>',
            ],
            'intern' => [
                'name' => 'Qoʻllab-quvvatlash xizmati stajyori',
                'code' => 'qollab-quvvatlash-stajyori',
                'preview_text' => 'Uch oy, haftasiga yigirma soat, murabbiy bilan — IT sohasida yaxshi boshlanish.',
                'description' => '<p>Mijozlarning savollariga pochta va chat orqali javob berasiz hamda mahsulot ichdan qanday ishlashini oʻrganasiz.</p>',
                'duties' => '<ul><li>Mijozlarga pochta va chat orqali javob berish</li><li>Koʻp beriladigan savollarni yozib borish</li></ul>',
                'requirements' => '<ul><li>Talabalar ham qabul qilinadi</li><li>Sabr-toqat va yordam berish istagi</li></ul>',
                'conditions' => '<ul><li>Oʻqishga moslashuvchan ish jadvali</li><li>Eng yaxshi stajyorlarga ish taklifi</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Suhbatga taklif',
                'subject' => '“:vacancy” vakansiyasiga arizangiz',
                'body' => "Assalomu alaykum, :name!\n\n“:vacancy” vakansiyasiga ariza berganingiz uchun rahmat. Siz bilan tanishmoqchimiz: iltimos, kelasi haftada sizga qulay boʻlgan ikki-uch vaqtni yozib yuboring.\n\nHurmat bilan,\n:company",
            ],
            'rejection' => [
                'name' => 'Rad javobi',
                'subject' => '“:vacancy” vakansiyasiga arizangiz',
                'body' => "Assalomu alaykum, :name!\n\n“:vacancy” vakansiyasiga qiziqish bildirganingiz uchun rahmat. Bu safar boshqa nomzodlar bilan davom etishga qaror qildik. Rezyumengizni saqlab qolamiz va mos lavozim ochilsa, siz bilan bogʻlanamiz.\n\nHurmat bilan,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Assalomu alaykum! Laravel bilan besh yillik tajribam bor, jamoangizga qoʻshilishni juda istardim.',
            'Olti yildan beri PHP va PostgreSQL bilan, asosan API ustida ishlayman.',
            'Xayrli kun! Portfolioga havola rezyumeda.',
            'Hozircha mos vakansiya yoʻq, lekin sizlarda QA muhandisi boʻlib ishlashni istardim.',
        ],
    ],
];
