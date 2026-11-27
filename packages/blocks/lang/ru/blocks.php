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
        'block' => 'Добавить блок',
    ],
    'fields' => [
        'name' => 'Название',
        'content' => 'Содержимое',
        'updated' => 'Изменён',
        'code_hint' => 'Коротко, латиницей. Шаблон показывает блок через @block(\'код\'), текст — кнопкой + в редакторе.',
    ],
    'embed' => 'Блок',
    'builder' => [
        'label' => 'Переиспользуемый блок',
    ],
];
