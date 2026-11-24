<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Alerts;

use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Route;

/**
 * The alert as a plain-text letter. Marked with the "integrations" purpose,
 * so the mail module, when installed, sends it through the account chosen
 * for it.
 */
final class FailureMail extends Mailable
{
    public function __construct(public readonly ExchangeLog $entry)
    {
        $this->metadata('atrium_purpose', 'integrations');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('atrium-integrations::integrations.alert.subject', [
            'site' => config('app.name', 'Atrium'),
            'integration' => $this->entry->integration,
        ]));
    }

    public function content(): Content
    {
        $entry = $this->entry;

        return new Content(text: 'atrium-integrations::mail.alert', with: [
            'lines' => array_values(array_filter([
                __('atrium-integrations::integrations.alert.what', ['integration' => $entry->integration, 'action' => $entry->action]),
                $entry->subject !== null ? __('atrium-integrations::integrations.alert.subject_line', ['subject' => $entry->subject]) : null,
                __('atrium-integrations::integrations.alert.error', ['error' => $entry->error ?? '—']),
                __('atrium-integrations::integrations.alert.attempts', ['count' => $entry->attempt]),
            ])),
            'url' => Route::has('admin.integration_logs.index') ? route('admin.integration_logs.index') : null,
        ]);
    }
}
