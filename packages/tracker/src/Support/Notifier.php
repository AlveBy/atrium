<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Support;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Models\AdminUser;
use Atrium\Tracker\Mail\TaskMail;
use Atrium\Tracker\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * The letters to the person a task is given to: when it is given to them and
 * when its status changes. Each can be switched off in the module's settings.
 * Nobody is written to about what they did themselves.
 */
final class Notifier
{
    private static bool $silent = false;

    /**
     * Run work that changes tasks without writing to anybody: the demo data.
     */
    public static function silently(callable $work): void
    {
        $was = self::$silent;
        self::$silent = true;

        try {
            $work();
        } finally {
            self::$silent = $was;
        }
    }

    public static function assigned(Task $task): void
    {
        self::send($task, TaskMail::ASSIGNED, 'notify_assigned');
    }

    public static function statusChanged(Task $task, string $from): void
    {
        self::send($task, TaskMail::STATUS, 'notify_status', $from);
    }

    private static function send(Task $task, string $kind, string $setting, ?string $from = null): void
    {
        if (self::$silent || ! Setting::get('tracker.'.$setting, true)) {
            return;
        }

        $admin = AdminUser::query()->find($task->assignee_id);

        if ($admin === null || ! $admin->is_active || $admin->getKey() === Admins::currentId()) {
            return;
        }

        // Only for what was committed.
        DB::afterCommit(static function () use ($admin, $task, $kind, $from): void {
            Mail::to($admin)->locale($admin->locale ?: app()->getLocale())->send(new TaskMail($task, $kind, $from));
        });
    }
}
