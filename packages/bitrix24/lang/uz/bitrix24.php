<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => 'Bitrix24: shakllar',

    'settings' => [
        'description' => 'Shakllardan kelgan arizalar Bitrix24 hisobingizga lid sifatida tushadi, Bitrix24 chat vidjeti esa saytda ko\'rsatiladi. Bitrix24\'da CRM huquqiga ega kiruvchi vebhuk yarating (Dasturchilar uchun → Boshqa → Kiruvchi vebhuk) va uning manzilini shu yerga joylang.',
        'webhook' => 'Kiruvchi vebhuk',
        'webhook_hint' => 'Bitrix24 ko\'rsatadigan manzil, masalan https://portal.bitrix24.com/rest/1/code/. U shifrlangan holda saqlanadi va boshqa ko\'rsatilmaydi: almashtirish uchun yangisini joylang; joriysini saqlash uchun maydonni bo\'sh qoldiring.',
        'send_forms' => 'Shakllardan kelgan arizalarni Bitrix24\'ga yuborish',
        'send_forms_hint' => 'Quyida bog\'lanmagan shakl lid sifatida yuboriladi, maydonlar turiga qarab joylashtiriladi.',
        'widget_code' => 'Chat vidjeti kodi',
        'widget_code_hint' => 'Bitrix24\'dagi ochiq liniya yoki chat vidjetining kodi (CRM → Kontakt-markaz → Onlayn-chat → Vidjet). U saytning har bir sahifasi oxiriga qo\'yiladi; maydon bo\'sh bo\'lsa, vidjet bo\'lmaydi.',
        'check' => 'Ulanishni tekshirish',
        'connected' => 'Ulandi: :portal, foydalanuvchi :user.',
    ],

    'fields' => [
        'form' => 'Shakl',
        'mode' => 'Qanday yuborish',
        'active' => 'Bu shaklni Bitrix24\'ga yuborish',
        'mapping' => 'Maydonlar',
        'mapping_hint' => 'Har bir maydon uchun bitta satr: "shakl maydoni = Bitrix24 maydoni", masalan "phone = PHONE" yoki "message = COMMENTS". Satri yo\'q maydon turiga qarab joylashadi (pochta EMAIL\'ga, telefon PHONE\'ga); joyi yo\'q narsa izohga tushadi. # bilan boshlanadigan satrlar e\'tiborga olinmaydi.',
    ],

    'modes' => [
        'lead' => 'Lid',
        'deal' => 'Kontaktli bitim',
    ],

    'errors' => [
        'no_webhook' => 'Bitrix24 vebhuki ko\'rsatilmagan: uni Modullar → Bitrix24 bo\'limiga joylang.',
        'unreachable' => 'Bitrix24 javob bermayapti. Vebhukdagi portal manzilini tekshiring.',
        'portal' => 'Bitrix24 rad etdi: :error',
        'mapping_line' => ':line-satr "shakl maydoni = Bitrix24 maydoni" ko\'rinishida emas.',
    ],

    'payload' => [
        'subject' => 'Shakldan kelgan ariza № :id',
        'title' => 'Sayt: :form',
        'visitor' => 'Sayt tashrifchisi',
        'yes' => 'Ha',
        'lead' => 'Lid № :id yaratildi.',
        'deal' => 'Bitim № :deal yaratildi, kontakt № :contact.',
        'gone' => 'Ariza yuborilishidan oldin o\'chirildi.',
    ],
];
