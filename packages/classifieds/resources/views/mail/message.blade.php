{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{-- Only a link: the message itself is personal data and stays on the site. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #16181d;">
    <p>{{ __('atrium-classifieds::classifieds.mail.message_body', ['title' => $ad->title]) }}</p>
    <p><a href="{{ $url }}" style="color: #1d4ed8;">{{ __('atrium-classifieds::classifieds.mail.open_thread') }}</a></p>
</body>
</html>
