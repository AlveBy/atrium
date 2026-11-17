{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@php
    $t = fn (string $key, array $replace = []) => __('atrium-image-map::image-map.front.'.$key, $replace);
    $tabs = count($layers) > 1;
@endphp
@if ($assets)
    <link rel="stylesheet" href="{{ \Atrium\ImageMap\Support\Assets::url('image-map.css') }}">
    <script type="module" src="{{ \Atrium\ImageMap\Support\Assets::url('image-map.js') }}"></script>
@endif
<div class="aim" id="{{ $id }}" data-aim
     data-i18n-found="{{ $t('found') }}" data-i18n-none="{{ $t('none') }}" data-i18n-close="{{ $t('close') }}">
    @if ($description !== '')
        <p class="aim-desc">{{ $description }}</p>
    @endif

    @if ($tabs || $showSearch)
        <div class="aim-bar">
            @if ($tabs)
                <div class="aim-floors" role="tablist" aria-label="{{ $t('floors', ['map' => $name]) }}" data-aim-tabs>
                    @foreach ($layers as $layer)
                        <button type="button" role="tab" class="aim-floor" id="{{ $layer['id'] }}-tab"
                                aria-controls="{{ $layer['id'] }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                @unless ($loop->first) tabindex="-1" @endunless data-aim-tab>
                            {{ $layer['name'] }}<span class="aim-count" data-aim-count hidden></span>
                        </button>
                    @endforeach
                </div>
            @endif

            @if ($showSearch)
                <div class="aim-search" role="search">
                    <label for="{{ $id }}-q">{{ $t('search') }}</label>
                    <input type="search" id="{{ $id }}-q" autocomplete="off" data-aim-search
                           placeholder="{{ $t('search_placeholder') }}" aria-describedby="{{ $id }}-found">
                    <p class="aim-found" id="{{ $id }}-found" aria-live="polite" data-aim-found></p>
                </div>
            @endif
        </div>
    @endif

    @foreach ($layers as $layer)
        <section class="aim-layer" id="{{ $layer['id'] }}" data-aim-layer
                 @if ($tabs) role="tabpanel" aria-labelledby="{{ $layer['id'] }}-tab" @else aria-label="{{ $layer['name'] }}" @endif>
            <div class="aim-stage" style="aspect-ratio: {{ $layer['width'] }} / {{ $layer['height'] }}">
                <img class="aim-img" src="{{ $layer['image'] }}" alt="{{ $t('plan', ['map' => $name, 'layer' => $layer['name']]) }}"
                     width="{{ $layer['width'] }}" height="{{ $layer['height'] }}" @unless ($loop->first) loading="lazy" @endunless decoding="async">
                <svg class="aim-svg" viewBox="0 0 {{ $layer['width'] }} {{ $layer['height'] }}" preserveAspectRatio="none"
                     role="group" aria-label="{{ $t('areas_of', ['layer' => $layer['name']]) }}">
                    @if ($statuses !== [])
                        @include('atrium-image-map::partials.patterns', ['statuses' => $statuses, 'scope' => $layer['id'], 'size' => max(8, $layer['width'] / 90)])
                    @endif
                    @foreach ($layer['areas'] as $area)
                        @php
                            $status = $area['status'] === null ? null : ($statuses[$area['status']] ?? null);
                            $color = $status['color'] ?? \Atrium\ImageMap\Support\MapPresenter::DEFAULT_COLOR;
                            $shapeAttributes = collect($area['svg']['attributes'])->map(fn ($v, $k) => $k.'="'.e($v).'"')->implode(' ');
                        @endphp
                        @if ($area['action'] === 'link')
                            <a class="aim-area" href="{{ $area['url'] }}" id="{{ $area['dom'] }}" data-aim-area data-action="link"
                               aria-label="{{ $area['label'] }}" data-search="{{ $area['search'] }}" data-shape="{{ $area['shape'] }}"
                               @if ($area['external']) target="_blank" rel="noopener" @endif>
                        @else
                            <g class="aim-area" id="{{ $area['dom'] }}" tabindex="0" role="button" data-aim-area data-action="{{ $area['action'] }}"
                               aria-label="{{ $area['label'] }}" data-search="{{ $area['search'] }}" data-shape="{{ $area['shape'] }}"
                               @if ($area['action'] !== 'tooltip') aria-haspopup="dialog" @endif>
                        @endif
                            <{{ $area['svg']['tag'] }} class="aim-halo" {!! $shapeAttributes !!}/>
                            <{{ $area['svg']['tag'] }} class="aim-shape aim-o-{{ $status['dash'] ?? 'solid' }}" {!! $shapeAttributes !!}
                                fill="{{ \Atrium\ImageMap\Support\MapPresenter::fill($status, $layer['id']) }}" stroke="{{ $color }}"/>
                            @if ($area['shape'] === 'point')
                                <circle class="aim-dot" cx="{{ $area['svg']['attributes']['cx'] }}" cy="{{ $area['svg']['attributes']['cy'] }}" r="{{ round($area['svg']['marker'] / 3, 1) }}" fill="{{ $color }}"/>
                            @endif
                        @if ($area['action'] === 'link')
                            </a>
                        @else
                            </g>
                        @endif
                    @endforeach
                </svg>
            </div>
        </section>
    @endforeach

    @if ($showLegend)
        <div class="aim-legend">
            <p class="aim-legend-title" id="{{ $id }}-legend">{{ $t('legend') }}</p>
            <ul aria-labelledby="{{ $id }}-legend">
                @foreach ($legend as $item)
                    <li>
                        <svg class="aim-swatch" viewBox="0 0 36 24" width="36" height="24" aria-hidden="true" focusable="false">
                            @include('atrium-image-map::partials.patterns', ['statuses' => [$item], 'scope' => $id.'-lg', 'size' => 8])
                            <rect x="2" y="2" width="32" height="20" rx="2" class="aim-shape aim-o-{{ $item['dash'] }}"
                                  fill="{{ \Atrium\ImageMap\Support\MapPresenter::fill($item, $id.'-lg') }}" stroke="{{ $item['color'] }}"/>
                        </svg>
                        <span>{{ $item['name'] }}</span> <span class="aim-legend-count">{{ $t('count', ['count' => $item['count']]) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($showList)
        <details class="aim-list" data-aim-list>
            <summary>{{ $t('list', ['count' => $areaCount]) }}</summary>
            @foreach ($layers as $layer)
                @if ($layer['areas'] !== [])
                    @if ($tabs)
                        <{{ $headingTag }} class="aim-list-layer">{{ $layer['name'] }}</{{ $headingTag }}>
                    @endif
                    <ul>
                        @foreach ($layer['areas'] as $area)
                            <li data-aim-item="{{ $area['dom'] }}" data-search="{{ $area['search'] }}">
                                @if ($area['action'] === 'link')
                                    <a href="{{ $area['url'] }}" @if ($area['external']) target="_blank" rel="noopener" @endif>{{ $area['title'] }}</a>
                                @else
                                    <button type="button" class="aim-list-open" data-aim-open="{{ $area['dom'] }}" aria-haspopup="dialog">{{ $area['title'] }}</button>
                                @endif
                                @if ($area['statusName'] !== '')
                                    <span class="aim-list-status">{{ $area['statusName'] }}</span>
                                @endif
                                @if ($area['description'] !== '')
                                    <span class="aim-list-desc">{{ $area['description'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endforeach
        </details>
    @endif

    @foreach ($layers as $layer)
        @foreach ($layer['areas'] as $area)
            @if ($area['action'] !== 'link')
                <template id="{{ $area['dom'] }}-content">
                    @include('atrium-image-map::partials.content', ['area' => $area, 'headingTag' => $headingTag])
                </template>
            @endif
        @endforeach
    @endforeach
</div>
