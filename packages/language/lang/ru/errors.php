<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'at' => 'Строка :line, позиция :column: :message',
    'types' => [
        'number' => 'число',
        'text' => 'текст',
        'boolean' => 'да/нет',
        'date' => 'дата',
        'empty' => 'пусто',
        'any' => 'любое значение',
        'list' => 'список: :of',
        'record' => 'запись',
        'or' => ' или ',
    ],
    'syntax' => [
        'encoding' => 'Формула — не текст в кодировке UTF-8.',
        'too_long' => 'Формула длиннее допустимого: не более :limit символов.',
        'too_deep' => 'Скобки и функции вложены глубже :limit уровней.',
        'unknown_character' => 'Неизвестный символ «:character».',
        'unclosed_string' => 'Не закрыта кавычка, открывающая текст.',
        'unclosed_name' => 'Не закрыта квадратная скобка, открывающая имя.',
        'empty_name' => 'Между квадратными скобками нет имени.',
        'bad_date' => 'Такой даты нет: :date.',
        'unexpected' => '«:token» здесь не ожидается.',
        'unexpected_end' => 'Формула обрывается раньше времени.',
        'operator_case' => '«:word» здесь не ожидается. Операторы пишутся заглавными буквами: :operator.',
        'chained_comparison' => 'За один раз сравниваются только два значения. Объединяйте сравнения через И или ИЛИ.',
    ],
    'arguments' => [
        'too_few' => 'Функции :function не хватает аргументов: нужно не меньше :min, передано: :given.',
        'too_many' => 'У функции :function слишком много аргументов: можно не больше :max, передано: :given.',
    ],
    'formula' => [
        'too_big' => 'В формуле больше :limit частей.',
        'too_deep' => 'Формула вложена глубже :limit уровней.',
        'too_long' => 'Сохранённая формула длиннее :limit байт.',
    ],
    'ir' => [
        'invalid' => 'Сохранённая формула повреждена в месте «:path».',
        'version' => 'Формула сохранена в версии формата, которую здесь прочитать нельзя; читается версия :supported.',
    ],
    'check' => [
        'unknown_field' => 'Поля «:name» нет.',
        'unknown_variable' => 'Элемента «:name» здесь нет: вокруг этого места нет цикла с таким именем.',
        'unknown_member' => 'У связанной записи нет поля «:name».',
        'not_a_record' => 'Перед «.:name» нужна связанная запись, а там: :actual.',
        'unknown_function' => 'Функции «:name» нет.',
        'permission' => 'Для функции :function нужно право «:permission».',
        'operand' => 'Оператору «:operator» нужен тип: :expected, а получен: :actual.',
        'argument' => 'Аргумент :argument функции :function: нужен тип «:expected», а получен «:actual».',
        'compare' => '«:operator» не может сравнить эти типы: :left и :right.',
        'condition' => 'Условие должно давать да или нет, а даёт: :actual.',
        'branches' => 'Оба результата условия должны быть одного типа, а получены: :then и :otherwise.',
        'not_a_list' => 'Циклу нужен список, а получен тип: :actual.',
        'depth' => 'Формула вложена глубже разрешённых :limit уровней.',
        'deep_list' => 'Списки вложены друг в друга глубже, чем на :limit уровней.',
    ],
    'value' => [
        'empty' => 'Здесь пустое значение, а формуле нужен тип: :expected.',
        'empty_field' => 'Поле «:field» пустое, а формуле нужно значение типа «:expected».',
        'wrong_type' => 'Формуле нужен тип «:expected», а получен «:actual».',
        'bad_field' => 'В поле «:field» значение не подходит к его типу: :expected.',
        'bad_return' => 'Функция :function вернула значение, не подходящее к её типу: :expected.',
        'compare' => 'Эти значения нельзя сравнить: :left и :right.',
        'too_large' => 'Слишком большое число: больше :digits цифр до точки.',
        'division_by_zero' => 'Деление на ноль.',
        'exponent' => 'Степень должна быть целым числом от -:limit до :limit.',
        'whole_days' => 'К дате можно прибавлять и из неё вычитать только целое число дней.',
        'out_of_range' => 'Аргумент :argument функции :function должен быть целым числом от :min до :max.',
        'negative_root' => 'Квадратного корня из отрицательного числа нет.',
        'date_out_of_range' => 'Дата выходит за пределы 1–9999 годов.',
        'no_values' => 'Функции :function не с чем работать: нет ни одного значения.',
        'search_too_long' => 'Функция :function ищет не более :limit символов.',
    ],
    'limit' => [
        'steps' => 'Формула остановлена: больше :limit шагов.',
        'time' => 'Формула остановлена: выполнялась дольше :limit мс.',
        'memory' => 'Формула остановлена: значения заняли больше :limit байт.',
        'recursion' => 'Формула остановлена: больше :limit формул выполнялись друг внутри друга.',
    ],
];
