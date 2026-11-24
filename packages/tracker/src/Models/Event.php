<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Models;

use Atrium\Tracker\Enums\Priority;
use Atrium\Tracker\Support\Statuses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One line of a task's history: who changed what, from what to what.
 *
 * @property int $id
 * @property int $task_id
 * @property int|null $actor_id
 * @property string $kind
 * @property string|null $old_value
 * @property string|null $new_value
 * @property Carbon|null $created_at
 */
class Event extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'tracker_events';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['task_id' => 'integer', 'actor_id' => 'integer', 'created_at' => 'datetime'];
    }

    /**
     * The line as the history shows it: "Status: To do → Done".
     */
    public function describe(): string
    {
        $dash = '—';

        return match ($this->kind) {
            'status' => __('atrium-tracker::tracker.events.status', ['old' => Statuses::label((string) $this->old_value), 'new' => Statuses::label((string) $this->new_value)]),
            'priority' => __('atrium-tracker::tracker.events.priority', ['old' => Priority::tryFrom((string) $this->old_value)?->label() ?? $dash, 'new' => Priority::tryFrom((string) $this->new_value)?->label() ?? $dash]),
            'assignee' => __('atrium-tracker::tracker.events.assignee', ['old' => $this->old_value ?? $dash, 'new' => $this->new_value ?? $dash]),
            default => __('atrium-tracker::tracker.events.created'),
        };
    }
}
