<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Console;

use Atrium\Classifieds\Support\AdWorkflow;
use Illuminate\Console\Command;

/**
 * Takes ads past their term off the site. The scheduler runs it every
 * hour; the owner is told by mail and can renew from the cabinet.
 */
final class ExpireCommand extends Command
{
    protected $signature = 'atrium:classifieds:expire';

    protected $description = 'Take classified ads past their term off the site';

    public function handle(AdWorkflow $workflow): int
    {
        $this->info(__('atrium-classifieds::classifieds.console.expired', ['count' => $workflow->expireDue()]));

        return self::SUCCESS;
    }
}
