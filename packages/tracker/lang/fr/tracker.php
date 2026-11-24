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

    'types' => [
        'task' => 'Tâche',
        'bug' => 'Bogue',
        'story' => 'Récit',
        'epic' => 'Epic',
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
        'epic_has_parent' => 'Une epic n’a pas de tâche parente.',
        'parent_other_project' => 'La tâche parente doit appartenir au même projet.',
        'parent_loop' => 'Une tâche ne peut pas être l’enfant d’elle-même ni de l’une de ses sous-tâches.',
    ],

    'settings' => [
        'description' => 'Les statuts des tâches et les courriels à ce sujet.',
        'statuses' => 'Statuts',
        'statuses_hint' => 'Dans l’ordre : une nouvelle tâche reçoit le premier, le dernier signifie « terminé ». Vide : :default. Si vous renommez un statut ici, les tâches gardent l’ancien nom : changez-le aussi dans les tâches.',
        'add_status' => 'Ajouter un statut',
        'notify_assigned' => 'Écrire à l’assigné',
        'notify_assigned_hint' => 'Un courriel quand une tâche est confiée à un administrateur.',
        'notify_status' => 'Écrire à propos d’un nouveau statut',
        'notify_status_hint' => 'Un courriel à l’assigné quand le statut de sa tâche change.',
    ],

    'mail' => [
        'assigned_subject' => ':code vous a été confiée : :title',
        'assigned_line' => 'La tâche :code « :title » vous a été confiée.',
        'status_subject' => ':code est maintenant « :status »',
        'status_line' => 'Le statut de :code « :title » a changé : :old → :new.',
    ],
];
