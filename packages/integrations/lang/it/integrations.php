<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Integrazioni',
    'title_log' => 'Registro degli scambi',

    'status' => [
        'ok' => 'Fatto',
        'retry' => 'Non riuscito, nuovo tentativo',
        'failed' => 'Non riuscito',
    ],
    'direction' => [
        'out' => 'Dal sito',
        'in' => 'Verso il sito',
    ],
    'fields' => [
        'when' => 'Quando',
        'integration' => 'Integrazione',
        'direction' => 'Direzione',
        'action' => 'Azione',
        'subject' => 'Oggetto',
        'status' => 'Esito',
        'message' => 'Cosa è stato fatto',
        'error' => 'Errore',
        'attempt' => 'Tentativo',
        'duration' => 'Durata, ms',
    ],

    'settings' => [
        'description' => 'Chi viene avvisato quando un connettore (1C, Bitrix24) si arrende dopo l\'ultimo tentativo e per quanto tempo viene conservato il registro degli scambi.',
        'alert_emails' => 'E-mail di avviso',
        'alert_emails_hint' => 'Uno o più indirizzi, separati da virgole o a capo. Vuoto: tutti i superamministratori attivi. Al massimo una e-mail per integrazione all\'ora.',
        'retention_days' => 'Conserva il registro, giorni',
        'retention_days_hint' => 'Le voci più vecchie vengono eliminate ogni notte.',
    ],

    'alert' => [
        'subject' => ':site: integrazione :integration non riuscita',
        'what' => 'L\'integrazione :integration non è riuscita a eseguire l\'azione :action e ha smesso di provare.',
        'subject_line' => 'Oggetto: :subject',
        'error' => 'Errore: :error',
        'attempts' => 'Tentativi effettuati: :count',
    ],
];
