<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => 'Bitrix24: formlar',

    'settings' => [
        'description' => 'Formlardan gelen başvurular Bitrix24\'ünüze lead olarak düşer ve Bitrix24 sohbet widget\'ı sitede gösterilir. Bitrix24\'te CRM yetkisi olan bir gelen webhook oluşturun (Geliştirici kaynakları → Diğer → Gelen webhook) ve adresini buraya yapıştırın.',
        'webhook' => 'Gelen webhook',
        'webhook_hint' => 'Bitrix24\'ün gösterdiği adres, şuna benzer: https://portal.bitrix24.com/rest/1/code/. Şifrelenmiş olarak saklanır ve bir daha gösterilmez: değiştirmek için yenisini yapıştırın; mevcut olanı korumak için alanı boş bırakın.',
        'send_forms' => 'Form başvurularını Bitrix24\'e gönder',
        'send_forms_hint' => 'Aşağıda bağlantısı olmayan form lead olarak gider, alanları türüne göre yerleştirilir.',
        'widget_code' => 'Sohbet widget\'ı kodu',
        'widget_code_hint' => 'Bitrix24\'teki açık hattın veya sohbet widget\'ının kodu (CRM → İletişim merkezi → Çevrimiçi sohbet → Widget). Sitenin her sayfasının sonuna eklenir; boş bırakılırsa widget gösterilmez.',
        'check' => 'Bağlantıyı kontrol et',
        'connected' => 'Bağlandı: :portal, kullanıcı :user.',
    ],

    'fields' => [
        'form' => 'Form',
        'mode' => 'Şu olarak gönder',
        'active' => 'Bu formu Bitrix24\'e gönder',
        'mapping' => 'Alanlar',
        'mapping_hint' => 'Her alan için bir satır: "form alanı = Bitrix24 alanı", örneğin "phone = PHONE" veya "message = COMMENTS". Satırı olmayan alan türüne göre yerleşir (e-posta EMAIL\'e, telefon PHONE\'a); yeri olmayan her şey yoruma eklenir. # ile başlayan satırlar yok sayılır.',
    ],

    'modes' => [
        'lead' => 'Lead',
        'deal' => 'Kişili anlaşma',
    ],

    'errors' => [
        'no_webhook' => 'Bitrix24 webhook\'u ayarlanmamış: Modüller → Bitrix24 bölümüne yapıştırın.',
        'unreachable' => 'Bitrix24 yanıt vermiyor. Webhook\'taki portal adresini kontrol edin.',
        'portal' => 'Bitrix24 reddetti: :error',
        'mapping_line' => ':line. satır "form alanı = Bitrix24 alanı" biçiminde değil.',
    ],

    'payload' => [
        'subject' => 'Form başvurusu no. :id',
        'title' => 'Web sitesi: :form',
        'visitor' => 'Site ziyaretçisi',
        'yes' => 'Evet',
        'lead' => 'Lead no. :id oluşturuldu.',
        'deal' => 'Anlaşma no. :deal oluşturuldu, kişi no. :contact.',
        'gone' => 'Başvuru gönderilmeden önce silindi.',
    ],
];
