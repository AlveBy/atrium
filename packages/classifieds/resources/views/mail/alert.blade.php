{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #16181d;">
    <p>{{ __('atrium-classifieds::classifieds.mail.alert_body', ['name' => $search->name]) }}</p>
    <ul>
        @foreach ($cards as $card)
            <li><a href="{{ $card['url'] }}" style="color: #1d4ed8;">{{ $card['title'] }}</a> — {{ $card['price'] }}@if ($card['city']), {{ $card['city'] }}@endif</li>
        @endforeach
    </ul>
    <p><a href="{{ $url }}" style="color: #1d4ed8;">{{ __('atrium-classifieds::classifieds.mail.alert_all') }}</a></p>
    <p style="color: #4f5662; font-size: 14px;">{{ __('atrium-classifieds::classifieds.mail.alert_unsubscribe') }}</p>
</body>
</html>
