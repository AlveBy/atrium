<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => 'Línea :line, columna :column: :message',
    'types' => [
        'number' => 'número',
        'text' => 'texto',
        'boolean' => 'sí/no',
        'date' => 'fecha',
        'empty' => 'nada',
        'any' => 'cualquier valor',
        'list' => 'lista: :of',
        'record' => 'registro',
        'or' => ' o ',
    ],
    'syntax' => [
        'encoding' => 'La fórmula no es un texto UTF-8 válido.',
        'too_long' => 'La fórmula tiene más de :limit caracteres.',
        'too_deep' => 'Los paréntesis y las funciones están anidados en más de :limit niveles.',
        'unknown_character' => 'Carácter desconocido «:character».',
        'unclosed_string' => 'El texto abierto con comillas no está cerrado.',
        'unclosed_name' => 'El nombre abierto con un corchete no está cerrado.',
        'empty_name' => 'No hay ningún nombre entre los corchetes.',
        'bad_date' => 'Esa fecha no existe: :date.',
        'unexpected' => 'Aquí no se esperaba «:token».',
        'unexpected_end' => 'La fórmula termina antes de tiempo.',
        'operator_case' => 'Aquí no se esperaba «:word». Los operadores se escriben en mayúsculas: :operator.',
        'chained_comparison' => 'Solo se pueden comparar dos valores a la vez. Una las comparaciones con AND u OR.',
    ],
    'arguments' => [
        'too_few' => 'A la función :function le faltan argumentos: como mínimo :min, recibidos: :given.',
        'too_many' => 'La función :function admite menos argumentos: como máximo :max, recibidos: :given.',
    ],
    'formula' => [
        'too_big' => 'La fórmula tiene más de :limit partes.',
        'too_deep' => 'La fórmula está anidada en más de :limit niveles.',
        'too_long' => 'La fórmula guardada ocupa más de :limit bytes.',
    ],
    'ir' => [
        'invalid' => 'La fórmula guardada está dañada en «:path».',
        'version' => 'La fórmula está guardada en una versión del formato que aquí no se puede leer; se lee la versión :supported.',
    ],
    'check' => [
        'unknown_field' => 'No existe el campo «:name».',
        'unknown_variable' => 'Aquí no existe el elemento «:name»: ningún bucle alrededor de este punto tiene ese nombre.',
        'unknown_member' => 'El registro relacionado no tiene el campo «:name».',
        'not_a_record' => '«.:name» necesita un registro relacionado antes del punto, encontrado: :actual.',
        'unknown_function' => 'No existe la función «:name».',
        'permission' => 'La función :function requiere el permiso «:permission».',
        'operand' => 'El operador «:operator» necesita: :expected, encontrado: :actual.',
        'argument' => 'El argumento :argument de la función :function necesita: :expected, encontrado: :actual.',
        'compare' => '«:operator» no puede comparar estos tipos: :left y :right.',
        'condition' => 'Una condición debe dar sí o no, encontrado: :actual.',
        'branches' => 'Los dos resultados de una condición deben ser del mismo tipo, encontrado: :then y :otherwise.',
        'not_a_list' => 'Un bucle necesita una lista, encontrado: :actual.',
        'depth' => 'La fórmula está anidada en más de los :limit niveles permitidos.',
        'deep_list' => 'Las listas están anidadas en más de :limit niveles.',
    ],
    'value' => [
        'empty' => 'Aquí hay un valor vacío, pero la fórmula necesita: :expected.',
        'empty_field' => 'El campo «:field» está vacío, pero la fórmula necesita: :expected.',
        'wrong_type' => 'La fórmula necesita: :expected, encontrado: :actual.',
        'bad_field' => 'El campo «:field» contiene un valor que no corresponde a su tipo: :expected.',
        'bad_return' => 'La función :function devolvió un valor que no corresponde a su tipo: :expected.',
        'compare' => 'No se pueden comparar: :left y :right.',
        'too_large' => 'Un número es demasiado grande: tiene más de :digits cifras antes de la coma.',
        'division_by_zero' => 'División por cero.',
        'exponent' => 'La potencia debe ser un número entero entre -:limit y :limit.',
        'whole_days' => 'A una fecha solo se le pueden sumar o restar días enteros.',
        'out_of_range' => 'El argumento :argument de la función :function debe ser un número entero de :min a :max.',
        'negative_root' => 'Un número negativo no tiene raíz cuadrada.',
        'date_out_of_range' => 'La fecha queda fuera de los años 1 a 9999.',
        'no_values' => 'La función :function no tiene valores con los que trabajar.',
        'search_too_long' => 'La función :function puede buscar como máximo :limit caracteres.',
    ],
    'limit' => [
        'steps' => 'La fórmula se detuvo: más de :limit pasos.',
        'time' => 'La fórmula se detuvo: duró más de :limit ms.',
        'memory' => 'La fórmula se detuvo: sus valores ocuparon más de :limit bytes.',
        'recursion' => 'La fórmula se detuvo: más de :limit fórmulas se ejecutaban unas dentro de otras.',
    ],
];
