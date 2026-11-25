<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Suivi',
    'my_tasks' => 'Mes tâches',
    'hours_short' => 'h',
    'thread_empty' => 'Rien pour l’instant.',

    'projects' => [
        'title' => 'Projets',
        'add' => 'Ajouter un projet',
        'fields' => [
            'name' => 'Nom',
            'key' => 'Clé',
            'key_hint' => 'De deux à dix majuscules latines ou chiffres, en commençant par une lettre : les tâches sont numérotées ATR-1, ATR-2 …',
            'description' => 'Description',
            'lead' => 'Responsable',
            'members' => 'Membres',
            'members_hint' => 'Les administrateurs qui travaillent sur le projet. Une tâche ne peut être confiée qu’à eux et au responsable ; laissez vide pour autoriser tout le monde.',
            'archived' => 'Archivé',
            'archived_hint' => 'Un projet archivé ne reçoit plus de nouvelles tâches ; ses tâches restent.',
        ],
    ],

    'tasks' => [
        'title' => 'Tâches',
        'add' => 'Ajouter une tâche',
        'tabs' => [
            'params' => 'Tâche',
            'files' => 'Fichiers',
            'activity' => 'Commentaires et historique',
        ],
        'fields' => [
            'code' => 'Tâche',
            'project' => 'Projet',
            'type' => 'Type',
            'title' => 'Titre',
            'description' => 'Description',
            'status' => 'Statut',
            'priority' => 'Priorité',
            'resolution' => 'Résolution',
            'assignee' => 'Assigné à',
            'parent' => 'Tâche parente',
            'parent_hint' => 'Une sous-tâche d’une tâche, ou une tâche d’une epic, dans le même projet.',
            'labels' => 'Étiquettes',
            'add_label' => 'Ajouter une étiquette',
            'due_date' => 'Échéance',
            'estimate' => 'Estimation',
            'attachments' => 'Pièces jointes',
            'new_comment' => 'Nouveau commentaire',
            'new_comment_hint' => 'Ajouté à la tâche à l’enregistrement.',
            'thread' => 'Commentaires et historique',
        ],
        'filters' => [
            'label' => 'Étiquette',
            'overdue' => 'En retard',
            'mine' => 'Mes tâches',
        ],
    ],

    'comments' => [
        'title' => 'Commentaires des tâches',
        'fields' => [
            'body' => 'Commentaire',
            'task' => 'Tâche',
            'author' => 'Auteur',
            'created' => 'Écrit',
        ],
    ],

    'terms' => [
        'types' => 'Types de tâche',
        'priorities' => 'Priorités',
        'statuses' => 'Statuts',
        'resolutions' => 'Résolutions',
        'fields' => [
            'code' => 'Code',
            'code_hint' => 'Lettres minuscules, chiffres et _. Les tâches gardent le code, il ne peut donc plus être changé ensuite.',
            'name' => 'Nom',
            'name_hint' => 'Vide : le nom intégré, dans la langue de chaque administrateur.',
            'sort' => 'Ordre',
            'default' => 'Par défaut',
            'default_hint' => 'Une nouvelle tâche reçoit celui-ci. Sans marque, le premier.',
            'category' => 'Catégorie',
            'symbol' => 'Symbole',
            'symbol_hint' => 'Affiché avant le nom, pour que la priorité ne se distingue pas seulement par la couleur.',
            'top_level' => 'Sans parent',
            'top_level_hint' => 'Les tâches de ce type se tiennent tout en haut, comme une epic.',
            'needs_parent' => 'Parent requis',
            'needs_parent_hint' => 'Les tâches de ce type appartiennent toujours à une autre tâche, comme une sous-tâche.',
        ],
        'categories' => [
            'todo' => 'À faire',
            'active' => 'En cours',
            'done' => 'Terminé',
        ],
    ],

    'types' => [
        'task' => 'Tâche',
        'bug' => 'Bogue',
        'story' => 'Récit',
        'epic' => 'Epic',
        'subtask' => 'Sous-tâche',
    ],

    'resolutions' => [
        'done' => 'Terminée',
        'wont_do' => 'Ne sera pas traitée',
        'duplicate' => 'Doublon',
        'cannot_reproduce' => 'Non reproductible',
    ],

    'priorities' => [
        'lowest' => 'La plus basse',
        'low' => 'Basse',
        'medium' => 'Moyenne',
        'high' => 'Haute',
        'highest' => 'La plus haute',
    ],

    'statuses' => [
        'todo' => 'À faire',
        'in_progress' => 'En cours',
        'review' => 'En revue',
        'done' => 'Terminé',
    ],

    'kinds' => [
        'comment' => 'Commentaire',
        'created' => 'Créée',
        'status' => 'Statut',
        'priority' => 'Priorité',
        'assignee' => 'Assigné à',
    ],

    'events' => [
        'created' => 'La tâche a été créée.',
        'status' => 'Statut : :old → :new',
        'priority' => 'Priorité : :old → :new',
        'assignee' => 'Assigné à : :old → :new',
    ],

    'errors' => [
        'archived' => 'Le projet est archivé : on ne peut pas y déplacer de tâches.',
        'not_in_project' => 'Cet administrateur ne travaille pas sur le projet.',
        'type_has_no_parent' => 'Une tâche de ce type n’a pas de tâche parente.',
        'needs_parent' => 'Une tâche de ce type a besoin d’une tâche parente.',
        'in_use' => 'Des tâches l’utilisent encore : modifiez-les d’abord.',
        'parent_other_project' => 'La tâche parente doit appartenir au même projet.',
        'parent_loop' => 'Une tâche ne peut pas être l’enfant d’elle-même ni de l’une de ses sous-tâches.',
    ],

    'settings' => [
        'description' => 'Les courriels sur les tâches.',
        'notify_assigned' => 'Écrire à l’assigné',
        'notify_assigned_hint' => 'Un courriel quand une tâche est confiée à un administrateur.',
        'notify_status' => 'Écrire à propos d’un nouveau statut',
        'notify_status_hint' => 'Un courriel à l’assigné quand le statut de sa tâche change.',
        'disabled_plugins' => 'Extensions désactivées',
        'disabled_plugins_hint' => 'Les extensions cochées sont ignorées ; une nouvelle extension est active tant que vous ne la cochez pas.',
    ],

    'mail' => [
        'assigned_subject' => ':code vous a été confiée : :title',
        'assigned_line' => 'La tâche :code « :title » vous a été confiée.',
        'status_subject' => ':code est maintenant « :status »',
        'status_line' => 'Le statut de :code « :title » a changé : :old → :new.',
    ],
];
