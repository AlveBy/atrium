<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Entegrasyonlar',
    'title_log' => 'Değişim günlüğü',

    'status' => [
        'ok' => 'Tamamlandı',
        'retry' => 'Başarısız, yeniden denenecek',
        'failed' => 'Başarısız',
    ],
    'direction' => [
        'out' => 'Siteden',
        'in' => 'Siteye',
    ],
    'fields' => [
        'when' => 'Ne zaman',
        'integration' => 'Entegrasyon',
        'direction' => 'Yön',
        'action' => 'İşlem',
        'subject' => 'Konu',
        'status' => 'Sonuç',
        'message' => 'Yapılan işlem',
        'error' => 'Hata',
        'attempt' => 'Deneme',
        'duration' => 'Süre, ms',
    ],

    'settings' => [
        'description' => 'Bir bağlayıcı (1C, Bitrix24) son denemesinden sonra vazgeçtiğinde kimin bilgilendirileceği ve değişim günlüğünün ne kadar saklanacağı.',
        'alert_emails' => 'Uyarı e-postaları',
        'alert_emails_hint' => 'Bir veya daha fazla adres; virgülle veya yeni satırla ayırın. Boşsa: tüm etkin süper yöneticiler. Entegrasyon başına saatte en fazla bir e-posta.',
        'retention_days' => 'Günlüğü saklama süresi, gün',
        'retention_days_hint' => 'Daha eski kayıtlar her gece silinir.',
    ],

    'alert' => [
        'subject' => ':site: :integration entegrasyonu başarısız oldu',
        'what' => ':integration entegrasyonu :action işlemini yapamadı ve denemeyi bıraktı.',
        'subject_line' => 'Konu: :subject',
        'error' => 'Hata: :error',
        'attempts' => 'Yapılan deneme: :count',
    ],
];
