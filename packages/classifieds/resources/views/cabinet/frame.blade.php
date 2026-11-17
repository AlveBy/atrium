{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    The board's own cabinet frame, for a site without the personal cabinet
    module. With it, the pages sit in its frame and its menu.
--}}
@extends(view()->exists('atrium-theme::layout') ? 'atrium-theme::layout' : 'atrium-classifieds::layout')

@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
    @php($board = app(\Atrium\Classifieds\Board::class))
    <ul class="cl-nav">
        @foreach (['ads', 'messages', 'favourites', 'searches'] as $section)
            @php($url = $board->route('cabinet.'.$section))
            <li><a href="{{ $url }}" @if (url()->current() === $url) aria-current="page" @endif>{{ __('atrium-classifieds::classifieds.cabinet.'.$section) }}</a></li>
        @endforeach
    </ul>
    <h1>{{ $title }}</h1>
    @yield('cabinet')
@endsection
