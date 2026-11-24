<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Tracker',
    'my_tasks' => 'Le mie attività',
    'hours_short' => 'h',
    'thread_empty' => 'Ancora niente.',

    'projects' => [
        'title' => 'Progetti',
        'add' => 'Aggiungi progetto',
        'fields' => [
            'name' => 'Nome',
            'key' => 'Chiave',
            'key_hint' => 'Da due a dieci maiuscole latine o cifre, a partire da una lettera: le attività sono numerate ATR-1, ATR-2 …',
            'description' => 'Descrizione',
            'lead' => 'Responsabile',
            'members' => 'Membri',
            'members_hint' => 'Gli amministratori che lavorano al progetto. Un’attività può essere assegnata solo a loro e al responsabile; lascia vuoto per consentire tutti.',
            'archived' => 'Archiviato',
            'archived_hint' => 'Un progetto archiviato non riceve nuove attività; le sue attività restano.',
        ],
    ],

    'tasks' => [
        'title' => 'Attività',
        'add' => 'Aggiungi attività',
        'tabs' => [
            'params' => 'Attività',
            'files' => 'File',
            'activity' => 'Commenti e cronologia',
        ],
        'fields' => [
            'code' => 'Attività',
            'project' => 'Progetto',
            'type' => 'Tipo',
            'title' => 'Titolo',
            'description' => 'Descrizione',
            'status' => 'Stato',
            'priority' => 'Priorità',
            'assignee' => 'Assegnatario',
            'parent' => 'Attività padre',
            'parent_hint' => 'Una sottoattività di un’attività, o un’attività di un’epic, nello stesso progetto.',
            'labels' => 'Etichette',
            'add_label' => 'Aggiungi etichetta',
            'due_date' => 'Scadenza',
            'estimate' => 'Stima',
            'attachments' => 'Allegati',
            'new_comment' => 'Nuovo commento',
            'new_comment_hint' => 'Viene aggiunto all’attività quando la salvi.',
            'thread' => 'Commenti e cronologia',
        ],
        'filters' => [
            'label' => 'Etichetta',
            'overdue' => 'In ritardo',
            'mine' => 'Le mie attività',
        ],
    ],

    'comments' => [
        'title' => 'Commenti delle attività',
        'fields' => [
            'body' => 'Commento',
            'task' => 'Attività',
            'author' => 'Autore',
            'created' => 'Scritto',
        ],
    ],

    'types' => [
        'task' => 'Attività',
        'bug' => 'Bug',
        'story' => 'Storia',
        'epic' => 'Epic',
    ],

    'priorities' => [
        'lowest' => 'Più bassa',
        'low' => 'Bassa',
        'medium' => 'Media',
        'high' => 'Alta',
        'highest' => 'Più alta',
    ],

    'statuses' => [
        'todo' => 'Da fare',
        'in_progress' => 'In corso',
        'review' => 'In revisione',
        'done' => 'Fatto',
    ],

    'kinds' => [
        'comment' => 'Commento',
        'created' => 'Creata',
        'status' => 'Stato',
        'priority' => 'Priorità',
        'assignee' => 'Assegnatario',
    ],

    'events' => [
        'created' => 'L’attività è stata creata.',
        'status' => 'Stato: :old → :new',
        'priority' => 'Priorità: :old → :new',
        'assignee' => 'Assegnatario: :old → :new',
    ],

    'errors' => [
        'archived' => 'Il progetto è archiviato: non si possono spostare attività al suo interno.',
        'not_in_project' => 'Questo amministratore non lavora al progetto.',
        'epic_has_parent' => 'Un’epic non ha un’attività padre.',
        'parent_other_project' => 'L’attività padre deve essere dello stesso progetto.',
        'parent_loop' => 'Un’attività non può essere figlia di se stessa o di una sua sottoattività.',
    ],

    'settings' => [
        'description' => 'Gli stati delle attività e le email che li riguardano.',
        'statuses' => 'Stati',
        'statuses_hint' => 'In ordine: una nuova attività riceve il primo, l’ultimo significa «fatto». Vuoto: :default. Se rinomini uno stato qui, le attività mantengono il vecchio nome: cambialo anche nelle attività.',
        'add_status' => 'Aggiungi stato',
        'notify_assigned' => 'Scrivi all’assegnatario',
        'notify_assigned_hint' => 'Un’email quando un’attività viene assegnata a un amministratore.',
        'notify_status' => 'Scrivi di un nuovo stato',
        'notify_status_hint' => 'Un’email all’assegnatario quando cambia lo stato della sua attività.',
    ],

    'mail' => [
        'assigned_subject' => ':code ti è stata assegnata: :title',
        'assigned_line' => 'L’attività :code «:title» ti è stata assegnata.',
        'status_subject' => ':code ora è «:status»',
        'status_line' => 'Lo stato di :code «:title» è cambiato: :old → :new.',
    ],
];
