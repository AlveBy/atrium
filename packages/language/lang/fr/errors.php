<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

// Machine-translated from English; needs review by a native speaker.

return [
    'at' => 'Ligne :line, colonne :column : :message',
    'types' => [
        'number' => 'nombre',
        'text' => 'texte',
        'boolean' => 'oui/non',
        'date' => 'date',
        'empty' => 'rien',
        'any' => 'toute valeur',
        'list' => 'liste : :of',
        'record' => 'enregistrement',
        'or' => ' ou ',
    ],
    'syntax' => [
        'encoding' => 'La formule n’est pas un texte UTF-8 valide.',
        'too_long' => 'La formule dépasse :limit caractères.',
        'too_deep' => 'Les parenthèses et les fonctions sont imbriquées sur plus de :limit niveaux.',
        'unknown_character' => 'Caractère inconnu « :character ».',
        'unclosed_string' => 'Le texte ouvert par un guillemet n’est pas fermé.',
        'unclosed_name' => 'Le nom ouvert par un crochet n’est pas fermé.',
        'empty_name' => 'Il n’y a pas de nom entre les crochets.',
        'bad_date' => 'Cette date n’existe pas : :date.',
        'unexpected' => '« :token » n’était pas attendu ici.',
        'unexpected_end' => 'La formule se termine trop tôt.',
        'operator_case' => '« :word » n’était pas attendu ici. Les opérateurs s’écrivent en majuscules : :operator.',
        'chained_comparison' => 'On ne compare que deux valeurs à la fois. Reliez les comparaisons avec AND ou OR.',
    ],
    'arguments' => [
        'too_few' => 'Il manque des arguments à la fonction :function : au moins :min, fournis : :given.',
        'too_many' => 'La fonction :function prend moins d’arguments : au plus :max, fournis : :given.',
    ],
    'formula' => [
        'too_big' => 'La formule contient plus de :limit éléments.',
        'too_deep' => 'La formule est imbriquée sur plus de :limit niveaux.',
        'too_long' => 'La formule enregistrée dépasse :limit octets.',
    ],
    'ir' => [
        'invalid' => 'La formule enregistrée est endommagée à l’emplacement « :path ».',
        'version' => 'La formule est enregistrée dans une version du format illisible ici ; la version lue est :supported.',
    ],
    'check' => [
        'unknown_field' => 'Il n’y a pas de champ « :name ».',
        'unknown_variable' => 'Il n’y a pas d’élément « :name » ici : aucune boucle autour de cet endroit ne porte ce nom.',
        'unknown_member' => 'L’enregistrement lié n’a pas de champ « :name ».',
        'not_a_record' => '« .:name » demande un enregistrement lié avant le point, trouvé : :actual.',
        'unknown_function' => 'Il n’y a pas de fonction « :name ».',
        'permission' => 'La fonction :function exige l’autorisation « :permission ».',
        'operand' => 'L’opérateur « :operator » attend : :expected, trouvé : :actual.',
        'argument' => 'L’argument :argument de la fonction :function attend : :expected, trouvé : :actual.',
        'compare' => '« :operator » ne peut pas comparer ces types : :left et :right.',
        'condition' => 'Une condition doit répondre oui ou non, trouvé : :actual.',
        'branches' => 'Les deux résultats d’une condition doivent être du même type, trouvé : :then et :otherwise.',
        'not_a_list' => 'Une boucle demande une liste, trouvé : :actual.',
        'depth' => 'La formule est imbriquée au-delà des :limit niveaux autorisés.',
        'deep_list' => 'Les listes sont imbriquées sur plus de :limit niveaux.',
    ],
    'value' => [
        'empty' => 'Une valeur est vide ici, alors que la formule attend : :expected.',
        'empty_field' => 'Le champ « :field » est vide, alors que la formule attend : :expected.',
        'wrong_type' => 'La formule attend : :expected, trouvé : :actual.',
        'bad_field' => 'Le champ « :field » contient une valeur qui ne correspond pas à son type : :expected.',
        'bad_return' => 'La fonction :function a renvoyé une valeur qui ne correspond pas à son type : :expected.',
        'compare' => 'Impossible de comparer : :left et :right.',
        'too_large' => 'Un nombre est trop grand : plus de :digits chiffres avant la virgule.',
        'division_by_zero' => 'Division par zéro.',
        'exponent' => 'La puissance doit être un nombre entier entre -:limit et :limit.',
        'whole_days' => 'On ne peut ajouter à une date, ou en retirer, que des jours entiers.',
        'out_of_range' => 'L’argument :argument de la fonction :function doit être un nombre entier de :min à :max.',
        'negative_root' => 'Un nombre négatif n’a pas de racine carrée.',
        'date_out_of_range' => 'La date sort des années 1 à 9999.',
        'no_values' => 'La fonction :function n’a aucune valeur à traiter.',
        'search_too_long' => 'La fonction :function peut chercher au plus :limit caractères.',
    ],
    'limit' => [
        'steps' => 'La formule a été arrêtée : plus de :limit étapes.',
        'time' => 'La formule a été arrêtée : elle a duré plus de :limit ms.',
        'memory' => 'La formule a été arrêtée : ses valeurs ont occupé plus de :limit octets.',
        'recursion' => 'La formule a été arrêtée : plus de :limit formules s’exécutaient les unes dans les autres.',
    ],
];
