<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Where applications are sent
    |--------------------------------------------------------------------------
    |
    | POST /{apply_path}, and /{language}/{apply_path} for the other content
    | languages. The form component builds the address by itself.
    |
    */
    'apply_path' => 'jobs/apply',

    /*
    |--------------------------------------------------------------------------
    | Who is told about a new application
    |--------------------------------------------------------------------------
    |
    | Comma-separated addresses of the HR team. The vacancy's contact person
    | gets the mail too, and so do the recipients of the forms module's form
    | (see `form` below) when it is set up.
    |
    */
    'notify' => env('ATRIUM_JOBS_NOTIFY', ''),

    /*
    |--------------------------------------------------------------------------
    | The CV file
    |--------------------------------------------------------------------------
    |
    | Kept on a private disk ("local" is storage/app/private): it has no
    | public address and is downloaded only from the admin, by those who may
    | see the applications.
    |
    */
    'disk' => env('ATRIUM_JOBS_DISK', 'local'),

    'cv_required' => (bool) env('ATRIUM_JOBS_CV_REQUIRED', true),

    'cv_types' => ['pdf', 'doc', 'docx'],

    // Kilobytes.
    'cv_max_size' => (int) env('ATRIUM_JOBS_CV_MAX_SIZE', 5120),

    /*
    |--------------------------------------------------------------------------
    | Personal data
    |--------------------------------------------------------------------------
    |
    | An application, its CV and its notes are deleted this many days after
    | it was last changed (a status, a note, a reply). 0 keeps them forever.
    | `atrium:jobs:prune` does it; the scheduler runs it daily.
    |
    | The consent checkbox links to the privacy policy at `privacy_url`.
    |
    */
    'retention_days' => (int) env('ATRIUM_JOBS_RETENTION_DAYS', 180),

    'privacy_url' => env('ATRIUM_JOBS_PRIVACY_URL'),

    /*
    |--------------------------------------------------------------------------
    | Extra questions from the forms module
    |--------------------------------------------------------------------------
    |
    | With alveby/atrium-forms installed, the fields of the site's form with
    | this code are asked after the standard ones, the answers are kept with
    | the application, a copy goes to the form's submissions and the form's
    | recipients get the mail. Without the module, or without such a form,
    | the standard fields are all there is.
    |
    */
    'form' => 'job-application',

    /*
    |--------------------------------------------------------------------------
    | Anti-spam
    |--------------------------------------------------------------------------
    |
    | A hidden field people leave empty, the time the form was drawn (a form
    | sent sooner than `min_seconds` is a bot's) and a limit per address.
    |
    */
    'honeypot' => 'website',

    'min_seconds' => (int) env('ATRIUM_JOBS_MIN_SECONDS', 3),

    'per_hour' => (int) env('ATRIUM_JOBS_PER_HOUR', 10),

    /*
    |--------------------------------------------------------------------------
    | Lists
    |--------------------------------------------------------------------------
    */

    // Vacancies per page of the public list.
    'per_page' => 20,

    // The currencies offered for a salary; any ISO 4217 code works.
    'currencies' => ['EUR', 'USD', 'GBP', 'PLN', 'CHF', 'RUB', 'BYN', 'UAH', 'KZT'],

    // The pipeline tile on the admin dashboard (applications by status).
    'dashboard' => true,
];
