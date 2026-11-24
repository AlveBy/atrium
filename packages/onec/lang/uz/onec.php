<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => '1C almashinuvi',

    'settings' => [
        'description' => 'Katalog 1C:Korxona\'dan standart CommerceML almashinuvi orqali keladi. 1C\'da saytga almashinuv sozlamalarida mana bu manzilni kiriting: :url (faqat saytni so\'raydigan konfiguratsiya sayt manzilidan keyin :bitrix oladi), pastda esa shu yerda belgilagan login va parolni kiriting.',
        'login' => 'Login',
        'login_hint' => '1C qaysi nom bilan kiradi. Login va parol ikkalasi belgilanmaguncha almashinuv yopiq.',
        'password' => 'Parol',
        'password_hint' => 'Shifrlangan holda saqlanadi va boshqa ko\'rsatilmaydi: almashtirish uchun yangisini kiriting; joriysini saqlash uchun maydonni bo\'sh qoldiring.',
    ],

    'fields' => [
        'started_at' => 'Boshlandi',
        'finished_at' => 'Tugadi',
        'file' => 'Fayl',
        'status_label' => 'Natija',
        'sections_created' => 'Yangi bo\'limlar',
        'sections_updated' => 'Bo\'limlar yangilandi',
        'products_created' => 'Yangi tovarlar',
        'products_updated' => 'Tovarlar yangilandi',
        'offers_updated' => 'Narxlar va qoldiqlar yangilandi',
        'skipped' => 'O\'tkazib yuborilgan takliflar (bunday tovar yo\'q)',
        'errors' => 'Xatolar',
        'error' => 'Nima xato ketdi',
    ],

    'status' => [
        'running' => 'Bajarilmoqda',
        'done' => 'Tayyor',
        'failed' => 'Muvaffaqiyatsiz',
    ],

    'reply' => [
        'wrong_login' => 'Login yoki parol noto\'g\'ri, yoki almashinuv Modullar → 1C bo\'limida sozlanmagan.',
        'denied' => 'Kirilmagan: almashinuvni qaytadan boshlang.',
        'unknown_type' => 'Almashinuv turi noma\'lum: :type.',
        'unknown_mode' => 'Almashinuv qadami noma\'lum: :mode.',
        'bad_name' => 'Bunday fayl nomiga ruxsat yo\'q.',
        'no_file' => ':file fayli kelmadi.',
        'progress' => 'Yuklangan pozitsiyalar: :count.',
        'summary' => 'Bo\'limlar: :sections_created yangi, :sections_updated yangilandi. Tovarlar: :products_created yangi, :products_updated yangilandi. Narxlar va qoldiqlar: :offers. O\'tkazib yuborildi: :skipped. Xatolar: :errors.',
        'no_orders' => 'Buyurtmalar almashilmaydi: do\'kon moduli o\'rnatilmagan.',
    ],
];
