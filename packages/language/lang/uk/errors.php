<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => 'Рядок :line, позиція :column: :message',
    'types' => [
        'number' => 'число',
        'text' => 'текст',
        'boolean' => 'так/ні',
        'date' => 'дата',
        'empty' => 'порожньо',
        'any' => 'будь-яке значення',
        'list' => 'список: :of',
        'record' => 'запис',
        'or' => ' або ',
    ],
    'syntax' => [
        'encoding' => 'Формула — не текст у кодуванні UTF-8.',
        'too_long' => 'Формула довша за дозволене: не більше :limit символів.',
        'too_deep' => 'Дужки та функції вкладені глибше ніж на :limit рівнів.',
        'unknown_character' => 'Невідомий символ «:character».',
        'unclosed_string' => 'Не закрито лапки, що відкривають текст.',
        'unclosed_name' => 'Не закрито квадратну дужку, що відкриває назву.',
        'empty_name' => 'Між квадратними дужками немає назви.',
        'bad_date' => 'Такої дати немає: :date.',
        'unexpected' => '«:token» тут не очікується.',
        'unexpected_end' => 'Формула обривається завчасно.',
        'operator_case' => '«:word» тут не очікується. Оператори пишуться великими літерами: :operator.',
        'chained_comparison' => 'За один раз порівнюються лише два значення. Об’єднуйте порівняння через І (AND) або АБО (OR).',
    ],
    'arguments' => [
        'too_few' => 'Функції :function бракує аргументів: потрібно щонайменше :min, передано: :given.',
        'too_many' => 'У функції :function забагато аргументів: можна щонайбільше :max, передано: :given.',
    ],
    'formula' => [
        'too_big' => 'У формулі більше ніж :limit частин.',
        'too_deep' => 'Формула вкладена глибше ніж на :limit рівнів.',
        'too_long' => 'Збережена формула довша за :limit байтів.',
    ],
    'ir' => [
        'invalid' => 'Збережену формулу пошкоджено в місці «:path».',
        'version' => 'Формулу збережено у версії формату, яку тут прочитати не можна; читається версія :supported.',
    ],
    'check' => [
        'unknown_field' => 'Поля «:name» немає.',
        'unknown_variable' => 'Елемента «:name» тут немає: навколо цього місця немає циклу з такою назвою.',
        'unknown_member' => 'У пов’язаного запису немає поля «:name».',
        'not_a_record' => 'Перед «.:name» потрібен пов’язаний запис, а там: :actual.',
        'unknown_function' => 'Функції «:name» немає.',
        'permission' => 'Для функції :function потрібне право «:permission».',
        'operand' => 'Оператору «:operator» потрібен тип: :expected, а отримано: :actual.',
        'argument' => 'Аргумент :argument функції :function: потрібен тип «:expected», а отримано «:actual».',
        'compare' => '«:operator» не може порівняти ці типи: :left і :right.',
        'condition' => 'Умова має давати так або ні, а дає: :actual.',
        'branches' => 'Обидва результати умови мають бути одного типу, а отримано: :then і :otherwise.',
        'not_a_list' => 'Циклу потрібен список, а отримано тип: :actual.',
        'depth' => 'Формула вкладена глибше за дозволені :limit рівнів.',
        'deep_list' => 'Списки вкладені один в одного глибше, ніж на :limit рівнів.',
    ],
    'value' => [
        'empty' => 'Тут порожнє значення, а формулі потрібен тип: :expected.',
        'empty_field' => 'Поле «:field» порожнє, а формулі потрібне значення типу «:expected».',
        'wrong_type' => 'Формулі потрібен тип «:expected», а отримано «:actual».',
        'bad_field' => 'У полі «:field» значення не відповідає його типу: :expected.',
        'bad_return' => 'Функція :function повернула значення, що не відповідає її типу: :expected.',
        'compare' => 'Ці значення не можна порівняти: :left і :right.',
        'too_large' => 'Завелике число: більше ніж :digits цифр до крапки.',
        'division_by_zero' => 'Ділення на нуль.',
        'exponent' => 'Степінь має бути цілим числом від -:limit до :limit.',
        'whole_days' => 'До дати можна додавати й від неї віднімати лише цілу кількість днів.',
        'out_of_range' => 'Аргумент :argument функції :function має бути цілим числом від :min до :max.',
        'negative_root' => 'Квадратного кореня з від’ємного числа немає.',
        'date_out_of_range' => 'Дата виходить за межі 1–9999 років.',
        'no_values' => 'Функції :function нема з чим працювати: немає жодного значення.',
        'search_too_long' => 'Функція :function шукає щонайбільше :limit символів.',
    ],
    'limit' => [
        'steps' => 'Формулу зупинено: більше ніж :limit кроків.',
        'time' => 'Формулу зупинено: виконувалася довше ніж :limit мс.',
        'memory' => 'Формулу зупинено: значення зайняли більше ніж :limit байтів.',
        'recursion' => 'Формулу зупинено: більше ніж :limit формул виконувалися одна всередині одної.',
    ],
];
