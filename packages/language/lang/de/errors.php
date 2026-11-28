<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => 'Zeile :line, Spalte :column: :message',
    'types' => [
        'number' => 'Zahl',
        'text' => 'Text',
        'boolean' => 'Ja/Nein',
        'date' => 'Datum',
        'empty' => 'nichts',
        'any' => 'beliebiger Wert',
        'list' => 'Liste: :of',
        'record' => 'Datensatz',
        'or' => ' oder ',
    ],
    'syntax' => [
        'encoding' => 'Die Formel ist kein gültiger UTF-8-Text.',
        'too_long' => 'Die Formel ist länger als :limit Zeichen.',
        'too_deep' => 'Klammern und Funktionen sind tiefer als :limit Ebenen verschachtelt.',
        'unknown_character' => 'Unbekanntes Zeichen „:character“.',
        'unclosed_string' => 'Der mit einem Anführungszeichen begonnene Text ist nicht abgeschlossen.',
        'unclosed_name' => 'Der mit einer eckigen Klammer begonnene Name ist nicht abgeschlossen.',
        'empty_name' => 'Zwischen den eckigen Klammern steht kein Name.',
        'bad_date' => 'Dieses Datum gibt es nicht: :date.',
        'unexpected' => '„:token“ wurde hier nicht erwartet.',
        'unexpected_end' => 'Die Formel endet zu früh.',
        'operator_case' => '„:word“ wurde hier nicht erwartet. Operatoren werden in Großbuchstaben geschrieben: :operator.',
        'chained_comparison' => 'Es können nur zwei Werte auf einmal verglichen werden. Verbinden Sie Vergleiche mit AND oder OR.',
    ],
    'arguments' => [
        'too_few' => 'Der Funktion :function fehlen Argumente: mindestens :min, angegeben: :given.',
        'too_many' => 'Die Funktion :function nimmt weniger Argumente: höchstens :max, angegeben: :given.',
    ],
    'formula' => [
        'too_big' => 'Die Formel hat mehr als :limit Teile.',
        'too_deep' => 'Die Formel ist tiefer als :limit Ebenen verschachtelt.',
        'too_long' => 'Die gespeicherte Formel ist länger als :limit Bytes.',
    ],
    'ir' => [
        'invalid' => 'Die gespeicherte Formel ist bei „:path“ beschädigt.',
        'version' => 'Die Formel ist in einer Formatversion gespeichert, die hier nicht gelesen werden kann; gelesen wird Version :supported.',
    ],
    'check' => [
        'unknown_field' => 'Ein Feld „:name“ gibt es nicht.',
        'unknown_variable' => 'Ein Element „:name“ gibt es hier nicht: Keine Schleife um diese Stelle trägt diesen Namen.',
        'unknown_member' => 'Der verknüpfte Datensatz hat kein Feld „:name“.',
        'not_a_record' => '„.:name“ braucht vor dem Punkt einen verknüpften Datensatz, gefunden: :actual.',
        'unknown_function' => 'Eine Funktion „:name“ gibt es nicht.',
        'permission' => 'Die Funktion :function braucht die Berechtigung „:permission“.',
        'operand' => 'Der Operator „:operator“ braucht: :expected, gefunden: :actual.',
        'argument' => 'Argument :argument der Funktion :function braucht: :expected, gefunden: :actual.',
        'compare' => '„:operator“ kann diese nicht vergleichen: :left und :right.',
        'condition' => 'Eine Bedingung muss Ja oder Nein ergeben, gefunden: :actual.',
        'branches' => 'Beide Ergebnisse einer Bedingung müssen denselben Typ haben, gefunden: :then und :otherwise.',
        'not_a_list' => 'Eine Schleife braucht eine Liste, gefunden: :actual.',
        'depth' => 'Die Formel ist tiefer als die erlaubten :limit Ebenen verschachtelt.',
        'deep_list' => 'Listen sind tiefer als :limit Ebenen ineinander verschachtelt.',
    ],
    'value' => [
        'empty' => 'Hier ist ein Wert leer, die Formel braucht aber: :expected.',
        'empty_field' => 'Das Feld „:field“ ist leer, die Formel braucht aber: :expected.',
        'wrong_type' => 'Die Formel braucht: :expected, gefunden: :actual.',
        'bad_field' => 'Das Feld „:field“ enthält einen Wert, der nicht zu seinem Typ passt: :expected.',
        'bad_return' => 'Die Funktion :function hat einen Wert geliefert, der nicht zu ihrem Typ passt: :expected.',
        'compare' => 'Diese Werte lassen sich nicht vergleichen: :left und :right.',
        'too_large' => 'Eine Zahl ist zu groß: mehr als :digits Stellen vor dem Komma.',
        'division_by_zero' => 'Division durch null.',
        'exponent' => 'Die Potenz muss eine ganze Zahl zwischen -:limit und :limit sein.',
        'whole_days' => 'Zu einem Datum lassen sich nur ganze Tage addieren oder davon abziehen.',
        'out_of_range' => 'Argument :argument der Funktion :function muss eine ganze Zahl von :min bis :max sein.',
        'negative_root' => 'Aus einer negativen Zahl gibt es keine Quadratwurzel.',
        'date_out_of_range' => 'Das Datum liegt außerhalb der Jahre 1 bis 9999.',
        'no_values' => 'Die Funktion :function hat keine Werte, mit denen sie rechnen kann.',
        'search_too_long' => 'Die Funktion :function kann höchstens :limit Zeichen suchen.',
    ],
    'limit' => [
        'steps' => 'Die Formel wurde angehalten: mehr als :limit Schritte.',
        'time' => 'Die Formel wurde angehalten: Sie lief länger als :limit ms.',
        'memory' => 'Die Formel wurde angehalten: Ihre Werte belegten mehr als :limit Bytes.',
        'recursion' => 'Die Formel wurde angehalten: Mehr als :limit Formeln liefen ineinander.',
    ],
];
