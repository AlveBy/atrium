<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Mail;

use Atrium\Jobs\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;

/**
 * A new application, to HR. The CV is not attached: personal data stays on
 * the private disk, the letter links to the application in the admin.
 * "Reply" answers the candidate.
 */
class ApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Application $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('atrium-jobs::jobs.mail.subject', [
                'vacancy' => $this->application->vacancy_name ?? __('atrium-jobs::jobs.public.general'),
                'name' => $this->application->name,
            ]),
            replyTo: [new Address($this->application->email, $this->application->name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'atrium-jobs::mail.application', with: [
            'adminUrl' => Route::has('admin.job_applications.edit') ? route('admin.job_applications.edit', $this->application->id) : null,
        ]);
    }
}
