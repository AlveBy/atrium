<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => ':line 行 :column 文字目: :message',
    'types' => [
        'number' => '数値',
        'text' => 'テキスト',
        'boolean' => 'はい/いいえ',
        'date' => '日付',
        'empty' => '空',
        'any' => '任意の値',
        'list' => ':of のリスト',
        'record' => 'レコード',
        'or' => ' または ',
    ],
    'syntax' => [
        'encoding' => '数式が正しい UTF-8 テキストではありません。',
        'too_long' => '数式が :limit 文字を超えています。',
        'too_deep' => 'かっこと関数の入れ子が :limit 階層を超えています。',
        'unknown_character' => '不明な文字「:character」です。',
        'unclosed_string' => '引用符で開いたテキストが閉じられていません。',
        'unclosed_name' => '角かっこで開いた名前が閉じられていません。',
        'empty_name' => '角かっこの間に名前がありません。',
        'bad_date' => 'そのような日付はありません: :date。',
        'unexpected' => 'ここに「:token」は使えません。',
        'unexpected_end' => '数式が途中で終わっています。',
        'operator_case' => 'ここに「:word」は使えません。演算子は大文字で書きます: :operator。',
        'chained_comparison' => '一度に比較できる値は二つだけです。比較は AND または OR でつないでください。',
    ],
    'arguments' => [
        'too_few' => '関数 :function の引数が足りません。必要な数: :min 以上、指定された数: :given。',
        'too_many' => '関数 :function の引数が多すぎます。指定できる数: :max 以下、指定された数: :given。',
    ],
    'formula' => [
        'too_big' => '数式の要素が :limit 個を超えています。',
        'too_deep' => '数式の入れ子が :limit 階層を超えています。',
        'too_long' => '保存された数式が :limit バイトを超えています。',
    ],
    'ir' => [
        'invalid' => '保存された数式が「:path」の位置で壊れています。',
        'version' => '数式はここでは読み込めない形式のバージョンで保存されています。読み込めるのはバージョン :supported です。',
    ],
    'check' => [
        'unknown_field' => 'フィールド「:name」はありません。',
        'unknown_variable' => 'ここに「:name」という要素はありません。この位置を囲むループにその名前はありません。',
        'unknown_member' => '関連レコードにフィールド「:name」はありません。',
        'not_a_record' => '「.:name」の前には関連レコードが必要です。実際: :actual。',
        'unknown_function' => '関数「:name」はありません。',
        'permission' => '関数 :function には権限「:permission」が必要です。',
        'operand' => '演算子「:operator」に必要な型: :expected、実際: :actual。',
        'argument' => '関数 :function の引数 :argument に必要な型: :expected、実際: :actual。',
        'compare' => '「:operator」は次の型を比較できません: :left と :right。',
        'condition' => '条件は「はい」か「いいえ」になる必要があります。実際: :actual。',
        'branches' => '条件の二つの結果は同じ型である必要があります。実際: :then と :otherwise。',
        'not_a_list' => 'ループにはリストが必要です。実際: :actual。',
        'depth' => '数式の入れ子が許可されている :limit 階層を超えています。',
        'deep_list' => 'リストの入れ子が :limit 階層を超えています。',
    ],
    'value' => [
        'empty' => 'ここの値は空ですが、数式に必要な型は :expected です。',
        'empty_field' => 'フィールド「:field」は空ですが、数式に必要な型は :expected です。',
        'wrong_type' => '数式に必要な型: :expected、実際: :actual。',
        'bad_field' => 'フィールド「:field」の値が型に合いません: :expected。',
        'bad_return' => '関数 :function が型に合わない値を返しました: :expected。',
        'compare' => '次の値は比較できません: :left と :right。',
        'too_large' => '数値が大きすぎます。小数点より前が :digits 桁を超えています。',
        'division_by_zero' => 'ゼロで割っています。',
        'exponent' => '指数は -:limit から :limit までの整数である必要があります。',
        'whole_days' => '日付に足し引きできるのは整数の日数だけです。',
        'out_of_range' => '関数 :function の引数 :argument は :min から :max までの整数である必要があります。',
        'negative_root' => '負の数の平方根はありません。',
        'date_out_of_range' => '日付が 1 年から 9999 年の範囲外です。',
        'no_values' => '関数 :function で扱う値がありません。',
        'search_too_long' => '関数 :function で検索できるのは :limit 文字までです。',
    ],
    'limit' => [
        'steps' => '数式を停止しました: :limit ステップを超えました。',
        'time' => '数式を停止しました: 実行時間が :limit ミリ秒を超えました。',
        'memory' => '数式を停止しました: 値のサイズが :limit バイトを超えました。',
        'recursion' => '数式を停止しました: 入れ子で実行中の数式が :limit 個を超えました。',
    ],
];
