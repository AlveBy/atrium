<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Integracje',
    'title_log' => 'Dziennik wymiany',

    'status' => [
        'ok' => 'Gotowe',
        'retry' => 'Błąd, ponowimy próbę',
        'failed' => 'Błąd',
    ],
    'direction' => [
        'out' => 'Z witryny',
        'in' => 'Do witryny',
    ],
    'fields' => [
        'when' => 'Kiedy',
        'integration' => 'Integracja',
        'direction' => 'Kierunek',
        'action' => 'Działanie',
        'subject' => 'Dotyczy',
        'status' => 'Wynik',
        'message' => 'Co zrobiono',
        'error' => 'Błąd',
        'attempt' => 'Próba',
        'duration' => 'Czas, ms',
    ],

    'settings' => [
        'description' => 'Kogo powiadomić, gdy konektor (1C, Bitrix24) podda się po ostatniej próbie, i jak długo przechowywać dziennik wymiany.',
        'alert_emails' => 'Adresy e-mail alertów',
        'alert_emails_hint' => 'Jeden lub więcej adresów, oddzielonych przecinkami lub nowymi wierszami. Puste: wszyscy aktywni superadministratorzy. Najwyżej jedna wiadomość na integrację w ciągu godziny.',
        'retention_days' => 'Przechowuj dziennik, dni',
        'retention_days_hint' => 'Starsze wpisy są usuwane każdej nocy.',
    ],

    'alert' => [
        'subject' => ':site: błąd integracji :integration',
        'what' => 'Integracja :integration nie mogła wykonać działania :action i przestała próbować.',
        'subject_line' => 'Dotyczy: :subject',
        'error' => 'Błąd: :error',
        'attempts' => 'Wykonane próby: :count',
    ],
];
