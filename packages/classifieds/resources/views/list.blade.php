{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- The board, a category, a city or a company: one list with filters. --}}
@extends(view()->exists('atrium-theme::layout') ? 'atrium-theme::layout' : 'atrium-classifieds::layout', ['switch' => $alternates])

@include('atrium-classifieds::partials.head')

@section('content')
    <h1>{{ $heading }}</h1>

    @if ($category?->description)
        <p class="cl-meta">{{ $category->description }}</p>
    @endif

    @if ($categories->isNotEmpty())
        <ul class="cl-subcats">
            @foreach ($categories as $child)
                <li><a href="{{ $city ? app(\Atrium\Classifieds\Board::class)->route('city', ['city' => $city->code, 'path' => $child->path()]) : $child->url() }}">{{ $child->name }}</a></li>
            @endforeach
        </ul>
    @endif

    <p class="cl-meta">
        {{ trans_choice('atrium-classifieds::classifieds.found', $ads->total(), ['count' => $ads->total()]) }}
        · <a href="{{ app(\Atrium\Classifieds\Board::class)->route('cabinet.create', array_filter(['category' => $category?->id])) }}">{{ __('atrium-classifieds::classifieds.post') }}</a>
    </p>

    <div class="cl-layout">
        <aside>@include('atrium-classifieds::partials.filters')</aside>
        <div>
            @include('atrium-classifieds::partials.cards', ['cards' => $cards])
            @include('atrium-classifieds::partials.pagination', ['paginator' => $ads])
        </div>
    </div>
@endsection
