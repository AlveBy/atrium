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
        'block' => 'Blok qo‘shish',
    ],
    'fields' => [
        'name' => 'Nomi',
        'content' => 'Mazmuni',
        'updated' => 'O‘zgartirilgan',
        'code_hint' => 'Qisqa, lotin harflarida. Shablon blokni @block(\'kod\') orqali, matn muharrirdagi + tugmasi orqali ko‘rsatadi.',
    ],
    'embed' => 'Blok',
    'builder' => [
        'label' => 'Qayta ishlatiladigan blok',
    ],
];
