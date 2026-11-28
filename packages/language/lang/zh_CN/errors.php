<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => '第 :line 行第 :column 列：:message',
    'types' => [
        'number' => '数字',
        'text' => '文本',
        'boolean' => '是/否',
        'date' => '日期',
        'empty' => '空',
        'any' => '任意值',
        'list' => ':of 列表',
        'record' => '记录',
        'or' => ' 或 ',
    ],
    'syntax' => [
        'encoding' => '公式不是有效的 UTF-8 文本。',
        'too_long' => '公式超过了 :limit 个字符。',
        'too_deep' => '括号和函数的嵌套超过了 :limit 层。',
        'unknown_character' => '未知字符“:character”。',
        'unclosed_string' => '用引号开始的文本没有结束。',
        'unclosed_name' => '用方括号开始的名称没有结束。',
        'empty_name' => '方括号之间没有名称。',
        'bad_date' => '没有这个日期：:date。',
        'unexpected' => '此处不应出现“:token”。',
        'unexpected_end' => '公式过早结束。',
        'operator_case' => '此处不应出现“:word”。运算符要用大写字母书写：:operator。',
        'chained_comparison' => '一次只能比较两个值。请用 AND 或 OR 连接多个比较。',
    ],
    'arguments' => [
        'too_few' => '函数 :function 的参数不足：至少需要 :min 个，实际提供 :given 个。',
        'too_many' => '函数 :function 的参数过多：最多 :max 个，实际提供 :given 个。',
    ],
    'formula' => [
        'too_big' => '公式的组成部分超过了 :limit 个。',
        'too_deep' => '公式的嵌套超过了 :limit 层。',
        'too_long' => '保存的公式超过了 :limit 字节。',
    ],
    'ir' => [
        'invalid' => '保存的公式在“:path”处已损坏。',
        'version' => '公式保存所用的格式版本在此无法读取；可读取的版本是 :supported。',
    ],
    'check' => [
        'unknown_field' => '没有字段“:name”。',
        'unknown_variable' => '此处没有“:name”这一项：包围此处的循环中没有使用该名称的。',
        'unknown_member' => '关联记录没有字段“:name”。',
        'not_a_record' => '“.:name”的点号前需要一个关联记录，实际为：:actual。',
        'unknown_function' => '没有函数“:name”。',
        'permission' => '函数 :function 需要权限“:permission”。',
        'operand' => '运算符“:operator”需要：:expected，实际为：:actual。',
        'argument' => '函数 :function 的第 :argument 个参数需要：:expected，实际为：:actual。',
        'compare' => '“:operator”无法比较以下类型：:left 和 :right。',
        'condition' => '条件的结果必须是“是”或“否”，实际为：:actual。',
        'branches' => '条件的两个结果必须是同一类型，实际为：:then 和 :otherwise。',
        'not_a_list' => '循环需要一个列表，实际为：:actual。',
        'depth' => '公式的嵌套超过了允许的 :limit 层。',
        'deep_list' => '列表的嵌套超过了 :limit 层。',
    ],
    'value' => [
        'empty' => '此处的值为空，但公式需要：:expected。',
        'empty_field' => '字段“:field”为空，但公式需要：:expected。',
        'wrong_type' => '公式需要：:expected，实际为：:actual。',
        'bad_field' => '字段“:field”的值与其类型不符：:expected。',
        'bad_return' => '函数 :function 返回的值与其类型不符：:expected。',
        'compare' => '无法比较：:left 和 :right。',
        'too_large' => '数字过大：小数点前超过了 :digits 位。',
        'division_by_zero' => '除数为零。',
        'exponent' => '指数必须是 -:limit 到 :limit 之间的整数。',
        'whole_days' => '日期只能加减整数天。',
        'out_of_range' => '函数 :function 的第 :argument 个参数必须是 :min 到 :max 之间的整数。',
        'negative_root' => '负数没有平方根。',
        'date_out_of_range' => '日期超出了 1 年到 9999 年的范围。',
        'no_values' => '函数 :function 没有可处理的值。',
        'search_too_long' => '函数 :function 最多只能查找 :limit 个字符。',
    ],
    'limit' => [
        'steps' => '公式已停止：超过了 :limit 步。',
        'time' => '公式已停止：运行时间超过了 :limit 毫秒。',
        'memory' => '公式已停止：公式中的值占用超过了 :limit 字节。',
        'recursion' => '公式已停止：相互嵌套运行的公式超过了 :limit 个。',
    ],
];
