<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'blocks' => 'Blöcke',
    ],
    'create' => [
        'block' => 'Block hinzufügen',
    ],
    'fields' => [
        'name' => 'Name',
        'content' => 'Inhalt',
        'updated' => 'Geändert',
        'code_hint' => 'Kurz, in lateinischen Buchstaben. Eine Vorlage zeigt den Block mit @block(\'code\'), ein Text mit der +-Taste des Editors.',
    ],
    'embed' => 'Block',
    'builder' => [
        'label' => 'Wiederverwendbarer Block',
    ],
];
