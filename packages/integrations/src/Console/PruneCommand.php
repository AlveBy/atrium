<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Console;

use Atrium\Admin\Facades\Setting;
use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Console\Command;

/**
 * Deletes the log lines older than the number of days set in Modules →
 * Integrations (90 unless changed).
 */
final class PruneCommand extends Command
{
    protected $signature = 'atrium:integrations:prune';

    protected $description = 'Delete exchange log lines older than the retention period';

    public function handle(): int
    {
        $days = max(1, (int) Setting::get('integrations.retention_days', config('atrium-integrations.retention_days')));

        $deleted = ExchangeLog::query()->where('created_at', '<', now()->subDays($days))->delete();

        $this->info('Deleted: '.$deleted);

        return self::SUCCESS;
    }
}
