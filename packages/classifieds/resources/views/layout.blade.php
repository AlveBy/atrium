{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- A plain frame for the board on a site without the public theme. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metaTitle ?? '' }}</title>
    @if (! empty($metaDescription))
    <meta name="description" content="{{ $metaDescription }}">
    @endif
    @if (! empty($canonical))
    <link rel="canonical" href="{{ $canonical }}">
    @endif
    <x-atrium::hreflang :urls="$alternates ?? []" />
    <x-atrium::head />
    <style>
        :root { --bg: #fff; --surface: #f3f4f6; --text: #16181d; --muted: #4f5662; --accent: #1d4ed8; --control: #6b7280; --line: #e2e5ea; --focus: #1d4ed8; }
        body { margin: 0 auto; max-width: 72rem; padding: 2rem 1.25rem; font: 1rem/1.6 system-ui, sans-serif; color: var(--text); background: var(--bg); }
        a { color: var(--accent); }
    </style>
    @stack('head')
</head>
<body>
    <x-atrium::body-start />
    <main id="content">
        @if (! empty($breadcrumbs))
            <nav class="breadcrumbs" aria-label="breadcrumbs">
                <ol>
                    @foreach ($breadcrumbs as $crumb)
                        <li><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
                    @endforeach
                </ol>
            </nav>
        @endif
        @yield('content')
    </main>
    <x-atrium::body-end />
</body>
</html>
