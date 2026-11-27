<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => 'Wiersz :line, kolumna :column: :message',
    'types' => [
        'number' => 'liczba',
        'text' => 'tekst',
        'boolean' => 'tak/nie',
        'date' => 'data',
        'empty' => 'nic',
        'any' => 'dowolna wartość',
        'list' => 'lista: :of',
        'record' => 'rekord',
        'or' => ' lub ',
    ],
    'syntax' => [
        'encoding' => 'Formuła nie jest poprawnym tekstem UTF-8.',
        'too_long' => 'Formuła ma więcej niż :limit znaków.',
        'too_deep' => 'Nawiasy i funkcje są zagnieżdżone głębiej niż na :limit poziomów.',
        'unknown_character' => 'Nieznany znak „:character”.',
        'unclosed_string' => 'Tekst otwarty cudzysłowem nie został zamknięty.',
        'unclosed_name' => 'Nazwa otwarta nawiasem kwadratowym nie została zamknięta.',
        'empty_name' => 'Między nawiasami kwadratowymi nie ma nazwy.',
        'bad_date' => 'Nie ma takiej daty: :date.',
        'unexpected' => '„:token” nie jest tu oczekiwane.',
        'unexpected_end' => 'Formuła kończy się za wcześnie.',
        'operator_case' => '„:word” nie jest tu oczekiwane. Operatory pisze się wielkimi literami: :operator.',
        'chained_comparison' => 'Naraz można porównać tylko dwie wartości. Łącz porównania przez AND lub OR.',
    ],
    'arguments' => [
        'too_few' => 'Funkcji :function brakuje argumentów: potrzeba co najmniej :min, podano: :given.',
        'too_many' => 'Funkcja :function przyjmuje mniej argumentów: najwyżej :max, podano: :given.',
    ],
    'formula' => [
        'too_big' => 'Formuła ma więcej niż :limit części.',
        'too_deep' => 'Formuła jest zagnieżdżona głębiej niż na :limit poziomów.',
        'too_long' => 'Zapisana formuła ma więcej niż :limit bajtów.',
    ],
    'ir' => [
        'invalid' => 'Zapisana formuła jest uszkodzona w miejscu „:path”.',
        'version' => 'Formuła jest zapisana w wersji formatu, której nie można tu odczytać; odczytywana jest wersja :supported.',
    ],
    'check' => [
        'unknown_field' => 'Nie ma pola „:name”.',
        'unknown_variable' => 'Nie ma tu elementu „:name”: żadna pętla wokół tego miejsca nie ma takiej nazwy.',
        'unknown_member' => 'Powiązany rekord nie ma pola „:name”.',
        'not_a_record' => '„.:name” wymaga przed kropką powiązanego rekordu, a jest: :actual.',
        'unknown_function' => 'Nie ma funkcji „:name”.',
        'permission' => 'Funkcja :function wymaga uprawnienia „:permission”.',
        'operand' => 'Operator „:operator” wymaga: :expected, a jest: :actual.',
        'argument' => 'Argument :argument funkcji :function wymaga: :expected, a jest: :actual.',
        'compare' => '„:operator” nie może porównać tych typów: :left i :right.',
        'condition' => 'Warunek musi dawać tak lub nie, a jest: :actual.',
        'branches' => 'Oba wyniki warunku muszą być tego samego typu, a są: :then i :otherwise.',
        'not_a_list' => 'Pętla wymaga listy, a jest: :actual.',
        'depth' => 'Formuła jest zagnieżdżona głębiej niż dozwolone :limit poziomów.',
        'deep_list' => 'Listy są zagnieżdżone głębiej niż na :limit poziomów.',
    ],
    'value' => [
        'empty' => 'Wartość jest tu pusta, a formuła wymaga: :expected.',
        'empty_field' => 'Pole „:field” jest puste, a formuła wymaga: :expected.',
        'wrong_type' => 'Formuła wymaga: :expected, a jest: :actual.',
        'bad_field' => 'Pole „:field” zawiera wartość niepasującą do jego typu: :expected.',
        'bad_return' => 'Funkcja :function zwróciła wartość niepasującą do jej typu: :expected.',
        'compare' => 'Nie można porównać: :left i :right.',
        'too_large' => 'Liczba jest za duża: ma więcej niż :digits cyfr przed przecinkiem.',
        'division_by_zero' => 'Dzielenie przez zero.',
        'exponent' => 'Potęga musi być liczbą całkowitą od -:limit do :limit.',
        'whole_days' => 'Do daty można dodawać i od niej odejmować tylko całe dni.',
        'out_of_range' => 'Argument :argument funkcji :function musi być liczbą całkowitą od :min do :max.',
        'negative_root' => 'Liczba ujemna nie ma pierwiastka kwadratowego.',
        'date_out_of_range' => 'Data wykracza poza lata od 1 do 9999.',
        'no_values' => 'Funkcja :function nie ma żadnych wartości do przetworzenia.',
        'search_too_long' => 'Funkcja :function może szukać najwyżej :limit znaków.',
    ],
    'limit' => [
        'steps' => 'Formułę zatrzymano: więcej niż :limit kroków.',
        'time' => 'Formułę zatrzymano: działała dłużej niż :limit ms.',
        'memory' => 'Formułę zatrzymano: jej wartości zajęły więcej niż :limit bajtów.',
        'recursion' => 'Formułę zatrzymano: więcej niż :limit formuł działało jedna wewnątrz drugiej.',
    ],
];
