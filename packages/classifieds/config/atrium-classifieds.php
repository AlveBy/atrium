<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    /*
    | The public pages: /classifieds, the category and city landings, the
    | ads, company pages and the seller's cabinet under /classifieds/my.
    */
    'routes' => (bool) env('ATRIUM_CLASSIFIEDS_ROUTES', true),
    'prefix' => env('ATRIUM_CLASSIFIEDS_PREFIX', 'classifieds'),

    /*
    | Who posts. With the personal cabinet (alveby/atrium-account) installed
    | its people and companies post and these two are ignored; without it
    | the application's own users sign in through this guard.
    */
    'guard' => env('ATRIUM_CLASSIFIEDS_GUARD', 'web'),
    'login_route' => 'login',

    /*
    | Defaults of the settings an administrator changes per site under
    | Modules → Classifieds; see ClassifiedsSettings.
    */
    'defaults' => [
        'premoderation' => true,
        'ttl_days' => 30,
        'renewals' => 5,
        'ads_per_day' => 5,
        'messages_per_hour' => 20,
        'phone_reveals_per_hour' => 20,
        'complaints_to_review' => 3,
        'currency' => 'USD',
        'per_page' => 20,
        'max_photos' => 10,
        'raise_price' => 2,
        'highlight_price' => 3,
        'highlight_days' => 7,
        'vip_price' => 10,
        'vip_days' => 7,
    ],

    /*
    | Cards of the list (title, price, city, photo, flags) are kept in the
    | cache, so a list page reads ids and never the ads' properties.
    */
    'card_ttl' => 3600,

    // How many new matches one saved-search e-mail lists.
    'alert_limit' => 10,
];
