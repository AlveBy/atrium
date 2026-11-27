<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'blocks' => 'Bloklar',
    ],
    'create' => [
        'block' => 'Blok ekle',
    ],
    'fields' => [
        'name' => 'Ad',
        'content' => 'İçerik',
        'updated' => 'Değiştirildi',
        'code_hint' => 'Kısa, Latin harfleriyle. Şablon bloğu @block(\'kod\') ile, metin ise düzenleyicideki + düğmesiyle gösterir.',
    ],
    'embed' => 'Blok',
    'builder' => [
        'label' => 'Yeniden kullanılabilir blok',
    ],
];
