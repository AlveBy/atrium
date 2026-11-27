<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => 'Linha :line, coluna :column: :message',
    'types' => [
        'number' => 'número',
        'text' => 'texto',
        'boolean' => 'sim/não',
        'date' => 'data',
        'empty' => 'nada',
        'any' => 'qualquer valor',
        'list' => 'lista: :of',
        'record' => 'registro',
        'or' => ' ou ',
    ],
    'syntax' => [
        'encoding' => 'A fórmula não é um texto UTF-8 válido.',
        'too_long' => 'A fórmula tem mais de :limit caracteres.',
        'too_deep' => 'Parênteses e funções estão aninhados em mais de :limit níveis.',
        'unknown_character' => 'Caractere desconhecido “:character”.',
        'unclosed_string' => 'O texto aberto com aspas não foi fechado.',
        'unclosed_name' => 'O nome aberto com colchete não foi fechado.',
        'empty_name' => 'Não há nome entre os colchetes.',
        'bad_date' => 'Essa data não existe: :date.',
        'unexpected' => '“:token” não era esperado aqui.',
        'unexpected_end' => 'A fórmula termina cedo demais.',
        'operator_case' => '“:word” não era esperado aqui. Os operadores são escritos em maiúsculas: :operator.',
        'chained_comparison' => 'Só é possível comparar dois valores por vez. Una as comparações com AND ou OR.',
    ],
    'arguments' => [
        'too_few' => 'Faltam argumentos na função :function: no mínimo :min, informados: :given.',
        'too_many' => 'A função :function aceita menos argumentos: no máximo :max, informados: :given.',
    ],
    'formula' => [
        'too_big' => 'A fórmula tem mais de :limit partes.',
        'too_deep' => 'A fórmula está aninhada em mais de :limit níveis.',
        'too_long' => 'A fórmula salva tem mais de :limit bytes.',
    ],
    'ir' => [
        'invalid' => 'A fórmula salva está danificada em “:path”.',
        'version' => 'A fórmula está salva em uma versão do formato que não pode ser lida aqui; a versão lida é :supported.',
    ],
    'check' => [
        'unknown_field' => 'Não existe o campo “:name”.',
        'unknown_variable' => 'Não existe o item “:name” aqui: nenhum laço em volta deste ponto tem esse nome.',
        'unknown_member' => 'O registro relacionado não tem o campo “:name”.',
        'not_a_record' => '“.:name” precisa de um registro relacionado antes do ponto, encontrado: :actual.',
        'unknown_function' => 'Não existe a função “:name”.',
        'permission' => 'A função :function exige a permissão “:permission”.',
        'operand' => 'O operador “:operator” precisa de: :expected, encontrado: :actual.',
        'argument' => 'O argumento :argument da função :function precisa de: :expected, encontrado: :actual.',
        'compare' => '“:operator” não consegue comparar estes tipos: :left e :right.',
        'condition' => 'Uma condição deve resultar em sim ou não, encontrado: :actual.',
        'branches' => 'Os dois resultados de uma condição devem ser do mesmo tipo, encontrado: :then e :otherwise.',
        'not_a_list' => 'Um laço precisa de uma lista, encontrado: :actual.',
        'depth' => 'A fórmula está aninhada além dos :limit níveis permitidos.',
        'deep_list' => 'As listas estão aninhadas em mais de :limit níveis.',
    ],
    'value' => [
        'empty' => 'Há um valor vazio aqui, mas a fórmula precisa de: :expected.',
        'empty_field' => 'O campo “:field” está vazio, mas a fórmula precisa de: :expected.',
        'wrong_type' => 'A fórmula precisa de: :expected, encontrado: :actual.',
        'bad_field' => 'O campo “:field” contém um valor que não combina com o tipo dele: :expected.',
        'bad_return' => 'A função :function devolveu um valor que não combina com o tipo dela: :expected.',
        'compare' => 'Não é possível comparar: :left e :right.',
        'too_large' => 'Um número é grande demais: tem mais de :digits dígitos antes da vírgula.',
        'division_by_zero' => 'Divisão por zero.',
        'exponent' => 'A potência deve ser um número inteiro entre -:limit e :limit.',
        'whole_days' => 'A uma data só se podem somar ou subtrair dias inteiros.',
        'out_of_range' => 'O argumento :argument da função :function deve ser um número inteiro de :min a :max.',
        'negative_root' => 'Número negativo não tem raiz quadrada.',
        'date_out_of_range' => 'A data está fora dos anos 1 a 9999.',
        'no_values' => 'A função :function não tem valores para trabalhar.',
        'search_too_long' => 'A função :function pode procurar no máximo :limit caracteres.',
    ],
    'limit' => [
        'steps' => 'A fórmula foi interrompida: mais de :limit passos.',
        'time' => 'A fórmula foi interrompida: levou mais de :limit ms.',
        'memory' => 'A fórmula foi interrompida: os valores ocuparam mais de :limit bytes.',
        'recursion' => 'A fórmula foi interrompida: mais de :limit fórmulas estavam rodando umas dentro das outras.',
    ],
];
