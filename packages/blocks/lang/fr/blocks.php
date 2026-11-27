<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'modules' => [
        'blocks' => 'Blocs',
    ],
    'create' => [
        'block' => 'Ajouter un bloc',
    ],
    'fields' => [
        'name' => 'Nom',
        'content' => 'Contenu',
        'updated' => 'Modifié',
        'code_hint' => 'Court, en lettres latines. Un modèle affiche le bloc avec @block(\'code\'), un texte avec le bouton + de l’éditeur.',
    ],
    'embed' => 'Bloc',
    'builder' => [
        'label' => 'Bloc réutilisable',
    ],
];
