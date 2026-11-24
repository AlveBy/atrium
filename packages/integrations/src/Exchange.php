<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations;

use Atrium\Admin\Modules\Modules;
use Atrium\Integrations\Alerts\FailureAlert;
use Atrium\Integrations\Enums\Direction;
use Atrium\Integrations\Enums\Status;
use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The exchange log of every connector: one call writes one line, and a line
 * that says "failed" also tells the administrators (at most once per
 * integration in `alert_interval` minutes).
 *
 *     app(Exchange::class)->record('bitrix24', 'lead.add', Status::Ok, subject: 'Submission #12');
 *
 * A connector that runs queued uses Jobs\IntegrationJob, which calls this
 * for every attempt; one that answers requests (1C pushing a catalogue)
 * calls it itself. Text is cut to 2000 characters, and whatever the caller
 * lists in $secrets (a webhook address, a password) is replaced by `***` in
 * every text before it is stored, so a log line is always safe to show.
 *
 * When the module itself is not installed the line goes to the application
 * log instead, and the connector keeps working.
 */
class Exchange
{
    public const LIMIT = 2000;

    public function __construct(
        private readonly Modules $modules,
        private readonly FailureAlert $alert,
    ) {}

    /**
     * @param  list<string>  $secrets
     */
    public function record(
        string $integration,
        string $action,
        Status $status,
        ?string $subject = null,
        ?string $message = null,
        ?string $error = null,
        int $attempt = 1,
        ?int $durationMs = null,
        Direction $direction = Direction::Out,
        array $secrets = [],
    ): ExchangeLog {
        $entry = new ExchangeLog([
            'integration' => $integration,
            'direction' => $direction->value,
            'action' => $action,
            'status' => $status->value,
            'subject' => $this->clean($subject, $secrets, 250),
            'message' => $this->clean($message, $secrets, self::LIMIT),
            'error' => $this->clean($error, $secrets, self::LIMIT),
            'attempt' => $attempt,
            'duration_ms' => $durationMs,
        ]);

        if ($this->modules->isInstalled(IntegrationsServiceProvider::MODULE)) {
            $entry->created_at = now();
            $entry->save();
        } else {
            Log::log($status === Status::Failed ? 'error' : 'info', trim("Atrium integration {$integration} {$action}: {$status->value} {$entry->error}"));
        }

        if ($status === Status::Failed) {
            $this->alert->send($entry);
        }

        return $entry;
    }

    /**
     * @param  list<string>  $secrets
     */
    private function clean(?string $text, array $secrets, int $limit): ?string
    {
        if ($text === null) {
            return null;
        }

        return Str::limit(str_replace($secrets, '***', $text), $limit, '…');
    }
}
