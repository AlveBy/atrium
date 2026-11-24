<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Integrations
|--------------------------------------------------------------------------
|
| Defaults for alveby/atrium-integrations, the base of the connectors. The
| e-mail addresses of alerts and the number of days the log is kept are also
| set in the admin (Modules → Integrations); a value saved there wins.
|
*/

return [
    // Seconds to wait before the 2nd, 3rd, ... attempt of a failed job. A job
    // is tried five times; if the last attempt fails too, it is logged as
    // failed and the administrators are told.
    'backoff' => [10, 60, 300, 900],

    // At most one alert per integration in this many minutes, so a connector
    // that is down for a night sends one letter, not one per record.
    'alert_interval' => (int) env('ATRIUM_INTEGRATIONS_ALERT_INTERVAL', 60),

    // How many days a log line is kept.
    'retention_days' => (int) env('ATRIUM_INTEGRATIONS_RETENTION_DAYS', 90),

    'schedule' => (bool) env('ATRIUM_INTEGRATIONS_SCHEDULE', true),
];
