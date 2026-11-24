<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Integrations',
    'title_log' => 'Exchange log',

    'status' => [
        'ok' => 'Done',
        'retry' => 'Failed, will retry',
        'failed' => 'Failed',
    ],
    'direction' => [
        'out' => 'From the site',
        'in' => 'To the site',
    ],
    'fields' => [
        'when' => 'When',
        'integration' => 'Integration',
        'direction' => 'Direction',
        'action' => 'Action',
        'subject' => 'About',
        'status' => 'Result',
        'message' => 'What was done',
        'error' => 'Error',
        'attempt' => 'Attempt',
        'duration' => 'Time, ms',
    ],

    'settings' => [
        'description' => 'Who is told when a connector (1C, Bitrix24) gives up after its last attempt, and how long the exchange log is kept.',
        'alert_emails' => 'Alert e-mails',
        'alert_emails_hint' => 'One or more addresses, separated by commas or new lines. Empty: every active superadministrator. One letter per integration per hour at most.',
        'retention_days' => 'Keep the log for, days',
        'retention_days_hint' => 'Older lines are deleted every night.',
    ],

    'alert' => [
        'subject' => ':site: integration :integration failed',
        'what' => 'Integration :integration could not do :action and has stopped trying.',
        'subject_line' => 'About: :subject',
        'error' => 'Error: :error',
        'attempts' => 'Attempts made: :count',
    ],
];
