<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => 'Радок :line, пазіцыя :column: :message',
    'types' => [
        'number' => 'лік',
        'text' => 'тэкст',
        'boolean' => 'так/не',
        'date' => 'дата',
        'empty' => 'пуста',
        'any' => 'любое значэнне',
        'list' => 'спіс: :of',
        'record' => 'запіс',
        'or' => ' або ',
    ],
    'syntax' => [
        'encoding' => 'Формула — не тэкст у кадзіроўцы UTF-8.',
        'too_long' => 'Формула даўжэйшая за дазволенае: не больш за :limit сімвалаў.',
        'too_deep' => 'Дужкі і функцыі ўкладзены глыбей за :limit узроўняў.',
        'unknown_character' => 'Невядомы сімвал «:character».',
        'unclosed_string' => 'Не закрыта двукоссе, якое адкрывае тэкст.',
        'unclosed_name' => 'Не закрыта квадратная дужка, якая адкрывае назву.',
        'empty_name' => 'Паміж квадратнымі дужкамі няма назвы.',
        'bad_date' => 'Такой даты няма: :date.',
        'unexpected' => '«:token» тут не чакаецца.',
        'unexpected_end' => 'Формула абрываецца заўчасна.',
        'operator_case' => '«:word» тут не чакаецца. Аператары пішуцца вялікімі літарамі: :operator.',
        'chained_comparison' => 'За адзін раз параўноўваюцца толькі два значэнні. Аб’ядноўвайце параўнанні праз І (AND) або АБО (OR).',
    ],
    'arguments' => [
        'too_few' => 'Функцыі :function не хапае аргументаў: трэба не менш за :min, перададзена: :given.',
        'too_many' => 'У функцыі :function занадта шмат аргументаў: можна не больш за :max, перададзена: :given.',
    ],
    'formula' => [
        'too_big' => 'У формуле больш за :limit частак.',
        'too_deep' => 'Формула ўкладзена глыбей за :limit узроўняў.',
        'too_long' => 'Захаваная формула даўжэйшая за :limit байтаў.',
    ],
    'ir' => [
        'invalid' => 'Захаваная формула пашкоджана ў месцы «:path».',
        'version' => 'Формула захавана ў версіі фармату, якую тут прачытаць нельга; чытаецца версія :supported.',
    ],
    'check' => [
        'unknown_field' => 'Поля «:name» няма.',
        'unknown_variable' => 'Элемента «:name» тут няма: вакол гэтага месца няма цыкла з такой назвай.',
        'unknown_member' => 'У звязанага запісу няма поля «:name».',
        'not_a_record' => 'Перад «.:name» патрэбен звязаны запіс, а там: :actual.',
        'unknown_function' => 'Функцыі «:name» няма.',
        'permission' => 'Для функцыі :function патрэбна права «:permission».',
        'operand' => 'Аператару «:operator» патрэбен тып: :expected, а атрыманы: :actual.',
        'argument' => 'Аргумент :argument функцыі :function: патрэбен тып «:expected», а атрыманы «:actual».',
        'compare' => '«:operator» не можа параўнаць гэтыя тыпы: :left і :right.',
        'condition' => 'Умова павінна даваць так або не, а дае: :actual.',
        'branches' => 'Абодва вынікі ўмовы павінны быць аднаго тыпу, а атрыманы: :then і :otherwise.',
        'not_a_list' => 'Цыклу патрэбен спіс, а атрыманы тып: :actual.',
        'depth' => 'Формула ўкладзена глыбей за дазволеныя :limit узроўняў.',
        'deep_list' => 'Спісы ўкладзены адзін у адзін глыбей, чым на :limit узроўняў.',
    ],
    'value' => [
        'empty' => 'Тут пустое значэнне, а формуле патрэбен тып: :expected.',
        'empty_field' => 'Поле «:field» пустое, а формуле патрэбна значэнне тыпу «:expected».',
        'wrong_type' => 'Формуле патрэбен тып «:expected», а атрыманы «:actual».',
        'bad_field' => 'У полі «:field» значэнне не адпавядае яго тыпу: :expected.',
        'bad_return' => 'Функцыя :function вярнула значэнне, якое не адпавядае яе тыпу: :expected.',
        'compare' => 'Гэтыя значэнні нельга параўнаць: :left і :right.',
        'too_large' => 'Занадта вялікі лік: больш за :digits лічбаў да кропкі.',
        'division_by_zero' => 'Дзяленне на нуль.',
        'exponent' => 'Ступень павінна быць цэлым лікам ад -:limit да :limit.',
        'whole_days' => 'Да даты можна дадаваць і ад яе аднімаць толькі цэлую колькасць дзён.',
        'out_of_range' => 'Аргумент :argument функцыі :function павінен быць цэлым лікам ад :min да :max.',
        'negative_root' => 'Квадратнага кораня з адмоўнага ліку няма.',
        'date_out_of_range' => 'Дата выходзіць за межы 1–9999 гадоў.',
        'no_values' => 'Функцыі :function няма з чым працаваць: няма ніводнага значэння.',
        'search_too_long' => 'Функцыя :function шукае не больш за :limit сімвалаў.',
    ],
    'limit' => [
        'steps' => 'Формула спынена: больш за :limit крокаў.',
        'time' => 'Формула спынена: выконвалася даўжэй за :limit мс.',
        'memory' => 'Формула спынена: значэнні занялі больш за :limit байтаў.',
        'recursion' => 'Формула спынена: больш за :limit формул выконваліся адна ўнутры адной.',
    ],
];
