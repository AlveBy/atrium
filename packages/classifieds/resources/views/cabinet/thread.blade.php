{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($cabinet ? 'atrium-account::cabinet' : 'atrium-classifieds::cabinet.frame')

@include('atrium-classifieds::partials.head')

@section('cabinet')
    @php($board = app(\Atrium\Classifieds\Board::class))
    @include('atrium-classifieds::cabinet.status')
    <p><a href="{{ $ad->url() }}">{{ $ad->title }}</a> · {{ $ad->priceText() }}</p>
    <ol class="cl-thread">
        @foreach ($messages as $message)
            @php($own = $message->from_seller === $seller)
            <li class="@if ($own) mine @endif">
                <div class="cl-meta">{{ $own ? __('atrium-classifieds::classifieds.cabinet.you') : ($message->from_seller ? __('atrium-classifieds::classifieds.cabinet.seller') : __('atrium-classifieds::classifieds.cabinet.buyer')) }} · {{ $message->created_at?->translatedFormat('j M, H:i') }}</div>
                {!! nl2br(e($message->body)) !!}
            </li>
        @endforeach
    </ol>
    <form class="cl-form" method="post" action="{{ $board->route('cabinet.reply', ['ad' => $ad->id, 'buyer' => $buyer]) }}">
        @csrf
        <label for="cl-reply">{{ __('atrium-classifieds::classifieds.cabinet.reply') }}</label>
        <textarea id="cl-reply" name="body" rows="4" maxlength="2000" required></textarea>
        <p><button type="submit" class="cl-button">{{ __('atrium-classifieds::classifieds.send') }}</button></p>
    </form>
@endsection
