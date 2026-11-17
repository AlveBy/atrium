{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- Ad cards, read from the card cache: no query per ad. --}}
@if ($cards === [])
    <p class="empty">{{ __('atrium-classifieds::classifieds.empty') }}</p>
@else
    <ul class="cl-cards">
        @foreach ($cards as $card)
            @php($vip = ($card['vip_until'] ?? 0) > now()->getTimestamp())
            @php($highlighted = ($card['highlighted_until'] ?? 0) > now()->getTimestamp())
            <li class="cl-card @if ($vip) is-vip @endif @if ($highlighted) is-highlighted @endif">
                @if ($vip || $highlighted)
                    <span class="cl-badges">
                        @if ($vip)<span class="cl-badge">{{ __('atrium-classifieds::classifieds.badge.vip') }}</span>@endif
                        @if ($highlighted)<span class="cl-badge">{{ __('atrium-classifieds::classifieds.badge.highlighted') }}</span>@endif
                    </span>
                @endif
                @if ($card['photo'])
                    <img src="{{ $card['photo'] }}" alt="" loading="lazy">
                @else
                    <div class="cl-nophoto" aria-hidden="true"></div>
                @endif
                <div class="cl-card-body">
                    <h2><a href="{{ $card['url'] }}">{{ $card['title'] }}</a></h2>
                    <div class="cl-price">{{ $card['price'] }}</div>
                    <div class="cl-meta">
                        {{ $card['city'] }}@if ($card['city'] && $card['published_at']) · @endif
                        @if ($card['published_at'])<time datetime="{{ $card['published_at'] }}">{{ \Illuminate\Support\Carbon::parse($card['published_at'])->translatedFormat('j M') }}</time>@endif
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
