<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => 'Riga :line, colonna :column: :message',
    'types' => [
        'number' => 'numero',
        'text' => 'testo',
        'boolean' => 'sì/no',
        'date' => 'data',
        'empty' => 'niente',
        'any' => 'qualsiasi valore',
        'list' => 'elenco: :of',
        'record' => 'record',
        'or' => ' o ',
    ],
    'syntax' => [
        'encoding' => 'La formula non è un testo UTF-8 valido.',
        'too_long' => 'La formula supera i :limit caratteri.',
        'too_deep' => 'Parentesi e funzioni sono annidate per più di :limit livelli.',
        'unknown_character' => 'Carattere sconosciuto «:character».',
        'unclosed_string' => 'Il testo aperto con le virgolette non è chiuso.',
        'unclosed_name' => 'Il nome aperto con una parentesi quadra non è chiuso.',
        'empty_name' => 'Tra le parentesi quadre non c’è un nome.',
        'bad_date' => 'Questa data non esiste: :date.',
        'unexpected' => '«:token» non era previsto qui.',
        'unexpected_end' => 'La formula finisce troppo presto.',
        'operator_case' => '«:word» non era previsto qui. Gli operatori si scrivono in maiuscolo: :operator.',
        'chained_comparison' => 'Si possono confrontare solo due valori alla volta. Unisci i confronti con AND o OR.',
    ],
    'arguments' => [
        'too_few' => 'Alla funzione :function mancano argomenti: almeno :min, forniti: :given.',
        'too_many' => 'La funzione :function accetta meno argomenti: al massimo :max, forniti: :given.',
    ],
    'formula' => [
        'too_big' => 'La formula ha più di :limit parti.',
        'too_deep' => 'La formula è annidata per più di :limit livelli.',
        'too_long' => 'La formula salvata supera i :limit byte.',
    ],
    'ir' => [
        'invalid' => 'La formula salvata è danneggiata in «:path».',
        'version' => 'La formula è salvata in una versione del formato che qui non si può leggere; si legge la versione :supported.',
    ],
    'check' => [
        'unknown_field' => 'Il campo «:name» non esiste.',
        'unknown_variable' => 'Qui l’elemento «:name» non esiste: nessun ciclo intorno a questo punto ha questo nome.',
        'unknown_member' => 'Il record collegato non ha il campo «:name».',
        'not_a_record' => '«.:name» richiede un record collegato prima del punto, trovato: :actual.',
        'unknown_function' => 'La funzione «:name» non esiste.',
        'permission' => 'La funzione :function richiede il permesso «:permission».',
        'operand' => 'L’operatore «:operator» richiede: :expected, trovato: :actual.',
        'argument' => 'L’argomento :argument della funzione :function richiede: :expected, trovato: :actual.',
        'compare' => '«:operator» non può confrontare questi tipi: :left e :right.',
        'condition' => 'Una condizione deve dare sì o no, trovato: :actual.',
        'branches' => 'I due risultati di una condizione devono essere dello stesso tipo, trovato: :then e :otherwise.',
        'not_a_list' => 'Un ciclo richiede un elenco, trovato: :actual.',
        'depth' => 'La formula è annidata oltre i :limit livelli consentiti.',
        'deep_list' => 'Gli elenchi sono annidati oltre :limit livelli.',
    ],
    'value' => [
        'empty' => 'Qui un valore è vuoto, ma la formula richiede: :expected.',
        'empty_field' => 'Il campo «:field» è vuoto, ma la formula richiede: :expected.',
        'wrong_type' => 'La formula richiede: :expected, trovato: :actual.',
        'bad_field' => 'Il campo «:field» contiene un valore non adatto al suo tipo: :expected.',
        'bad_return' => 'La funzione :function ha restituito un valore non adatto al suo tipo: :expected.',
        'compare' => 'Non si possono confrontare: :left e :right.',
        'too_large' => 'Un numero è troppo grande: ha più di :digits cifre prima della virgola.',
        'division_by_zero' => 'Divisione per zero.',
        'exponent' => 'La potenza deve essere un numero intero tra -:limit e :limit.',
        'whole_days' => 'A una data si possono aggiungere o togliere solo giorni interi.',
        'out_of_range' => 'L’argomento :argument della funzione :function deve essere un numero intero da :min a :max.',
        'negative_root' => 'Un numero negativo non ha radice quadrata.',
        'date_out_of_range' => 'La data è fuori dagli anni da 1 a 9999.',
        'no_values' => 'La funzione :function non ha valori su cui lavorare.',
        'search_too_long' => 'La funzione :function può cercare al massimo :limit caratteri.',
    ],
    'limit' => [
        'steps' => 'La formula è stata fermata: più di :limit passi.',
        'time' => 'La formula è stata fermata: è durata più di :limit ms.',
        'memory' => 'La formula è stata fermata: i suoi valori hanno occupato più di :limit byte.',
        'recursion' => 'La formula è stata fermata: più di :limit formule erano in esecuzione una dentro l’altra.',
    ],
];
