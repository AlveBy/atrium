{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- Colours come from the panel's own theme variables; the kind is always named in words. --}}
<ol data-tracker-thread style="list-style:none;margin:0;padding:0;display:grid;gap:.75rem">
    @forelse ($entries as $entry)
        <li data-tracker-entry="{{ $entry['kind'] }}" style="border:1px solid var(--atrium-line);border-radius:.5rem;padding:.625rem .875rem;{{ $entry['kind'] === 'comment' ? 'background:var(--atrium-surface)' : 'background:var(--atrium-surface-alt)' }}">
            <div style="font-size:.8125rem;color:var(--atrium-muted)">
                <strong style="color:var(--atrium-fg)">{{ $entry['who'] ?? '—' }}</strong>
                · {{ __('atrium-tracker::tracker.kinds.'.$entry['kind']) }}
                @if ($entry['at'])
                    · <time datetime="{{ $entry['at']->toIso8601String() }}">{{ $entry['at']->isoFormat('LLL') }}</time>
                @endif
            </div>
            <div style="margin-top:.25rem;white-space:pre-wrap;overflow-wrap:anywhere">{{ $entry['text'] }}</div>
        </li>
    @empty
        <li style="color:var(--atrium-muted)">{{ __('atrium-tracker::tracker.thread_empty') }}</li>
    @endforelse
</ol>
