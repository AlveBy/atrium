{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- Threads about my ads and the ones I wrote as a buyer. --}}
@extends($cabinet ? 'atrium-account::cabinet' : 'atrium-classifieds::cabinet.frame')

@include('atrium-classifieds::partials.head')

@section('cabinet')
    @php($board = app(\Atrium\Classifieds\Board::class))
    @include('atrium-classifieds::cabinet.status')
    @if ($threads === [])
        <p class="empty">{{ __('atrium-classifieds::classifieds.cabinet.no_messages') }}</p>
    @else
        <table class="cl-table">
            <tbody>
            @foreach ($threads as $thread)
                <tr>
                    <td>
                        <a href="{{ $board->route('cabinet.thread', ['ad' => $thread['ad']->id, 'buyer' => $thread['buyer_id']]) }}">{{ $thread['ad']->title }}</a><br>
                        <span class="cl-meta">{{ $thread['seller'] ? __('atrium-classifieds::classifieds.cabinet.from_buyer') : __('atrium-classifieds::classifieds.cabinet.to_seller') }}
                            · {{ $thread['last']?->created_at?->translatedFormat('j M, H:i') }}</span>
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit((string) $thread['last']?->body, 80) }}</td>
                    <td>@if ($thread['unread'] > 0)<span class="cl-badge">{{ trans_choice('atrium-classifieds::classifieds.cabinet.unread', $thread['unread'], ['count' => $thread['unread']]) }}</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
