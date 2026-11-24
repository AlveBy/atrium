<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => 'Bitrix24: moduli',

    'settings' => [
        'description' => 'Gli invii dei moduli arrivano nel tuo Bitrix24 come lead, e il widget chat di Bitrix24 viene mostrato sul sito. Crea in Bitrix24 un webhook in entrata (Risorse per sviluppatori → Altro → Webhook in entrata) con il permesso per il CRM e incolla qui il suo indirizzo.',
        'webhook' => 'Webhook in entrata',
        'webhook_hint' => 'L\'indirizzo mostrato da Bitrix24, simile a https://portal.bitrix24.com/rest/1/code/. Viene salvato cifrato e non viene più mostrato: per cambiarlo, incollane uno nuovo; lascia il campo vuoto per mantenere quello attuale.',
        'send_forms' => 'Invia i moduli a Bitrix24',
        'send_forms_hint' => 'Un modulo senza collegamento qui sotto viene inviato come lead, con i campi sistemati in base al loro tipo.',
        'widget_code' => 'Codice del widget chat',
        'widget_code_hint' => 'Il codice della linea aperta o del widget chat di Bitrix24 (CRM → Contact center → Chat online → Widget). Viene inserito alla fine di ogni pagina del sito; se è vuoto, il widget non c\'è.',
        'check' => 'Verifica la connessione',
        'connected' => 'Connesso: :portal, utente :user.',
    ],

    'fields' => [
        'form' => 'Modulo',
        'mode' => 'Invia come',
        'active' => 'Invia questo modulo a Bitrix24',
        'mapping' => 'Campi',
        'mapping_hint' => 'Una riga per campo: "campo del modulo = campo Bitrix24", ad esempio "phone = PHONE" o "message = COMMENTS". Un campo senza riga viene sistemato in base al suo tipo (un\'e-mail in EMAIL, un telefono in PHONE); ciò che non ha posto va nel commento. Le righe che iniziano con # vengono ignorate.',
    ],

    'modes' => [
        'lead' => 'Lead',
        'deal' => 'Trattativa con un contatto',
    ],

    'errors' => [
        'no_webhook' => 'Il webhook di Bitrix24 non è impostato: incollalo in Moduli → Bitrix24.',
        'unreachable' => 'Bitrix24 non risponde. Controlla l\'indirizzo del portale nel webhook.',
        'portal' => 'Bitrix24 ha rifiutato: :error',
        'mapping_line' => 'La riga :line non è nel formato "campo del modulo = campo Bitrix24".',
    ],

    'payload' => [
        'subject' => 'Invio del modulo n. :id',
        'title' => 'Sito web: :form',
        'visitor' => 'Visitatore del sito',
        'yes' => 'Sì',
        'lead' => 'Lead n. :id creato.',
        'deal' => 'Trattativa n. :deal creata, contatto n. :contact.',
        'gone' => 'L\'invio è stato eliminato prima di essere inviato.',
    ],
];
