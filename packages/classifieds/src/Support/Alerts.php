<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Admin\Models\Site;
use Atrium\Admin\Support\Sites;
use Atrium\Classifieds\Board;
use Atrium\Classifieds\Mail\AlertMail;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\SavedSearch;
use Illuminate\Support\Facades\Mail;

/**
 * E-mails about new ads matching saved searches. Each search remembers the
 * newest ad it has reported, so an ad is never sent twice and a search
 * saved today does not mail the whole board.
 */
class Alerts
{
    public function __construct(private readonly AdSearch $search, private readonly Accounts $accounts, private readonly Board $board) {}

    /**
     * A new search starts after the newest ad there is.
     *
     * @param  array<string,mixed>  $filters
     */
    public function save(int $userId, string $name, array $filters, bool $notify = true): SavedSearch
    {
        return SavedSearch::query()->create([
            'user_id' => $userId,
            'name' => mb_substr(trim($name), 0, 191),
            'locale' => app()->getLocale(),
            'filters' => $this->search->normalize($filters),
            'notify' => $notify,
            'last_ad_id' => (int) Ad::query()->withoutGlobalScopes()->max('id'),
        ]);
    }

    /**
     * Mail every search with new matches. Returns how many letters went.
     */
    public function send(): int
    {
        $sent = 0;
        $sites = app(Sites::class);
        $previous = $sites->isResolved() ? $sites->current() : null;
        $locale = app()->getLocale();

        SavedSearch::query()->withoutGlobalScopes()->where('notify', true)->orderBy('id')->chunkById(100, function ($searches) use (&$sent, $sites, $locale): void {
            foreach ($searches as $saved) {
                $site = $saved->site_id !== null ? Site::query()->find($saved->site_id) : null;

                if ($site !== null) {
                    $sites->setCurrent($site);
                }

                app()->setLocale($saved->locale ?: $locale);
                $sent += $this->notify($saved) ? 1 : 0;
            }
        });

        app()->setLocale($locale);

        if ($previous !== null) {
            $sites->setCurrent($previous);
        }

        return $sent;
    }

    public function notify(SavedSearch $saved): bool
    {
        $cards = $this->search->newSince($saved->filters, $saved->last_ad_id, max(1, (int) config('atrium-classifieds.alert_limit', 10)));
        $email = $this->accounts->email($saved->user_id);

        if ($cards === [] || $email === null) {
            return false;
        }

        Mail::to($email)->send((new AlertMail($saved, $cards, $this->board->route('index', $saved->filters)))->locale(app()->getLocale()));

        $saved->forceFill(['last_ad_id' => max(array_column($cards, 'id')), 'notified_at' => now()])->save();

        return true;
    }
}
