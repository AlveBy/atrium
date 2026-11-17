{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
@php($t = fn (string $key) => __('atrium-image-map::image-map.editor.'.$key))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $t('title') }} — {{ $layer->option_label }}</title>
    <link rel="stylesheet" href="{{ $css }}">
</head>
<body>
<div class="ed" id="ed">
    <div class="ed-toolbar" role="toolbar" aria-label="{{ $t('toolbar') }}" aria-controls="ed-canvas">
        <div class="ed-group" role="group" aria-label="{{ $t('tools') }}" data-tools>
            <button type="button" class="ed-btn" data-tool="select" aria-pressed="true" aria-keyshortcuts="V" title="{{ $t('tool_select') }} (V)">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 2l10 9-4.5.6L13 17l-2.2 1-2.5-5.4L5 15z"/></svg><span>{{ $t('tool_select') }}</span>
            </button>
            <button type="button" class="ed-btn" data-tool="rect" aria-pressed="false" aria-keyshortcuts="R" title="{{ $t('tool_rect') }} (R)" data-create>
                <svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="5" width="14" height="10" fill="none" stroke-width="2"/></svg><span>{{ $t('tool_rect') }}</span>
            </button>
            <button type="button" class="ed-btn" data-tool="polygon" aria-pressed="false" aria-keyshortcuts="P" title="{{ $t('tool_polygon') }} (P)" data-create>
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 7l6-4 8 4-2 9H5z" fill="none" stroke-width="2"/></svg><span>{{ $t('tool_polygon') }}</span>
            </button>
            <button type="button" class="ed-btn" data-tool="circle" aria-pressed="false" aria-keyshortcuts="C" title="{{ $t('tool_circle') }} (C)" data-create>
                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="7" fill="none" stroke-width="2"/></svg><span>{{ $t('tool_circle') }}</span>
            </button>
            <button type="button" class="ed-btn" data-tool="point" aria-pressed="false" aria-keyshortcuts="M" title="{{ $t('tool_point') }} (M)" data-create>
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 18s6-6.2 6-10.5a6 6 0 10-12 0C4 11.8 10 18 10 18z" fill="none" stroke-width="2"/><circle cx="10" cy="7.5" r="2"/></svg><span>{{ $t('tool_point') }}</span>
            </button>
        </div>
        <div class="ed-group" role="group" aria-label="{{ $t('history') }}">
            <button type="button" class="ed-btn" data-cmd="undo" aria-keyshortcuts="Control+Z Meta+Z" title="{{ $t('undo') }} (Ctrl+Z)">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M7 5L3 9l4 4M3.5 9H12a5 5 0 010 10h-2" fill="none" stroke-width="2"/></svg><span>{{ $t('undo') }}</span>
            </button>
            <button type="button" class="ed-btn" data-cmd="redo" aria-keyshortcuts="Control+Shift+Z Control+Y" title="{{ $t('redo') }} (Ctrl+Shift+Z)">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M13 5l4 4-4 4M16.5 9H8a5 5 0 000 10h2" fill="none" stroke-width="2"/></svg><span>{{ $t('redo') }}</span>
            </button>
        </div>
        <div class="ed-group" role="group" aria-label="{{ $t('view') }}">
            <button type="button" class="ed-btn" data-cmd="snap" aria-pressed="true" aria-keyshortcuts="S" title="{{ $t('snap') }} (S)">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 4v6a6 6 0 0012 0V4h-4v6a2 2 0 01-4 0V4z" fill="none" stroke-width="2"/></svg><span>{{ $t('snap') }}</span>
            </button>
            <button type="button" class="ed-btn" data-cmd="grid" aria-pressed="false" aria-keyshortcuts="G" title="{{ $t('grid') }} (G)">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 7h14M3 13h14M7 3v14M13 3v14" fill="none" stroke-width="1.6"/></svg><span>{{ $t('grid') }}</span>
            </button>
            <button type="button" class="ed-btn ed-icon" data-cmd="zoom-out" aria-keyshortcuts="-" title="{{ $t('zoom_out') }} (−)" aria-label="{{ $t('zoom_out') }}">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 10h10" stroke-width="2"/></svg>
            </button>
            <output class="ed-zoom" data-zoom aria-live="off">100%</output>
            <button type="button" class="ed-btn ed-icon" data-cmd="zoom-in" aria-keyshortcuts="+" title="{{ $t('zoom_in') }} (+)" aria-label="{{ $t('zoom_in') }}">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M5 10h10M10 5v10" stroke-width="2"/></svg>
            </button>
            <button type="button" class="ed-btn" data-cmd="fit" aria-keyshortcuts="0" title="{{ $t('fit') }} (0)">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 7V3h4M13 3h4v4M17 13v4h-4M7 17H3v-4" fill="none" stroke-width="2"/></svg><span>{{ $t('fit') }}</span>
            </button>
        </div>
        <div class="ed-group ed-save" role="group" aria-label="{{ $t('saving_group') }}">
            <p class="ed-status" data-status role="status" aria-live="polite"></p>
            <button type="button" class="ed-btn ed-primary" data-cmd="save" aria-keyshortcuts="Control+S Meta+S" title="{{ $t('save') }} (Ctrl+S)" disabled>
                <span>{{ $t('save') }}</span>
            </button>
        </div>
    </div>

    <div class="ed-main">
        <div class="ed-canvas" id="ed-canvas" data-canvas tabindex="0" role="application"
             aria-label="{{ $t('canvas') }}" aria-describedby="ed-help">
            <svg class="ed-svg" data-svg xmlns="http://www.w3.org/2000/svg"></svg>
            <p class="ed-hint" data-hint aria-live="polite"></p>
        </div>

        <aside class="ed-side" aria-label="{{ $t('areas') }}">
            <h1 class="ed-h">{{ $t('areas') }} <span data-count></span></h1>
            <ul class="ed-list" data-list></ul>
            <p class="ed-empty" data-empty>{{ $t('empty') }}</p>

            <form class="ed-props" data-props hidden onsubmit="return false">
                <h2 class="ed-h">{{ $t('selected') }}</h2>
                <label for="ed-title">{{ $t('area_title') }} <span class="ed-lang" data-lang></span></label>
                <input id="ed-title" type="text" maxlength="255" data-prop="title" autocomplete="off">
                <label for="ed-status">{{ $t('status') }}</label>
                <select id="ed-status" data-prop="status_id"></select>
                <label for="ed-action">{{ $t('action') }}</label>
                <select id="ed-action" data-prop="action"></select>
                <p class="ed-error" data-error role="alert" hidden></p>
                <div class="ed-props-actions">
                    <a class="ed-link" data-details target="_top" href="#">{{ $t('details') }}</a>
                    <span class="ed-muted" data-details-later>{{ $t('details_later') }}</span>
                    <button type="button" class="ed-btn ed-danger" data-cmd="delete" aria-keyshortcuts="Delete">{{ $t('delete') }}</button>
                </div>
            </form>

            <details class="ed-help" id="ed-help">
                <summary>{{ $t('help') }}</summary>
                <ul>
                    @foreach (['help_draw', 'help_polygon', 'help_vertex', 'help_move', 'help_pan', 'help_keys'] as $line)
                        <li>{{ $t($line) }}</li>
                    @endforeach
                </ul>
            </details>
        </aside>
    </div>
</div>
<script type="application/json" id="ed-state">@json($state, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
<script type="module" src="{{ $js }}"></script>
</body>
</html>
