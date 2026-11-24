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

    'types' => [
        'task' => 'Görev',
        'bug' => 'Hata',
        'story' => 'Hikâye',
        'epic' => 'Epik',
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
        'epic_has_parent' => 'Bir epiğin üst görevi olmaz.',
        'parent_other_project' => 'Üst görev aynı projeden olmalıdır.',
        'parent_loop' => 'Bir görev kendisinin ya da alt görevlerinden birinin çocuğu olamaz.',
    ],

    'settings' => [
        'description' => 'Görev durumları ve bunlarla ilgili e-postalar.',
        'statuses' => 'Durumlar',
        'statuses_hint' => 'Sırayla: yeni görev ilkini alır, sonuncusu «bitti» demektir. Boşsa: :default. Burada bir durumu yeniden adlandırırsanız görevlerde eski ad kalır — görevlerde de değiştirin.',
        'add_status' => 'Durum ekle',
        'notify_assigned' => 'Atanana yaz',
        'notify_assigned_hint' => 'Bir görev bir yöneticiye verildiğinde e-posta.',
        'notify_status' => 'Yeni durum hakkında yaz',
        'notify_status_hint' => 'Atananın görevinin durumu değiştiğinde ona e-posta.',
    ],

    'mail' => [
        'assigned_subject' => ':code size atandı: :title',
        'assigned_line' => ':code «:title» görevi size atandı.',
        'status_subject' => ':code artık «:status»',
        'status_line' => ':code «:title» görevinin durumu değişti: :old → :new.',
    ],
];
