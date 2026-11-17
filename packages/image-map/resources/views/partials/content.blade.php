{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- What an area opens: its title, status, text and the card of the linked record. --}}
<div class="aim-content">
    <{{ $headingTag }} class="aim-content-title" data-aim-title>{{ ($area['card']['title'] ?? '') ?: $area['title'] }}</{{ $headingTag }}>
    @if ($area['statusName'] !== '')
        <p class="aim-content-status">{{ $area['statusName'] }}</p>
    @endif
    @if ($area['description'] !== '')
        <p class="aim-content-desc">{{ $area['description'] }}</p>
    @endif
    @if ($area['html'] !== '')
        {{-- Cleaned when saved: the text level for rich text, the custom level for HTML. --}}
        <div class="aim-content-html">{!! $area['html'] !!}</div>
    @endif
    @if ($area['card'] !== null)
        @include('atrium-image-map::partials.card', ['card' => $area['card']])
    @endif
</div>
