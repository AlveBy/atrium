{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@php
    $k = 'atrium-image-map::image-map.';
    $item = ['id' => $status->id ?? 0, 'pattern' => $status->patternCase()->value, 'color' => $status->hex(), 'dash' => $status->outlineCase()->value];
    $ratio = $status->contrastRatio();
    $dash = ['dashed' => '9 5', 'dotted' => '0.1 6'][$item['dash']] ?? null;
@endphp
<div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
    <svg viewBox="0 0 120 72" width="120" height="72" role="img" aria-label="{{ __($k.'hints.preview_label') }}" style="background:#fff;border-radius:6px">
        @include('atrium-image-map::partials.patterns', ['statuses' => [$item], 'scope' => 'aim-status-preview', 'size' => 12])
        <rect x="8" y="8" width="104" height="56" rx="3" fill="{{ \Atrium\ImageMap\Support\MapPresenter::fill($item, 'aim-status-preview') }}"
              stroke="{{ $item['color'] }}" stroke-width="{{ $item['dash'] === 'thick' ? 5 : ($item['dash'] === 'dotted' ? 3.5 : 2.5) }}"
              @if ($dash) stroke-dasharray="{{ $dash }}" @endif @if ($item['dash'] === 'dotted') stroke-linecap="round" @endif/>
    </svg>
    <p style="margin:0">
        {{ __($k.'hints.contrast_on_white', ['ratio' => number_format($ratio, 2)]) }}<br>
        <strong>{{ __($k.($ratio >= \Atrium\ImageMap\Support\Contrast::GRAPHICS ? 'hints.contrast_ok' : 'hints.contrast_low')) }}</strong>
    </p>
</div>
