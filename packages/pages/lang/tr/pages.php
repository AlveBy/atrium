<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'module' => 'Sayfalar',
    'create' => 'Sayfa ekle',
    'root' => 'En üst düzey',
    'actions' => [
        'subpage' => 'Alt sayfa',
    ],
    'fields' => [
        'name' => 'Ad',
        'code' => 'Adres',
        'parent' => 'Üst sayfa',
        'template' => 'Şablon',
        'content' => 'Metin',
        'updated_at' => 'Güncellenme tarihi',
    ],
    'hints' => [
        'code' => 'Sayfa adresinin son kısmı. Aynı üst sayfanın altındaki sayfaların adresleri farklı olmalıdır.',
    ],
    'templates' => [
        'default' => 'Sayfa',
        'children' => 'Alt sayfalarıyla birlikte sayfa',
        'landing' => 'Tam genişlik, başlıksız',
    ],
    'errors' => [
        'code_taken' => 'Aynı üst sayfanın altında bu adrese sahip başka bir sayfa zaten var.',
    ],
];
