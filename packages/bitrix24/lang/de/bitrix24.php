<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => 'Bitrix24: Formulare',

    'settings' => [
        'description' => 'Formularanfragen landen als Leads in Ihrem Bitrix24, und das Chat-Widget von Bitrix24 wird auf der Website angezeigt. Erstellen Sie in Bitrix24 einen eingehenden Webhook (Entwicklerressourcen → Sonstiges → Eingehender Webhook) mit Zugriff auf das CRM und fügen Sie dessen Adresse hier ein.',
        'webhook' => 'Eingehender Webhook',
        'webhook_hint' => 'Die Adresse, die Bitrix24 anzeigt, etwa https://portal.bitrix24.com/rest/1/code/. Sie wird verschlüsselt gespeichert und nie wieder angezeigt: Zum Ändern fügen Sie eine neue ein; lassen Sie das Feld leer, um die aktuelle zu behalten.',
        'send_forms' => 'Formularanfragen an Bitrix24 senden',
        'send_forms_hint' => 'Ein Formular ohne unten festgelegte Zuordnung geht als Lead weiter, die Felder werden nach ihrem Typ eingeordnet.',
        'widget_code' => 'Code des Chat-Widgets',
        'widget_code_hint' => 'Der Code der offenen Leitung oder des Chat-Widgets aus Bitrix24 (CRM → Contact-Center → Online-Chat → Widget). Er wird am Ende jeder Seite der Website eingefügt; leer bedeutet kein Widget.',
        'check' => 'Verbindung prüfen',
        'connected' => 'Verbunden: :portal, Benutzer :user.',
    ],

    'fields' => [
        'form' => 'Formular',
        'mode' => 'Senden als',
        'active' => 'Dieses Formular an Bitrix24 senden',
        'mapping' => 'Felder',
        'mapping_hint' => 'Eine Zeile pro Feld: "Formularfeld = Bitrix24-Feld", zum Beispiel "phone = PHONE" oder "message = COMMENTS". Ein Feld ohne Zeile wird nach seinem Typ zugeordnet (E-Mail an EMAIL, Telefon an PHONE); was keinen Platz hat, kommt in den Kommentar. Zeilen, die mit # beginnen, werden ignoriert.',
    ],

    'modes' => [
        'lead' => 'Lead',
        'deal' => 'Deal mit Kontakt',
    ],

    'errors' => [
        'no_webhook' => 'Der Bitrix24-Webhook ist nicht festgelegt: Fügen Sie ihn unter Module → Bitrix24 ein.',
        'unreachable' => 'Bitrix24 antwortet nicht. Prüfen Sie die Portaladresse im Webhook.',
        'portal' => 'Bitrix24 hat abgelehnt: :error',
        'mapping_line' => 'Zeile :line hat nicht die Form "Formularfeld = Bitrix24-Feld".',
    ],

    'payload' => [
        'subject' => 'Formularanfrage Nr. :id',
        'title' => 'Website: :form',
        'visitor' => 'Website-Besucher',
        'yes' => 'Ja',
        'lead' => 'Lead Nr. :id erstellt.',
        'deal' => 'Deal Nr. :deal erstellt, Kontakt Nr. :contact.',
        'gone' => 'Die Anfrage wurde vor dem Senden gelöscht.',
    ],
];
