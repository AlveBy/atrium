{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- One pattern per status, so a status is told by its texture as well as its colour. --}}
@php
    $s = round((float) $size, 1);
    $h = round($s / 2, 1);
    $w = round(max(1, $s / 7), 2);
@endphp
<defs>
    @foreach ($statuses as $status)
        @continue($status['pattern'] === 'none')
        <pattern id="{{ $scope }}-p{{ $status['id'] }}" patternUnits="userSpaceOnUse" width="{{ $s }}" height="{{ $s }}">
            <rect width="{{ $s }}" height="{{ $s }}" fill="{{ $status['color'] }}" fill-opacity="{{ $status['pattern'] === 'solid' ? '.32' : '.12' }}"/>
            @switch($status['pattern'])
                @case('hatch')
                    <path d="M0,{{ $s }} L{{ $s }},0 M-{{ $h }},{{ $h }} L{{ $h }},-{{ $h }} M{{ $h }},{{ $s + $h }} L{{ $s + $h }},{{ $h }}" stroke="{{ $status['color'] }}" stroke-width="{{ $w }}"/>
                    @break
                @case('cross')
                    <path d="M0,{{ $s }} L{{ $s }},0 M0,0 L{{ $s }},{{ $s }}" stroke="{{ $status['color'] }}" stroke-width="{{ $w }}"/>
                    @break
                @case('dots')
                    <circle cx="{{ $h }}" cy="{{ $h }}" r="{{ round($s / 6, 2) }}" fill="{{ $status['color'] }}"/>
                    @break
                @case('vertical')
                    <path d="M{{ $h }},0 V{{ $s }}" stroke="{{ $status['color'] }}" stroke-width="{{ $w }}"/>
                    @break
                @case('horizontal')
                    <path d="M0,{{ $h }} H{{ $s }}" stroke="{{ $status['color'] }}" stroke-width="{{ $w }}"/>
                    @break
            @endswitch
        </pattern>
    @endforeach
</defs>
