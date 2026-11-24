<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Tracker',
    'my_tasks' => 'Meine Aufgaben',
    'hours_short' => 'Std.',
    'thread_empty' => 'Noch nichts da.',

    'projects' => [
        'title' => 'Projekte',
        'add' => 'Projekt hinzufügen',
        'fields' => [
            'name' => 'Name',
            'key' => 'Kürzel',
            'key_hint' => 'Zwei bis zehn lateinische Großbuchstaben oder Ziffern, beginnend mit einem Buchstaben: Die Aufgaben heißen ATR-1, ATR-2 …',
            'description' => 'Beschreibung',
            'lead' => 'Leitung',
            'members' => 'Mitglieder',
            'members_hint' => 'Administratoren, die im Projekt arbeiten. Aufgaben können nur ihnen und der Leitung zugewiesen werden; leer lassen, um alle zuzulassen.',
            'archived' => 'Archiviert',
            'archived_hint' => 'In ein archiviertes Projekt kommen keine neuen Aufgaben; seine Aufgaben bleiben.',
        ],
    ],

    'tasks' => [
        'title' => 'Aufgaben',
        'add' => 'Aufgabe hinzufügen',
        'tabs' => [
            'params' => 'Aufgabe',
            'files' => 'Dateien',
            'activity' => 'Kommentare und Verlauf',
        ],
        'fields' => [
            'code' => 'Aufgabe',
            'project' => 'Projekt',
            'type' => 'Typ',
            'title' => 'Titel',
            'description' => 'Beschreibung',
            'status' => 'Status',
            'priority' => 'Priorität',
            'assignee' => 'Bearbeiter',
            'parent' => 'Übergeordnete Aufgabe',
            'parent_hint' => 'Eine Unteraufgabe einer Aufgabe oder eine Aufgabe eines Epics, im selben Projekt.',
            'labels' => 'Labels',
            'add_label' => 'Label hinzufügen',
            'due_date' => 'Fällig am',
            'estimate' => 'Schätzung',
            'attachments' => 'Anhänge',
            'new_comment' => 'Neuer Kommentar',
            'new_comment_hint' => 'Wird der Aufgabe beim Speichern hinzugefügt.',
            'thread' => 'Kommentare und Verlauf',
        ],
        'filters' => [
            'label' => 'Label',
            'overdue' => 'Überfällig',
            'mine' => 'Meine Aufgaben',
        ],
    ],

    'comments' => [
        'title' => 'Aufgabenkommentare',
        'fields' => [
            'body' => 'Kommentar',
            'task' => 'Aufgabe',
            'author' => 'Autor',
            'created' => 'Geschrieben',
        ],
    ],

    'types' => [
        'task' => 'Aufgabe',
        'bug' => 'Fehler',
        'story' => 'Story',
        'epic' => 'Epic',
    ],

    'priorities' => [
        'lowest' => 'Niedrigste',
        'low' => 'Niedrig',
        'medium' => 'Mittel',
        'high' => 'Hoch',
        'highest' => 'Höchste',
    ],

    'statuses' => [
        'todo' => 'Zu erledigen',
        'in_progress' => 'In Arbeit',
        'review' => 'In Prüfung',
        'done' => 'Erledigt',
    ],

    'kinds' => [
        'comment' => 'Kommentar',
        'created' => 'Erstellt',
        'status' => 'Status',
        'priority' => 'Priorität',
        'assignee' => 'Bearbeiter',
    ],

    'events' => [
        'created' => 'Die Aufgabe wurde erstellt.',
        'status' => 'Status: :old → :new',
        'priority' => 'Priorität: :old → :new',
        'assignee' => 'Bearbeiter: :old → :new',
    ],

    'errors' => [
        'archived' => 'Das Projekt ist archiviert: Aufgaben können nicht hineinverschoben werden.',
        'not_in_project' => 'Dieser Administrator arbeitet nicht im Projekt.',
        'epic_has_parent' => 'Ein Epic hat keine übergeordnete Aufgabe.',
        'parent_other_project' => 'Die übergeordnete Aufgabe muss im selben Projekt liegen.',
        'parent_loop' => 'Eine Aufgabe kann nicht ihr eigenes Kind oder das ihrer Unteraufgaben sein.',
    ],

    'settings' => [
        'description' => 'Die Status der Aufgaben und die E-Mails dazu.',
        'statuses' => 'Status',
        'statuses_hint' => 'In Reihenfolge: Eine neue Aufgabe bekommt den ersten, der letzte bedeutet „erledigt“. Leer: :default. Wird ein Status hier umbenannt, behalten die Aufgaben den alten Namen – ändern Sie ihn auch in den Aufgaben.',
        'add_status' => 'Status hinzufügen',
        'notify_assigned' => 'Dem Bearbeiter schreiben',
        'notify_assigned_hint' => 'Eine E-Mail, wenn einem Administrator eine Aufgabe zugewiesen wird.',
        'notify_status' => 'Über neue Status schreiben',
        'notify_status_hint' => 'Eine E-Mail an den Bearbeiter, wenn sich der Status seiner Aufgabe ändert.',
    ],

    'mail' => [
        'assigned_subject' => ':code wurde Ihnen zugewiesen: :title',
        'assigned_line' => 'Die Aufgabe :code „:title“ wurde Ihnen zugewiesen.',
        'status_subject' => ':code ist jetzt „:status“',
        'status_line' => 'Der Status von :code „:title“ hat sich geändert: :old → :new.',
    ],
];
