<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Console;

use Atrium\Classifieds\Support\Alerts;
use Illuminate\Console\Command;

/**
 * Mails new ads matching saved searches. The scheduler runs it every half
 * hour; each search reports an ad once.
 */
final class AlertsCommand extends Command
{
    protected $signature = 'atrium:classifieds:alerts';

    protected $description = 'Mail new classified ads matching saved searches';

    public function handle(Alerts $alerts): int
    {
        $this->info(__('atrium-classifieds::classifieds.console.alerts', ['count' => $alerts->send()]));

        return self::SUCCESS;
    }
}
