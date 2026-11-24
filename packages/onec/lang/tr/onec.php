<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => '1C değişimi',

    'settings' => [
        'description' => 'Katalog, standart CommerceML değişimiyle 1C:Enterprise\'tan gelir. 1C\'de siteyle değişim ayarında şu adresi girin: :url (yalnızca siteyi soran bir yapılandırma, site adresinden sonra :bitrix kullanır), altına da burada belirlediğiniz kullanıcı adını ve parolayı yazın.',
        'login' => 'Kullanıcı adı',
        'login_hint' => '1C\'nin giriş yaptığı ad. Kullanıcı adı ve parola ikisi de belirlenene kadar değişim kapalıdır.',
        'password' => 'Parola',
        'password_hint' => 'Şifreli saklanır ve bir daha gösterilmez: değiştirmek için yenisini yazın; mevcut olanı korumak için alanı boş bırakın.',
    ],

    'fields' => [
        'started_at' => 'Başlangıç',
        'finished_at' => 'Bitiş',
        'file' => 'Dosya',
        'status_label' => 'Sonuç',
        'sections_created' => 'Yeni bölümler',
        'sections_updated' => 'Güncellenen bölümler',
        'products_created' => 'Yeni ürünler',
        'products_updated' => 'Güncellenen ürünler',
        'offers_updated' => 'Güncellenen fiyat ve stoklar',
        'skipped' => 'Atlanan teklifler (böyle bir ürün yok)',
        'errors' => 'Hatalar',
        'error' => 'Ne ters gitti',
    ],

    'status' => [
        'running' => 'Sürüyor',
        'done' => 'Tamamlandı',
        'failed' => 'Başarısız',
    ],

    'reply' => [
        'wrong_login' => 'Kullanıcı adı veya parola yanlış ya da değişim Modüller → 1C bölümünde ayarlanmamış.',
        'denied' => 'Giriş yapılmadı: değişimi yeniden başlatın.',
        'unknown_type' => 'Bilinmeyen değişim türü: :type.',
        'unknown_mode' => 'Bilinmeyen değişim adımı: :mode.',
        'bad_name' => 'Bu dosya adına izin verilmiyor.',
        'no_file' => ':file dosyası gelmedi.',
        'progress' => 'İçe aktarılan öğe: :count.',
        'summary' => 'Bölümler: :sections_created yeni, :sections_updated güncellendi. Ürünler: :products_created yeni, :products_updated güncellendi. Fiyat ve stok: :offers. Atlanan: :skipped. Hata: :errors.',
        'no_orders' => 'Siparişler aktarılmaz: mağaza modülü yüklü değil.',
    ],
];
