{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- My ads: a person's own, or every ad of the company. --}}
@extends($cabinet ? 'atrium-account::cabinet' : 'atrium-classifieds::cabinet.frame')

@include('atrium-classifieds::partials.head')

@section('cabinet')
    @php($board = app(\Atrium\Classifieds\Board::class))
    @include('atrium-classifieds::cabinet.status')

    <p><a class="cl-button" href="{{ $board->route('cabinet.create') }}">{{ __('atrium-classifieds::classifieds.post') }}</a></p>

    <ul class="cl-nav" aria-label="{{ __('atrium-classifieds::classifieds.fields.status') }}">
        <li><a href="{{ $board->route('cabinet.ads') }}" @if ($status === null) aria-current="page" @endif>{{ __('atrium-classifieds::classifieds.filters.all') }}</a></li>
        @foreach (\Atrium\Classifieds\Enums\AdStatus::cases() as $case)
            <li><a href="{{ $board->route('cabinet.ads', ['status' => $case->value]) }}" @if ($status === $case) aria-current="page" @endif>{{ $case->label() }}</a></li>
        @endforeach
    </ul>

    @if ($ads->isEmpty())
        <p class="empty">{{ __('atrium-classifieds::classifieds.cabinet.no_ads') }}</p>
    @else
        <table class="cl-table">
            <thead><tr>
                <th>{{ __('atrium-classifieds::classifieds.fields.title') }}</th>
                <th>{{ __('atrium-classifieds::classifieds.fields.status') }}</th>
                <th>{{ __('atrium-classifieds::classifieds.fields.expires_at') }}</th>
                <th><span class="skip">{{ __('atrium-classifieds::classifieds.cabinet.actions') }}</span></th>
            </tr></thead>
            <tbody>
            @foreach ($ads as $ad)
                <tr>
                    <td>
                        <a href="{{ $ad->url() }}">{{ $ad->title }}</a><br>
                        <span class="cl-meta">{{ $ad->priceText() }} · {{ $ad->category?->name }} · {{ trans_choice('atrium-classifieds::classifieds.views', $ad->views, ['count' => $ad->views]) }}</span>
                        @if ($ad->isVip())<span class="cl-badge">{{ __('atrium-classifieds::classifieds.badge.vip') }}</span>@endif
                        @if ($ad->isHighlighted())<span class="cl-badge">{{ __('atrium-classifieds::classifieds.badge.highlighted') }}</span>@endif
                    </td>
                    <td>
                        {{ $ad->status->label() }}
                        @if ($ad->status === \Atrium\Classifieds\Enums\AdStatus::Rejected && $ad->reject_reason)
                            <br><span class="cl-meta">{{ __('atrium-classifieds::classifieds.fields.reject_reason') }}: {{ $ad->reject_reason }}</span>
                        @endif
                    </td>
                    <td>{{ $ad->expires_at?->translatedFormat('j M Y') ?? '—' }}</td>
                    <td>
                        <div class="cl-actions">
                            @if ($ad->status->isEditable())
                                <a class="cl-button secondary" href="{{ $board->route('cabinet.edit', ['ad' => $ad->id]) }}">{{ __('atrium-classifieds::classifieds.cabinet.edit') }}</a>
                            @endif
                            @if ($ad->status->canSubmit())
                                <form method="post" action="{{ $board->route('cabinet.submit', ['ad' => $ad->id]) }}">@csrf<button class="cl-button" type="submit">{{ __('atrium-classifieds::classifieds.cabinet.submit') }}</button></form>
                            @endif
                            @if (in_array($ad->status, [\Atrium\Classifieds\Enums\AdStatus::Active, \Atrium\Classifieds\Enums\AdStatus::Expired], true))
                                @if ($ad->renewals < $renewals)
                                    <form method="post" action="{{ $board->route('cabinet.renew', ['ad' => $ad->id]) }}">@csrf<button class="cl-button secondary" type="submit">{{ __('atrium-classifieds::classifieds.cabinet.renew') }}</button></form>
                                @endif
                                <form method="post" action="{{ $board->route('cabinet.sold', ['ad' => $ad->id]) }}">@csrf<button class="cl-button secondary" type="submit">{{ __('atrium-classifieds::classifieds.cabinet.sold') }}</button></form>
                            @endif
                            @if ($ad->isPublished())
                                <form method="post" action="{{ $board->route('cabinet.boost', ['ad' => $ad->id]) }}">
                                    @csrf
                                    <label class="skip" for="boost-{{ $ad->id }}">{{ __('atrium-classifieds::classifieds.cabinet.boost') }}</label>
                                    <select id="boost-{{ $ad->id }}" name="type">
                                        @foreach ($boosts as $offer)
                                            <option value="{{ $offer['type']->value }}">{{ $offer['type']->label() }} — {{ $offer['amount'] > 0 ? $board->money($offer['amount'], $offer['currency']) : __('atrium-classifieds::classifieds.price.free') }}</option>
                                        @endforeach
                                    </select>
                                    <button class="cl-button" type="submit">{{ __('atrium-classifieds::classifieds.cabinet.boost') }}</button>
                                </form>
                            @endif
                            <form method="post" action="{{ $board->route('cabinet.destroy', ['ad' => $ad->id]) }}" onsubmit="return confirm(@js(__('atrium-classifieds::classifieds.cabinet.confirm_delete')))">
                                @csrf @method('DELETE')
                                <button class="cl-button secondary" type="submit">{{ __('atrium-classifieds::classifieds.cabinet.delete') }}</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @include('atrium-classifieds::partials.pagination', ['paginator' => $ads])
    @endif
@endsection
