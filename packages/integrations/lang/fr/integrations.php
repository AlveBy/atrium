<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Intégrations',
    'title_log' => 'Journal des échanges',

    'status' => [
        'ok' => 'Terminé',
        'retry' => 'Échec, nouvel essai prévu',
        'failed' => 'Échec',
    ],
    'direction' => [
        'out' => 'Depuis le site',
        'in' => 'Vers le site',
    ],
    'fields' => [
        'when' => 'Quand',
        'integration' => 'Intégration',
        'direction' => 'Sens',
        'action' => 'Action',
        'subject' => 'Concerne',
        'status' => 'Résultat',
        'message' => 'Ce qui a été fait',
        'error' => 'Erreur',
        'attempt' => 'Tentative',
        'duration' => 'Durée, ms',
    ],

    'settings' => [
        'description' => 'Qui est prévenu quand un connecteur (1C, Bitrix24) abandonne après sa dernière tentative, et combien de temps le journal des échanges est conservé.',
        'alert_emails' => 'E-mails d\'alerte',
        'alert_emails_hint' => 'Une ou plusieurs adresses, séparées par des virgules ou des retours à la ligne. Vide : tous les super-administrateurs actifs. Un seul e-mail par intégration et par heure au maximum.',
        'retention_days' => 'Conserver le journal, jours',
        'retention_days_hint' => 'Les entrées plus anciennes sont supprimées chaque nuit.',
    ],

    'alert' => [
        'subject' => ':site : échec de l\'intégration :integration',
        'what' => 'L\'intégration :integration n\'a pas pu effectuer l\'action :action et a cessé d\'essayer.',
        'subject_line' => 'Concerne : :subject',
        'error' => 'Erreur : :error',
        'attempts' => 'Tentatives effectuées : :count',
    ],
];
