<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'blocks' => 'ブロック',
    ],
    'create' => [
        'block' => 'ブロックを追加',
    ],
    'fields' => [
        'name' => '名前',
        'content' => '内容',
        'updated' => '変更日',
        'code_hint' => '短く、ラテン文字で。テンプレートでは @block(\'コード\') で、テキストではエディターの + ボタンで表示します。',
    ],
    'embed' => 'ブロック',
];
