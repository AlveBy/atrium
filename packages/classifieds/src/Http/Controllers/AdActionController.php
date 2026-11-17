<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Http\Controllers;

use Atrium\Classifieds\Board;
use Atrium\Classifieds\Enums\ComplaintReason;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Favourite;
use Atrium\Classifieds\Support\Accounts;
use Atrium\Classifieds\Support\AdWorkflow;
use Atrium\Classifieds\Support\Alerts;
use Atrium\Classifieds\Support\Conversations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

/**
 * What a visitor does with an ad: shows the phone, complains, keeps it in
 * favourites, writes to the seller; and saves the search they are on.
 */
final class AdActionController extends Controller
{
    public function __construct(private readonly Board $board, private readonly AdWorkflow $workflow, private readonly Accounts $accounts) {}

    /**
     * The contacts, one click at a time and only so many an hour: harvesting
     * phones from the whole board is what the limit is for.
     */
    public function phone(Request $request, int $ad): JsonResponse
    {
        $record = $this->published($ad);
        $who = $this->accounts->userId() !== null ? 'u'.$this->accounts->userId() : 'ip:'.$request->ip();

        $this->workflow->hit('phone', $who, $this->board->int('phone_reveals_per_hour'), 3600);
        $record->newQuery()->whereKey($record->id)->increment('phone_views');

        return response()->json([
            'name' => $record->contact_name,
            'phone' => $record->contact_phone,
            'email' => $record->contact_email,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function complain(Request $request, int $ad): RedirectResponse
    {
        $record = $this->published($ad);
        $data = $request->validate([
            'reason' => ['required', Rule::enum(ComplaintReason::class)],
            'text' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->workflow->complain($record, $this->accounts->userId(), (string) $request->ip(), ComplaintReason::from($data['reason']), $data['text'] ?? null);

        return redirect()->to($record->url().'#complain')->with('atrium-classifieds.status', __('atrium-classifieds::classifieds.flash.complained'));
    }

    public function favourite(int $ad): RedirectResponse
    {
        $record = $this->published($ad);
        $userId = (int) $this->accounts->userId();
        $existing = Favourite::query()->where('user_id', $userId)->where('ad_id', $record->id)->first();

        if ($existing !== null) {
            $existing->delete();
        } else {
            Favourite::query()->create(['user_id' => $userId, 'ad_id' => $record->id]);
        }

        return redirect()->back(302, [], $record->url());
    }

    public function message(Request $request, int $ad, Conversations $conversations): RedirectResponse
    {
        $record = Ad::query()->find($ad);
        abort_if($record === null, 404);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $owner = $this->accounts->owner();
        abort_if($owner === null || $owner->owns($record), 403);

        $conversations->send($owner, $record, $owner->userId, $data['body']);

        return redirect()->to($this->board->route('cabinet.thread', ['ad' => $record->id, 'buyer' => $owner->userId]))
            ->with('atrium-classifieds.status', __('atrium-classifieds::classifieds.flash.sent'));
    }

    public function saveSearch(Request $request, Alerts $alerts): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'filters' => ['nullable', 'array'],
            'notify' => ['nullable', 'boolean'],
        ]);

        $alerts->save((int) $this->accounts->userId(), $data['name'], $data['filters'] ?? [], (bool) ($data['notify'] ?? true));

        return redirect()->to($this->board->route('cabinet.searches'))->with('atrium-classifieds.status', __('atrium-classifieds::classifieds.flash.search_saved'));
    }

    private function published(int $id): Ad
    {
        $ad = Ad::query()->find($id);
        abort_if($ad === null || ! $ad->isPublished(), 404);

        return $ad;
    }
}
