{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@php($k = 'atrium-image-map::image-map.')
@if ($layer->exists && $layer->imageUrl() !== null && $layer->width > 0 && $layer->height > 0)
    <iframe src="{{ route('admin.image-map.editor', $layer->id) }}" title="{{ __($k.'editor.frame_title', ['layer' => $layer->option_label]) }}"
            data-image-map-editor
            style="display:block;width:100%;height:min(82vh,780px);border:1px solid #767676;border-radius:8px;background:#fff"></iframe>
    <p style="margin:.5rem 0 0;font-size:.875rem;opacity:.85">{{ __($k.'editor.frame_hint') }}</p>
@else
    <p style="margin:0;font-size:.875rem">{{ __($k.'editor.no_image') }}</p>
@endif
