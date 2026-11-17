<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Mail;

use Atrium\Classifieds\Models\SavedSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * New ads matching a saved search.
 */
final class AlertMail extends Mailable
{
    use Queueable;

    /**
     * @param  array<int,array<string,mixed>>  $cards
     */
    public function __construct(public readonly SavedSearch $search, public readonly array $cards, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('atrium-classifieds::classifieds.mail.alert_subject', ['name' => $this->search->name, 'count' => count($this->cards)]));
    }

    public function content(): Content
    {
        return new Content(view: 'atrium-classifieds::mail.alert');
    }
}
