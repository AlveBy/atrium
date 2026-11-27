<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'blocks' => 'Блоки',
    ],
    'create' => [
        'block' => 'Додати блок',
    ],
    'fields' => [
        'name' => 'Назва',
        'content' => 'Вміст',
        'updated' => 'Змінено',
        'code_hint' => 'Коротко, латиницею. Шаблон показує блок через @block(\'код\'), текст — кнопкою + у редакторі.',
    ],
    'embed' => 'Блок',
    'builder' => [
        'label' => 'Багаторазовий блок',
    ],
];
