<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Mail;

use Atrium\Classifieds\Models\Ad;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The moderator's decision about an ad: published, or rejected with the
 * reason; and the end of its term.
 */
final class StatusMail extends Mailable
{
    use Queueable;

    public function __construct(public readonly Ad $ad, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('atrium-classifieds::classifieds.mail.status_subject.'.$this->ad->status->value, ['title' => $this->ad->title]));
    }

    public function content(): Content
    {
        return new Content(view: 'atrium-classifieds::mail.status');
    }
}
