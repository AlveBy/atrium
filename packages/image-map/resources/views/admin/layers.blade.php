{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@php($k = 'atrium-image-map::image-map.')
@if ($layers->isEmpty())
    <p style="margin:0">{{ __($k.'hints.no_layers') }}</p>
@else
    <ul style="margin:0;padding:0;list-style:none;display:grid;gap:.4rem">
        @foreach ($layers as $layer)
            <li style="display:flex;gap:.75rem;align-items:center">
                <a href="{{ route('admin.image_map_layers.edit', ['id' => $layer->id]) }}" style="font-weight:600;text-decoration:underline">{{ $layer->getTranslation('name', app()->getLocale()) ?: '#'.$layer->id }}</a>
                <span style="opacity:.8">{{ trans_choice($k.'hints.area_count', $layer->areas_count, ['count' => $layer->areas_count]) }}</span>
            </li>
        @endforeach
    </ul>
@endif
@if ($map->exists && $map->code)
    <p style="margin:.75rem 0 .25rem">{{ __($k.'hints.embed') }}</p>
    <code style="display:inline-block;padding:.25rem .5rem;border:1px solid #767676;border-radius:4px;user-select:all">&lt;x-atrium-image-map::map map="{{ $map->code }}" /&gt;</code>
@endif
