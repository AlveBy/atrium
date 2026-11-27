<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'blocks' => 'Blocks',
    ],
    'create' => [
        'block' => 'Add a block',
    ],
    'fields' => [
        'name' => 'Name',
        'content' => 'Content',
        'updated' => 'Changed',
        'code_hint' => 'Short, in Latin letters. A template shows the block with @block(\'code\'), a text with the + button of the editor.',
    ],
    'embed' => 'Block',
    'builder' => [
        'label' => 'Reusable block',
    ],
];
