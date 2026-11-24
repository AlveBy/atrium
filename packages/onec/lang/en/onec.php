<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    'title' => '1C',
    'title_runs' => '1C exchange',

    'settings' => [
        'description' => 'The catalogue comes from 1C:Enterprise by the standard CommerceML exchange. In 1C, in the exchange with the site, give this address: :url (a configuration that asks only for the site takes :bitrix after the site address), and below the login and the password you set here.',
        'login' => 'Login',
        'login_hint' => 'What 1C signs in with. The exchange is closed until both the login and the password are set.',
        'password' => 'Password',
        'password_hint' => 'Stored encrypted and never shown again: to change it, type a new one; leave the field empty to keep the current one.',
    ],

    'fields' => [
        'started_at' => 'Started',
        'finished_at' => 'Finished',
        'file' => 'File',
        'status_label' => 'Result',
        'sections_created' => 'New sections',
        'sections_updated' => 'Sections updated',
        'products_created' => 'New products',
        'products_updated' => 'Products updated',
        'offers_updated' => 'Prices and stock updated',
        'skipped' => 'Skipped offers (no such product)',
        'errors' => 'Errors',
        'error' => 'What went wrong',
    ],

    'status' => [
        'running' => 'In progress',
        'done' => 'Done',
        'failed' => 'Failed',
    ],

    'reply' => [
        'wrong_login' => 'Wrong login or password, or the exchange is not set up in Modules → 1C.',
        'denied' => 'Not signed in: start the exchange again.',
        'unknown_type' => 'Unknown exchange type: :type.',
        'unknown_mode' => 'Unknown step of the exchange: :mode.',
        'bad_name' => 'This file name is not allowed.',
        'no_file' => 'The file :file did not come.',
        'progress' => 'Imported items: :count.',
        'summary' => 'Sections: :sections_created new, :sections_updated updated. Products: :products_created new, :products_updated updated. Prices and stock: :offers. Skipped: :skipped. Errors: :errors.',
        'no_orders' => 'Orders are not exchanged: the shop module is not installed.',
    ],
];
