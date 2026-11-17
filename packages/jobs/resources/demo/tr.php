<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
 * The jobs demo for Turkish: a made-up company in Turkey.
 *
 * `country` is used when Turkish is the main language of the install: the
 * offices, the people, the currency and the usual way to state a salary
 * there. `texts` are the vacancies in Turkish, used whatever the main
 * country is, so they name no city, currency or law. Everything is
 * fictional; e-mail addresses are the part before the @, the domain is
 * `domain`.
 */
return [
    'country' => [
        'code' => 'TR',
        'currency' => 'TRY',
        'domain' => 'example.com.tr',
        'offices' => [
            ['name' => 'İstanbul', 'street' => 'Örnek Mah. Deneme Cad. No: 12/3', 'postal_code' => '34394', 'region' => null],
            ['name' => 'Ankara', 'street' => 'Numune Sok. No: 7', 'postal_code' => '06510', 'region' => null],
        ],
        'contact' => ['name' => 'Elif Yılmaz', 'email' => 'kariyer', 'phone' => '+90 212 000 01 42'],
        // Gross (brüt). A month is how salaries are stated in Turkey; interns
        // get a monthly stipend.
        'salaries' => [
            'backend' => ['from' => 110000, 'to' => 150000, 'period' => 'month'],
            'designer' => ['from' => 85000, 'to' => 110000, 'period' => 'month'],
            'account' => null,
            'intern' => ['from' => 20000, 'period' => 'month'],
        ],
        'applicants' => [
            ['name' => 'Emre Kaya', 'email' => 'emre.kaya'],
            ['name' => 'Zeynep Demir', 'email' => 'z.demir'],
            ['name' => 'Ayşe Çelik', 'email' => 'ayse.celik'],
            ['name' => 'Burak Arslan', 'email' => 'burak.arslan'],
        ],
    ],

    'texts' => [
        'departments' => [
            'engineering' => 'Yazılım Geliştirme',
            'design' => 'Tasarım',
            'sales' => 'Satış',
            'support' => 'Müşteri Destek',
        ],
        'vacancies' => [
            'backend' => [
                'name' => 'Backend Geliştirici (PHP, Laravel)',
                'code' => 'backend-gelistirici',
                'preview_text' => 'Ürünümüzün arkasındaki servisleri, her hafta yayına çıkan küçük bir ekiple birlikte geliştirin.',
                'description' => '<p>Sekiz kişilik bir geliştirici ekibiyiz. Özellikleri veritabanından API\'ye kadar uçtan uca siz üstleneceksiniz.</p>',
                'duties' => '<ul><li>REST API\'ler tasarlamak ve geliştirmek</li><li>Ekip arkadaşlarının kodunu incelemek</li><li>Testlerin her zaman geçmesini sağlamak</li></ul>',
                'requirements' => '<ul><li>En az 3 yıl PHP deneyimi</li><li>Laravel, SQL, Git</li><li>Güçlü yazılı iletişim</li></ul>',
                'conditions' => '<ul><li>Haftada iki gün ofiste</li><li>Özel sağlık sigortası</li><li>Yıllık eğitim bütçesi</li></ul>',
            ],
            'designer' => [
                'name' => 'Ürün Tasarımcısı',
                'code' => 'urun-tasarimcisi',
                'preview_text' => 'Müşterilerin uygulamamızı nasıl kullandığını ilk eskizden yayına kadar siz şekillendirin.',
                'description' => '<p>İki kişilik tasarım ekibinin bir parçası olacak, ürün yöneticileri ve geliştiricilerle yakın çalışacaksınız.</p>',
                'duties' => '<ul><li>Kullanıcılarla görüşmeler yapmak ve prototipleri test etmek</li><li>Tasarım sistemini düzenli tutmak</li></ul>',
                'requirements' => '<ul><li>Yayına çıkmış ürünler içeren bir portfolyo</li><li>Figma</li></ul>',
                'conditions' => '<ul><li>Ülkenin her yerinden uzaktan çalışma</li><li>Seçeceğiniz bir dizüstü bilgisayar ve monitör</li></ul>',
            ],
            'account' => [
                'name' => 'Kurumsal Müşteri Temsilcisi',
                'code' => 'musteri-temsilcisi',
                'preview_text' => 'Kurumsal müşterilerimizle ilgilenin ve üründen daha fazla verim almalarına yardımcı olun.',
                'description' => '<p>Yaklaşık otuz müşteriniz olacak ve arkanızda sizi destekleyen bir ekip bulunacak.</p>',
                'duties' => '<ul><li>Yeni müşterilerin ürünü kullanmaya başlamasını sağlamak</li><li>Her müşteriyle en az üç ayda bir görüşmek</li></ul>',
                'requirements' => '<ul><li>Satış veya müşteri ilişkileri alanında en az bir yıl deneyim</li><li>Açık ve samimi yazma becerisi</li></ul>',
                'conditions' => '<ul><li>Sözleşme yenilemelerinde prim</li><li>Toplu taşımaya yakın, sakin bir ofis</li></ul>',
            ],
            'intern' => [
                'name' => 'Müşteri Destek Stajyeri',
                'code' => 'musteri-destek-stajyeri',
                'preview_text' => 'Üç ay, haftada yirmi saat, bir mentor eşliğinde: bilişim dünyasına güzel bir başlangıç.',
                'description' => '<p>Müşterilerin sorularını e-posta ve canlı destek üzerinden yanıtlayacak, ürünün nasıl çalıştığını içeriden öğreneceksiniz.</p>',
                'duties' => '<ul><li>Müşterilere e-posta ve canlı destek üzerinden yanıt vermek</li><li>Sık sorulan soruları not etmek</li></ul>',
                'requirements' => '<ul><li>Öğrenciler başvurabilir</li><li>Sabır ve yardım etme isteği</li></ul>',
                'conditions' => '<ul><li>Derslerinize uygun esnek çalışma saatleri</li><li>Başarılı stajyerlere iş teklifi</li></ul>',
            ],
        ],
        'templates' => [
            'interview' => [
                'name' => 'Mülakat daveti',
                'subject' => ':vacancy pozisyonu için başvurunuz',
                'body' => "Merhaba :name,\n\n:vacancy pozisyonuna başvurduğunuz için teşekkür ederiz. Sizinle tanışmak isteriz: Önümüzdeki hafta size uygun iki ya da üç zaman aralığını bize iletebilir misiniz?\n\nSaygılarımızla,\n:company",
            ],
            'rejection' => [
                'name' => 'Olumsuz yanıt',
                'subject' => ':vacancy pozisyonu için başvurunuz',
                'body' => "Merhaba :name,\n\n:vacancy pozisyonuna gösterdiğiniz ilgi için teşekkür ederiz. Bu kez diğer adaylarla devam etmeye karar verdik. Özgeçmişinizi saklayacak, uygun bir pozisyon açıldığında sizinle iletişime geçeceğiz.\n\nSaygılarımızla,\n:company",
            ],
        ],
        // The applicants' cover letters, in the order of `applicants`: the
        // backend developer twice, the designer, and a CV without a vacancy.
        'messages' => [
            'Merhaba! Beş yıllık Laravel deneyimim var, ekibinize katılmayı çok isterim.',
            'Altı yıldır PHP ve PostgreSQL ile, çoğunlukla API\'ler üzerinde çalışıyorum.',
            'İyi günler! Portfolyomun bağlantısı özgeçmişimde.',
            'Şu an uygun bir ilan yok ama sizinle QA mühendisi olarak çalışmak isterim.',
        ],
    ],
];
