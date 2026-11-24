<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Jobs;

use Atrium\Integrations\Enums\Status;
use Atrium\Integrations\Exchange;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * A call to another system, run by the queue: tried five times with a growing
 * pause between attempts (`atrium-integrations.backoff`), and every attempt
 * leaves a line in the exchange log: done, failed and will be tried again,
 * or failed for good, which also tells the administrators.
 *
 *     final class SendLead extends IntegrationJob
 *     {
 *         public function __construct(private readonly int $submissionId) {}
 *
 *         protected function integration(): string { return 'bitrix24'; }
 *         protected function action(): string { return 'lead.add'; }
 *         protected function perform(): ?string { ...; return 'Lead 15'; }
 *     }
 *
 *     SendLead::dispatch($submission->id)->afterCommit();
 *
 * With the sync queue there is no second attempt: a failure is final.
 */
abstract class IntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    /** The connector's key in the log: bitrix24, 1c. */
    abstract protected function integration(): string;

    /** What this job does, in the log's "action" column: lead.add. */
    abstract protected function action(): string;

    /**
     * Does the work. An exception means a failed attempt. What it returns is
     * written to the log as what was done.
     */
    abstract protected function perform(): ?string;

    /** What the job is about, in words: "Form submission #12". */
    protected function subject(): ?string
    {
        return null;
    }

    /**
     * Texts that must never reach the log, such as the webhook address; they
     * are replaced by `***` in the error and in everything else written.
     *
     * @return list<string>
     */
    protected function secrets(): array
    {
        return [];
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return array_values(array_map('intval', (array) config('atrium-integrations.backoff')));
    }

    public function handle(Exchange $exchange): void
    {
        $started = now();

        try {
            $done = $this->perform();
        } catch (Throwable $e) {
            if ($this->willRetry()) {
                $this->record($exchange, Status::Retry, error: $e->getMessage(), started: $started);
            }

            throw $e;
        }

        $this->record($exchange, Status::Ok, message: $done, started: $started);
    }

    /**
     * The last attempt failed, or the job was killed (timeout): the line the
     * log and the alert are made from.
     */
    public function failed(Throwable $e): void
    {
        $this->record(app(Exchange::class), Status::Failed, error: $e->getMessage());
    }

    private function willRetry(): bool
    {
        return $this->job !== null && ! $this->job instanceof SyncJob && $this->attempts() < $this->tries;
    }

    private function record(Exchange $exchange, Status $status, ?string $message = null, ?string $error = null, ?CarbonInterface $started = null): void
    {
        $exchange->record(
            $this->integration(),
            $this->action(),
            $status,
            subject: $this->subject(),
            message: $message,
            error: $error,
            attempt: $this->attempts(),
            durationMs: $started === null ? null : (int) $started->diffInMilliseconds(now()),
            secrets: $this->secrets(),
        );
    }
}
