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
            'resolution' => 'Risoluzione',
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

    'terms' => [
        'types' => 'Tipi di attività',
        'priorities' => 'Priorità',
        'statuses' => 'Stati',
        'resolutions' => 'Risoluzioni',
        'fields' => [
            'code' => 'Codice',
            'code_hint' => 'Lettere minuscole, cifre e _. Le attività conservano il codice, quindi poi non si può cambiare.',
            'name' => 'Nome',
            'name_hint' => 'Vuoto: il nome predefinito, nella lingua di ciascun amministratore.',
            'sort' => 'Ordine',
            'default' => 'Predefinito',
            'default_hint' => 'Una nuova attività riceve questo. Senza segno, il primo.',
            'category' => 'Categoria',
            'symbol' => 'Simbolo',
            'symbol_hint' => 'Mostrato prima del nome, così la priorità non si distingue solo dal colore.',
            'top_level' => 'Senza padre',
            'top_level_hint' => 'Le attività di questo tipo stanno in cima, come un’epic.',
            'needs_parent' => 'Serve un padre',
            'needs_parent_hint' => 'Le attività di questo tipo appartengono sempre a un’altra attività, come una sottoattività.',
        ],
        'categories' => [
            'todo' => 'Da fare',
            'active' => 'In corso',
            'done' => 'Fatto',
        ],
    ],

    'types' => [
        'task' => 'Attività',
        'bug' => 'Bug',
        'story' => 'Storia',
        'epic' => 'Epic',
        'subtask' => 'Sottoattività',
    ],

    'resolutions' => [
        'done' => 'Fatta',
        'wont_do' => 'Non verrà fatta',
        'duplicate' => 'Duplicata',
        'cannot_reproduce' => 'Non riproducibile',
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
        'type_has_no_parent' => 'Un’attività di questo tipo non ha un’attività padre.',
        'needs_parent' => 'Un’attività di questo tipo ha bisogno di un’attività padre.',
        'in_use' => 'Ci sono ancora attività che lo usano: modificale prima.',
        'parent_other_project' => 'L’attività padre deve essere dello stesso progetto.',
        'parent_loop' => 'Un’attività non può essere figlia di se stessa o di una sua sottoattività.',
    ],

    'settings' => [
        'description' => 'Le email sulle attività.',
        'notify_assigned' => 'Scrivi all’assegnatario',
        'notify_assigned_hint' => 'Un’email quando un’attività viene assegnata a un amministratore.',
        'notify_status' => 'Scrivi di un nuovo stato',
        'notify_status_hint' => 'Un’email all’assegnatario quando cambia lo stato della sua attività.',
        'disabled_plugins' => 'Plugin disattivati',
        'disabled_plugins_hint' => 'I plugin selezionati vengono ignorati; un nuovo plugin è attivo finché non lo selezioni.',
    ],

    'mail' => [
        'assigned_subject' => ':code ti è stata assegnata: :title',
        'assigned_line' => 'L’attività :code «:title» ti è stata assegnata.',
        'status_subject' => ':code ora è «:status»',
        'status_line' => 'Lo stato di :code «:title» è cambiato: :old → :new.',
    ],
];
