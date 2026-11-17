{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@php
    $k = 'atrium-image-map::image-map.';
    $layer = $area->layer;
    $shape = $area->shapeCase();
    $g = $area->geometry ?? [];
    $marker = $layer ? max(8, $layer->width / 90) : 8;
@endphp
@if ($layer && $layer->imageUrl() && $layer->width > 0 && $layer->height > 0)
    <div style="position:relative;max-width:480px;line-height:0;border:1px solid #767676;border-radius:6px;overflow:hidden">
        <img src="{{ $layer->imageUrl() }}" alt="" style="display:block;width:100%;height:auto">
        <svg viewBox="0 0 {{ $layer->width }} {{ $layer->height }}" preserveAspectRatio="none" aria-hidden="true"
             style="position:absolute;inset:0;width:100%;height:100%">
            @php($style = 'fill="rgba(0,114,178,.25)" stroke="#111" stroke-width="3" vector-effect="non-scaling-stroke"')
            @if ($shape === \Atrium\ImageMap\Shape::Rect && isset($g['x']))
                <rect x="{{ $g['x'] }}" y="{{ $g['y'] }}" width="{{ $g['width'] }}" height="{{ $g['height'] }}" {!! $style !!}/>
            @elseif ($shape === \Atrium\ImageMap\Shape::Circle && isset($g['cx']))
                <circle cx="{{ $g['cx'] }}" cy="{{ $g['cy'] }}" r="{{ $g['r'] }}" {!! $style !!}/>
            @elseif ($shape === \Atrium\ImageMap\Shape::Point && isset($g['x']))
                <circle cx="{{ $g['x'] }}" cy="{{ $g['y'] }}" r="{{ $marker }}" {!! $style !!}/>
            @elseif ($shape === \Atrium\ImageMap\Shape::Polygon && isset($g['points']))
                <polygon points="{{ collect($g['points'])->map(fn ($p) => $p[0].','.$p[1])->implode(' ') }}" {!! $style !!}/>
            @endif
        </svg>
    </div>
    <p style="margin:.5rem 0 0">
        <a href="{{ route('admin.image_map_layers.edit', ['id' => $layer->id]) }}" style="font-weight:600;text-decoration:underline">{{ __($k.'hints.open_editor') }}</a>
    </p>
@else
    <p style="margin:0">{{ __($k.'hints.drawing_later') }}</p>
@endif
