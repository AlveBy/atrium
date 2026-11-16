<?php

declare(strict_types=1);

namespace Atrium\Jobs\Console;

use Atrium\Jobs\Models\Application;
use Illuminate\Console\Command;

/**
 * Personal data is not kept for ever: applications untouched for
 * `atrium-jobs.retention_days` are deleted with their CVs. Runs daily from
 * the scheduler; 0 days keeps everything.
 */
final class PruneCommand extends Command
{
    protected $signature = 'atrium:jobs:prune {--days= : Days since the last change; overrides atrium-jobs.retention_days}';

    protected $description = 'Delete job applications (and their CVs) older than the retention period';

    public function handle(): int
    {
        $days = $this->option('days') ?? config('atrium-jobs.retention_days', 180);

        if (! is_numeric($days) || (int) $days < 0) {
            $this->error('--days must be a whole number of days, 0 or more.');

            return self::FAILURE;
        }

        if ((int) $days === 0) {
            $this->info('Retention is off (0 days): nothing deleted.');

            return self::SUCCESS;
        }

        $count = 0;

        // One by one, every site: the model deletes the CV and the forms copy.
        Application::query()->withoutGlobalScopes()
            ->where('updated_at', '<', now()->subDays((int) $days))
            ->chunkById(200, function ($applications) use (&$count): void {
                foreach ($applications as $application) {
                    $application->delete();
                    $count++;
                }
            });

        $this->info("Deleted {$count} application(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
