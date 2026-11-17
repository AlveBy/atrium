<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Support;

use Atrium\Classifieds\Board;
use Atrium\Classifieds\Mail\NewMessageMail;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Messages between buyers and sellers, one thread per ad and buyer. The
 * seller side is the ad's owner: the person, or every member of the
 * company. Nobody else reads a thread; the text is stored encrypted and
 * the e-mail about a new message carries only a link.
 */
class Conversations
{
    public function __construct(private readonly Board $board, private readonly AdWorkflow $workflow, private readonly Accounts $accounts) {}

    /**
     * Write in the thread of the ad with the buyer. The buyer writes as
     * themselves; the seller (the owner) answers into an existing thread.
     */
    public function send(Owner $owner, Ad $ad, int $buyerId, string $body): Message
    {
        $fromSeller = $owner->owns($ad);
        $body = trim($body);

        if ($fromSeller) {
            abort_unless(Message::query()->where('ad_id', $ad->id)->where('buyer_id', $buyerId)->exists(), 404);
        } else {
            abort_unless($buyerId === $owner->userId, 403);

            if (! $ad->isPublished()) {
                throw ValidationException::withMessages(['body' => __('atrium-classifieds::classifieds.errors.not_published')]);
            }
        }

        if ($body === '') {
            throw ValidationException::withMessages(['body' => __('atrium-classifieds::classifieds.errors.empty_message')]);
        }

        $this->workflow->hit('message', 'u'.$owner->userId, $this->board->int('messages_per_hour'), 3600);

        $message = Message::query()->create([
            'ad_id' => $ad->id,
            'buyer_id' => $buyerId,
            'sender_id' => $owner->userId,
            'from_seller' => $fromSeller,
            'body' => $body,
        ]);

        $email = $this->accounts->email($fromSeller ? $buyerId : $ad->account_user_id);

        if ($email !== null) {
            try {
                Mail::to($email)->send(new NewMessageMail($ad, $this->board->route('cabinet.thread', ['ad' => $ad->id, 'buyer' => $buyerId])));
            } catch (\Throwable $e) {
                // The message is saved and shown in the cabinet either way.
                report($e);
            }
        }

        return $message;
    }

    /**
     * The messages of a thread, read by the one who opens it.
     *
     * @return Collection<int,Message>
     */
    public function open(Owner $owner, Ad $ad, int $buyerId): Collection
    {
        $seller = $owner->owns($ad);
        abort_unless($seller || $buyerId === $owner->userId, 403);

        Message::query()->where('ad_id', $ad->id)->where('buyer_id', $buyerId)
            ->where('from_seller', ! $seller)->whereNull('read_at')
            ->update(['read_at' => now()]);

        return Message::query()->where('ad_id', $ad->id)->where('buyer_id', $buyerId)->orderBy('id')->get();
    }

    /**
     * The threads of the owner's ads and the ones they started as a buyer,
     * newest first: {ad, buyer_id, last, unread, seller}.
     *
     * @return array<int,array<string,mixed>>
     */
    public function threads(Owner $owner): array
    {
        $rows = Message::query()
            ->where(fn (Builder $query) => $query->where('buyer_id', $owner->userId)->orWhereIn('ad_id', $owner->scope(Ad::query())->select('id')))
            ->selectRaw('ad_id, buyer_id, max(id) as last_id')
            ->groupBy('ad_id', 'buyer_id')
            ->orderByDesc('last_id')
            ->limit(200)
            ->get();

        $last = Message::query()->whereIn('id', $rows->pluck('last_id'))->get()->keyBy('id');
        $ads = Ad::query()->whereIn('id', $rows->pluck('ad_id'))->get()->keyBy('id');
        $threads = [];

        foreach ($rows as $row) {
            $ad = $ads->get($row->ad_id);

            if ($ad === null) {
                continue;
            }

            $seller = $owner->owns($ad);

            $threads[] = [
                'ad' => $ad,
                'buyer_id' => (int) $row->buyer_id,
                'last' => $last->get($row->last_id),
                'seller' => $seller,
                'unread' => Message::query()->where('ad_id', $ad->id)->where('buyer_id', $row->buyer_id)
                    ->where('from_seller', ! $seller)->whereNull('read_at')->count(),
            ];
        }

        return $threads;
    }

    public function unread(Owner $owner): int
    {
        return Message::query()->whereNull('read_at')
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $buyer) => $buyer->where('buyer_id', $owner->userId)->where('from_seller', true))
                ->orWhere(fn (Builder $seller) => $seller->where('from_seller', false)->whereIn('ad_id', $owner->scope(Ad::query())->select('id'))))
            ->count();
    }
}
