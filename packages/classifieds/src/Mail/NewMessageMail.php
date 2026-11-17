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
 * "You have a new message about …" with a link to the thread. The text
 * itself stays in the cabinet: mail is not the place for personal data.
 */
final class NewMessageMail extends Mailable
{
    use Queueable;

    public function __construct(public readonly Ad $ad, public readonly string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('atrium-classifieds::classifieds.mail.message_subject', ['title' => $this->ad->title]));
    }

    public function content(): Content
    {
        return new Content(view: 'atrium-classifieds::mail.message');
    }
}
