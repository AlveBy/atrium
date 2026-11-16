<?php

declare(strict_types=1);

namespace Atrium\Jobs\Mail;

use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Models\ReplyTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * HR's answer to a candidate, from a template, in the language they applied in.
 */
class ReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Application $application, public ReplyTemplate $template)
    {
        if ($application->locale !== null && $application->locale !== '') {
            $this->locale($application->locale);
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->template->render('subject', $this->application, $this->locale));
    }

    public function content(): Content
    {
        return new Content(view: 'atrium-jobs::mail.reply', with: [
            'body' => $this->template->render('body', $this->application, $this->locale),
        ]);
    }
}
