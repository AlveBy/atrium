<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Integrationen',
    'title_log' => 'Austauschprotokoll',

    'status' => [
        'ok' => 'Erledigt',
        'retry' => 'Fehlgeschlagen, neuer Versuch folgt',
        'failed' => 'Fehlgeschlagen',
    ],
    'direction' => [
        'out' => 'Von der Website',
        'in' => 'Zur Website',
    ],
    'fields' => [
        'when' => 'Wann',
        'integration' => 'Integration',
        'direction' => 'Richtung',
        'action' => 'Aktion',
        'subject' => 'Betrifft',
        'status' => 'Ergebnis',
        'message' => 'Was getan wurde',
        'error' => 'Fehler',
        'attempt' => 'Versuch',
        'duration' => 'Dauer, ms',
    ],

    'settings' => [
        'description' => 'Wer benachrichtigt wird, wenn ein Konnektor (1C, Bitrix24) nach dem letzten Versuch aufgibt, und wie lange das Austauschprotokoll aufbewahrt wird.',
        'alert_emails' => 'Benachrichtigungs-E-Mails',
        'alert_emails_hint' => 'Eine oder mehrere Adressen, getrennt durch Kommas oder Zeilenumbrüche. Leer: alle aktiven Superadministratoren. Höchstens eine E-Mail pro Integration und Stunde.',
        'retention_days' => 'Protokoll aufbewahren, Tage',
        'retention_days_hint' => 'Ältere Einträge werden jede Nacht gelöscht.',
    ],

    'alert' => [
        'subject' => ':site: Integration :integration fehlgeschlagen',
        'what' => 'Integration :integration konnte die Aktion :action nicht ausführen und versucht es nicht mehr.',
        'subject_line' => 'Betrifft: :subject',
        'error' => 'Fehler: :error',
        'attempts' => 'Bisherige Versuche: :count',
    ],
];
