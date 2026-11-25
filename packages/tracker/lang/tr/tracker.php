<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'İzleyici',
    'my_tasks' => 'Görevlerim',
    'hours_short' => 'sa',
    'thread_empty' => 'Henüz bir şey yok.',

    'projects' => [
        'title' => 'Projeler',
        'add' => 'Proje ekle',
        'fields' => [
            'name' => 'Ad',
            'key' => 'Anahtar',
            'key_hint' => 'Bir harfle başlayan iki ila on büyük Latin harfi veya rakam: görevler ATR-1, ATR-2 … diye numaralanır',
            'description' => 'Açıklama',
            'lead' => 'Yönetici',
            'members' => 'Üyeler',
            'members_hint' => 'Projede çalışan yöneticiler. Bir görev yalnızca onlara ve proje yöneticisine verilebilir; herkese izin vermek için boş bırakın.',
            'archived' => 'Arşivlendi',
            'archived_hint' => 'Arşivlenmiş projeye yeni görev girmez; görevleri kalır.',
        ],
    ],

    'tasks' => [
        'title' => 'Görevler',
        'add' => 'Görev ekle',
        'tabs' => [
            'params' => 'Görev',
            'files' => 'Dosyalar',
            'activity' => 'Yorumlar ve geçmiş',
        ],
        'fields' => [
            'code' => 'Görev',
            'project' => 'Proje',
            'type' => 'Tür',
            'title' => 'Başlık',
            'description' => 'Açıklama',
            'status' => 'Durum',
            'priority' => 'Öncelik',
            'resolution' => 'Çözüm',
            'assignee' => 'Atanan',
            'parent' => 'Üst görev',
            'parent_hint' => 'Aynı projedeki bir görevin alt görevi ya da bir epiğin görevi.',
            'labels' => 'Etiketler',
            'add_label' => 'Etiket ekle',
            'due_date' => 'Bitiş tarihi',
            'estimate' => 'Tahmin',
            'attachments' => 'Ekler',
            'new_comment' => 'Yeni yorum',
            'new_comment_hint' => 'Kaydettiğinizde göreve eklenir.',
            'thread' => 'Yorumlar ve geçmiş',
        ],
        'filters' => [
            'label' => 'Etiket',
            'overdue' => 'Gecikmiş',
            'mine' => 'Görevlerim',
        ],
    ],

    'comments' => [
        'title' => 'Görev yorumları',
        'fields' => [
            'body' => 'Yorum',
            'task' => 'Görev',
            'author' => 'Yazar',
            'created' => 'Yazıldığı zaman',
        ],
    ],

    'terms' => [
        'types' => 'Görev türleri',
        'priorities' => 'Öncelikler',
        'statuses' => 'Durumlar',
        'resolutions' => 'Çözümler',
        'fields' => [
            'code' => 'Kod',
            'code_hint' => 'Küçük harfler, rakamlar ve _. Görevler kodu saklar, bu yüzden sonradan değiştirilemez.',
            'name' => 'Ad',
            'name_hint' => 'Boşsa: yerleşik ad, her yöneticinin kendi dilinde.',
            'sort' => 'Sıra',
            'default' => 'Varsayılan',
            'default_hint' => 'Yeni görev bunu alır. İşaret yoksa ilkini.',
            'category' => 'Kategori',
            'symbol' => 'Simge',
            'symbol_hint' => 'Adın önünde gösterilir, böylece öncelik yalnızca renkle ayırt edilmez.',
            'top_level' => 'Üst görevsiz',
            'top_level_hint' => 'Bu türdeki görevler epik gibi en üstte durur.',
            'needs_parent' => 'Üst görev gerekir',
            'needs_parent_hint' => 'Bu türdeki görevler alt görev gibi her zaman başka bir görevin içindedir.',
        ],
        'categories' => [
            'todo' => 'Yapılacak',
            'active' => 'Sürüyor',
            'done' => 'Bitti',
        ],
    ],

    'types' => [
        'task' => 'Görev',
        'bug' => 'Hata',
        'story' => 'Hikâye',
        'epic' => 'Epik',
        'subtask' => 'Alt görev',
    ],

    'resolutions' => [
        'done' => 'Tamamlandı',
        'wont_do' => 'Yapılmayacak',
        'duplicate' => 'Yinelenen',
        'cannot_reproduce' => 'Yeniden üretilemiyor',
    ],

    'priorities' => [
        'lowest' => 'En düşük',
        'low' => 'Düşük',
        'medium' => 'Orta',
        'high' => 'Yüksek',
        'highest' => 'En yüksek',
    ],

    'statuses' => [
        'todo' => 'Yapılacak',
        'in_progress' => 'Sürüyor',
        'review' => 'İncelemede',
        'done' => 'Bitti',
    ],

    'kinds' => [
        'comment' => 'Yorum',
        'created' => 'Oluşturuldu',
        'status' => 'Durum',
        'priority' => 'Öncelik',
        'assignee' => 'Atanan',
    ],

    'events' => [
        'created' => 'Görev oluşturuldu.',
        'status' => 'Durum: :old → :new',
        'priority' => 'Öncelik: :old → :new',
        'assignee' => 'Atanan: :old → :new',
    ],

    'errors' => [
        'archived' => 'Proje arşivlendi: içine görev taşınamaz.',
        'not_in_project' => 'Bu yönetici projede çalışmıyor.',
        'type_has_no_parent' => 'Bu türdeki bir görevin üst görevi olmaz.',
        'needs_parent' => 'Bu türdeki bir görevin üst görevi gerekir.',
        'in_use' => 'Görevler bunu hâlâ kullanıyor: önce onları değiştirin.',
        'parent_other_project' => 'Üst görev aynı projeden olmalıdır.',
        'parent_loop' => 'Bir görev kendisinin ya da alt görevlerinden birinin çocuğu olamaz.',
    ],

    'settings' => [
        'description' => 'Görevlerle ilgili e-postalar.',
        'notify_assigned' => 'Atanana yaz',
        'notify_assigned_hint' => 'Bir görev bir yöneticiye verildiğinde e-posta.',
        'notify_status' => 'Yeni durum hakkında yaz',
        'notify_status_hint' => 'Atananın görevinin durumu değiştiğinde ona e-posta.',
        'disabled_plugins' => 'Kapatılan eklentiler',
        'disabled_plugins_hint' => 'İşaretlediğiniz eklentiler yok sayılır; yeni bir eklenti siz işaretleyene kadar açıktır.',
    ],

    'mail' => [
        'assigned_subject' => ':code size atandı: :title',
        'assigned_line' => ':code «:title» görevi size atandı.',
        'status_subject' => ':code artık «:status»',
        'status_line' => ':code «:title» görevinin durumu değişti: :old → :new.',
    ],
];
