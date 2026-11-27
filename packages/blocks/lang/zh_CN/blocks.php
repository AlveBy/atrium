<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'blocks' => '区块',
    ],
    'create' => [
        'block' => '添加区块',
    ],
    'fields' => [
        'name' => '名称',
        'content' => '内容',
        'updated' => '已修改',
        'code_hint' => '简短，使用拉丁字母。模板用 @block(\'代码\') 显示区块，文本则用编辑器的 + 按钮插入。',
    ],
    'embed' => '区块',
    'builder' => [
        'label' => '可复用区块',
    ],
];
