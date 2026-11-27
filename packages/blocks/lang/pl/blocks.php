<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'blocks' => 'Bloki',
    ],
    'create' => [
        'block' => 'Dodaj blok',
    ],
    'fields' => [
        'name' => 'Nazwa',
        'content' => 'Treść',
        'updated' => 'Zmieniono',
        'code_hint' => 'Krótko, łacińskimi literami. Szablon pokazuje blok przez @block(\'kod\'), tekst — przyciskiem + w edytorze.',
    ],
    'embed' => 'Blok',
    'builder' => [
        'label' => 'Blok wielokrotnego użytku',
    ],
];
