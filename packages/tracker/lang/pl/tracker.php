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
            'resolution' => 'Rozwiązanie',
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

    'terms' => [
        'types' => 'Typy zadań',
        'priorities' => 'Priorytety',
        'statuses' => 'Statusy',
        'resolutions' => 'Rozwiązania',
        'fields' => [
            'code' => 'Kod',
            'code_hint' => 'Małe litery, cyfry i _. Zadania zachowują kod, więc nie można go później zmienić.',
            'name' => 'Nazwa',
            'name_hint' => 'Puste: wbudowana nazwa, w języku każdego administratora.',
            'sort' => 'Kolejność',
            'default' => 'Domyślny',
            'default_hint' => 'Nowe zadanie dostaje ten. Bez oznaczenia — pierwszy.',
            'category' => 'Kategoria',
            'symbol' => 'Symbol',
            'symbol_hint' => 'Wyświetlany przed nazwą, aby priorytet nie różnił się tylko kolorem.',
            'top_level' => 'Bez zadania nadrzędnego',
            'top_level_hint' => 'Zadania tego typu stoją na samej górze, jak epik.',
            'needs_parent' => 'Wymaga zadania nadrzędnego',
            'needs_parent_hint' => 'Zadania tego typu zawsze należą do innego zadania, jak podzadanie.',
        ],
        'categories' => [
            'todo' => 'Do zrobienia',
            'active' => 'W toku',
            'done' => 'Gotowe',
        ],
    ],

    'types' => [
        'task' => 'Zadanie',
        'bug' => 'Błąd',
        'story' => 'Historia',
        'epic' => 'Epik',
        'subtask' => 'Podzadanie',
    ],

    'resolutions' => [
        'done' => 'Wykonane',
        'wont_do' => 'Nie będzie zrobione',
        'duplicate' => 'Duplikat',
        'cannot_reproduce' => 'Nie do odtworzenia',
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
        'type_has_no_parent' => 'Zadanie tego typu nie ma zadania nadrzędnego.',
        'needs_parent' => 'Zadanie tego typu wymaga zadania nadrzędnego.',
        'in_use' => 'Zadania nadal tego używają: najpierw je zmień.',
        'parent_other_project' => 'Zadanie nadrzędne musi być z tego samego projektu.',
        'parent_loop' => 'Zadanie nie może być dzieckiem samego siebie ani swojego podzadania.',
    ],

    'settings' => [
        'description' => 'Wiadomości o zadaniach.',
        'notify_assigned' => 'Pisz do wykonawcy',
        'notify_assigned_hint' => 'Wiadomość, gdy zadanie zostaje przydzielone administratorowi.',
        'notify_status' => 'Pisz o nowym statusie',
        'notify_status_hint' => 'Wiadomość do wykonawcy, gdy zmienia się status jego zadania.',
        'disabled_plugins' => 'Wyłączone wtyczki',
        'disabled_plugins_hint' => 'Zaznaczone wtyczki są ignorowane; nowa wtyczka działa, dopóki jej nie zaznaczysz.',
    ],

    'mail' => [
        'assigned_subject' => 'Przydzielono Ci :code: :title',
        'assigned_line' => 'Przydzielono Ci zadanie :code „:title”.',
        'status_subject' => ':code ma teraz status „:status”',
        'status_line' => 'Status zadania :code „:title” zmienił się: :old → :new.',
    ],
];
