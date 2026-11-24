<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Alerts;

use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Models\AdminUser;
use Atrium\Integrations\Models\ExchangeLog;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Tells the administrators that a connector failed for good: a letter to the
 * addresses in Modules → Integrations, or to every active superadministrator
 * when none are set. At most one letter per integration in `alert_interval`
 * minutes. The `atrium.integrations.failed` action fires for every failure
 * (not throttled), so another channel, say a messenger, can listen to it.
 *
 * A letter that cannot be sent is logged and changes nothing else.
 */
class FailureAlert
{
    public function __construct(private readonly Repository $cache) {}

    /**
     * True when a letter went out.
     */
    public function send(ExchangeLog $entry): bool
    {
        Hook::fire('atrium.integrations.failed', $entry);

        $to = $this->recipients();

        if ($to === [] || ! $this->cache->add('atrium-integrations:alert:'.$entry->integration, true, now()->addMinutes(max(1, (int) config('atrium-integrations.alert_interval'))))) {
            return false;
        }

        try {
            Mail::to($to)->send(new FailureMail($entry));
        } catch (Throwable $e) {
            // The text may carry an address with a key in it: the class only.
            Log::warning('Atrium integrations could not send an alert: '.class_basename($e));

            return false;
        }

        return true;
    }

    /**
     * @return array<int,string>
     */
    public function recipients(): array
    {
        $raw = (string) Setting::get('integrations.alert_emails', '');
        $emails = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $emails = array_values(array_unique(array_filter($emails, static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)));

        if ($emails !== []) {
            return $emails;
        }

        return AdminUser::query()->where('is_superadmin', true)->where('is_active', true)
            ->whereNotNull('email')->orderBy('id')->pluck('email')->all();
    }
}
