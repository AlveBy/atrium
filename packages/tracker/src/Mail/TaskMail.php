<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Mail;

use Atrium\Tracker\Models\Status;
use Atrium\Tracker\Models\Task;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The plain-text letter about a task: it was given to you, or its status changed.
 */
final class TaskMail extends Mailable
{
    public const ASSIGNED = 'assigned';

    public const STATUS = 'status';

    public function __construct(
        public readonly Task $task,
        public readonly string $kind,
        public readonly ?string $previous = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('atrium-tracker::tracker.mail.'.$this->kind.'_subject', [
            'code' => $this->task->code,
            'title' => $this->task->title,
            'status' => Status::labelOf($this->task->status),
        ]));
    }

    public function content(): Content
    {
        $lines = [
            __('atrium-tracker::tracker.mail.'.$this->kind.'_line', [
                'code' => $this->task->code,
                'title' => $this->task->title,
                'old' => Status::labelOf($this->previous),
                'new' => Status::labelOf($this->task->status),
            ]),
        ];

        return new Content(text: 'atrium-tracker::mail.task', with: [
            'lines' => $lines,
            'url' => route('admin.tracker_tasks.edit', $this->task->getKey()),
        ]);
    }
}
