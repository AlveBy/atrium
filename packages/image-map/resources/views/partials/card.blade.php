{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
<div class="aim-card">
    @if ($card['image'])
        <img class="aim-card-img" src="{{ $card['image'] }}" alt="" loading="lazy" decoding="async">
    @endif
    @if ($card['text'])
        <p class="aim-card-text">{{ $card['text'] }}</p>
    @endif
    @if ($card['lines'] !== [])
        <ul class="aim-card-lines">
            @foreach ($card['lines'] as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    @endif
    @if ($card['url'])
        <a class="aim-card-more" href="{{ $card['url'] }}">{{ __('atrium-image-map::image-map.front.more') }}</a>
    @endif
</div>
