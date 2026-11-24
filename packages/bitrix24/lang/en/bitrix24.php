<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => 'Bitrix24',
    'title_links' => 'Bitrix24: forms',

    'settings' => [
        'description' => 'Form submissions go to your Bitrix24 as leads, and the chat widget of Bitrix24 shows on the site. Create an incoming webhook in Bitrix24 (Developer resources → Other → Incoming webhook) with the right to CRM and paste its address here.',
        'webhook' => 'Incoming webhook',
        'webhook_hint' => 'The address Bitrix24 shows, like https://portal.bitrix24.com/rest/1/code/. Stored encrypted and never shown again: to change it, paste a new one; leave the field empty to keep the current one.',
        'send_forms' => 'Send form submissions to Bitrix24',
        'send_forms_hint' => 'A form with no link below goes as a lead with its fields placed by their type.',
        'widget_code' => 'Chat widget code',
        'widget_code_hint' => 'The code of the open line or chat widget from Bitrix24 (CRM → Contact center → Online chat → Widget). It is put at the end of every page of the site; empty means no widget.',
        'check' => 'Check connection',
        'connected' => 'Connected: :portal, user :user.',
    ],

    'fields' => [
        'form' => 'Form',
        'mode' => 'Send as',
        'active' => 'Send this form to Bitrix24',
        'mapping' => 'Fields',
        'mapping_hint' => 'One line per field: "form field = Bitrix24 field", for example "phone = PHONE" or "message = COMMENTS". A field without a line goes by its type (an e-mail to EMAIL, a phone to PHONE); what has no place goes into the comment. Lines starting with # are ignored.',
    ],

    'modes' => [
        'lead' => 'Lead',
        'deal' => 'Deal with a contact',
    ],

    'errors' => [
        'no_webhook' => 'The Bitrix24 webhook is not set: paste it in Modules → Bitrix24.',
        'unreachable' => 'Bitrix24 does not answer. Check the portal address in the webhook.',
        'portal' => 'Bitrix24 refused: :error',
        'mapping_line' => 'Line :line is not "form field = Bitrix24 field".',
    ],

    'payload' => [
        'subject' => 'Form submission #:id',
        'title' => 'Website: :form',
        'visitor' => 'Website visitor',
        'yes' => 'Yes',
        'lead' => 'Lead #:id created.',
        'deal' => 'Deal #:deal created, contact #:contact.',
        'gone' => 'The submission was deleted before it was sent.',
    ],
];
