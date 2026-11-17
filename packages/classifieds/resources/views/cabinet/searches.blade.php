{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@extends($cabinet ? 'atrium-account::cabinet' : 'atrium-classifieds::cabinet.frame')

@include('atrium-classifieds::partials.head')

@section('cabinet')
    @php($board = app(\Atrium\Classifieds\Board::class))
    @include('atrium-classifieds::cabinet.status')
    @if ($searches->isEmpty())
        <p class="empty">{{ __('atrium-classifieds::classifieds.cabinet.no_searches') }}</p>
    @else
        <table class="cl-table">
            <tbody>
            @foreach ($searches as $search)
                <tr>
                    <td><a href="{{ $board->route('index', $search->filters) }}">{{ $search->name }}</a></td>
                    <td>{{ $search->notify ? __('atrium-classifieds::classifieds.searches.notify_on') : __('atrium-classifieds::classifieds.searches.notify_off') }}</td>
                    <td>
                        <div class="cl-actions">
                            <form method="post" action="{{ $board->route('cabinet.searches.update', ['search' => $search->id]) }}">@csrf @method('PATCH')
                                <button class="cl-button secondary" type="submit">{{ $search->notify ? __('atrium-classifieds::classifieds.searches.turn_off') : __('atrium-classifieds::classifieds.searches.turn_on') }}</button></form>
                            <form method="post" action="{{ $board->route('cabinet.searches.destroy', ['search' => $search->id]) }}">@csrf @method('DELETE')
                                <button class="cl-button secondary" type="submit">{{ __('atrium-classifieds::classifieds.cabinet.delete') }}</button></form>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
