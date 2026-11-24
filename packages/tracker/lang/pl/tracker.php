<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Tracker',
    'my_tasks' => 'Moje zadania',
    'hours_short' => 'godz.',
    'thread_empty' => 'Na razie nic tu nie ma.',

    'projects' => [
        'title' => 'Projekty',
        'add' => 'Dodaj projekt',
        'fields' => [
            'name' => 'Nazwa',
            'key' => 'Klucz',
            'key_hint' => 'Od dwóch do dziesięciu wielkich liter łacińskich lub cyfr, zaczynając od litery: zadania są numerowane ATR-1, ATR-2 …',
            'description' => 'Opis',
            'lead' => 'Kierownik',
            'members' => 'Członkowie',
            'members_hint' => 'Administratorzy pracujący w projekcie. Zadanie można przydzielić tylko im i kierownikowi; zostaw puste, aby zezwolić wszystkim.',
            'archived' => 'Zarchiwizowany',
            'archived_hint' => 'Do zarchiwizowanego projektu nie trafiają nowe zadania; jego zadania zostają.',
        ],
    ],

    'tasks' => [
        'title' => 'Zadania',
        'add' => 'Dodaj zadanie',
        'tabs' => [
            'params' => 'Zadanie',
            'files' => 'Pliki',
            'activity' => 'Komentarze i historia',
        ],
        'fields' => [
            'code' => 'Zadanie',
            'project' => 'Projekt',
            'type' => 'Typ',
            'title' => 'Tytuł',
            'description' => 'Opis',
            'status' => 'Status',
            'priority' => 'Priorytet',
            'assignee' => 'Wykonawca',
            'parent' => 'Zadanie nadrzędne',
            'parent_hint' => 'Podzadanie innego zadania albo zadanie epiku, w tym samym projekcie.',
            'labels' => 'Etykiety',
            'add_label' => 'Dodaj etykietę',
            'due_date' => 'Termin',
            'estimate' => 'Szacunek',
            'attachments' => 'Załączniki',
            'new_comment' => 'Nowy komentarz',
            'new_comment_hint' => 'Zostanie dodany do zadania po zapisaniu.',
            'thread' => 'Komentarze i historia',
        ],
        'filters' => [
            'label' => 'Etykieta',
            'overdue' => 'Zaległe',
            'mine' => 'Moje zadania',
        ],
    ],

    'comments' => [
        'title' => 'Komentarze do zadań',
        'fields' => [
            'body' => 'Komentarz',
            'task' => 'Zadanie',
            'author' => 'Autor',
            'created' => 'Napisano',
        ],
    ],

    'types' => [
        'task' => 'Zadanie',
        'bug' => 'Błąd',
        'story' => 'Historia',
        'epic' => 'Epik',
    ],

    'priorities' => [
        'lowest' => 'Najniższy',
        'low' => 'Niski',
        'medium' => 'Średni',
        'high' => 'Wysoki',
        'highest' => 'Najwyższy',
    ],

    'statuses' => [
        'todo' => 'Do zrobienia',
        'in_progress' => 'W toku',
        'review' => 'W przeglądzie',
        'done' => 'Gotowe',
    ],

    'kinds' => [
        'comment' => 'Komentarz',
        'created' => 'Utworzono',
        'status' => 'Status',
        'priority' => 'Priorytet',
        'assignee' => 'Wykonawca',
    ],

    'events' => [
        'created' => 'Zadanie zostało utworzone.',
        'status' => 'Status: :old → :new',
        'priority' => 'Priorytet: :old → :new',
        'assignee' => 'Wykonawca: :old → :new',
    ],

    'errors' => [
        'archived' => 'Projekt jest zarchiwizowany: nie można do niego przenosić zadań.',
        'not_in_project' => 'Ten administrator nie pracuje w projekcie.',
        'epic_has_parent' => 'Epik nie ma zadania nadrzędnego.',
        'parent_other_project' => 'Zadanie nadrzędne musi być z tego samego projektu.',
        'parent_loop' => 'Zadanie nie może być dzieckiem samego siebie ani swojego podzadania.',
    ],

    'settings' => [
        'description' => 'Statusy zadań i wiadomości o nich.',
        'statuses' => 'Statusy',
        'statuses_hint' => 'Po kolei: nowe zadanie dostaje pierwszy, ostatni oznacza „gotowe”. Puste: :default. Jeśli zmienisz tu nazwę statusu, zadania zachowają starą — zmień ją także w zadaniach.',
        'add_status' => 'Dodaj status',
        'notify_assigned' => 'Pisz do wykonawcy',
        'notify_assigned_hint' => 'Wiadomość, gdy zadanie zostaje przydzielone administratorowi.',
        'notify_status' => 'Pisz o nowym statusie',
        'notify_status_hint' => 'Wiadomość do wykonawcy, gdy zmienia się status jego zadania.',
    ],

    'mail' => [
        'assigned_subject' => 'Przydzielono Ci :code: :title',
        'assigned_line' => 'Przydzielono Ci zadanie :code „:title”.',
        'status_subject' => ':code ma teraz status „:status”',
        'status_line' => 'Status zadania :code „:title” zmienił się: :old → :new.',
    ],
];
