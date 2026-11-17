{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #16181d;">
    <p>{{ __('atrium-classifieds::classifieds.mail.status_body.'.$ad->status->value, ['title' => $ad->title]) }}</p>
    @if ($ad->status === \Atrium\Classifieds\Enums\AdStatus::Rejected && $ad->reject_reason)
        <p><strong>{{ __('atrium-classifieds::classifieds.fields.reject_reason') }}:</strong> {{ $ad->reject_reason }}</p>
    @endif
    <p><a href="{{ $url }}" style="color: #1d4ed8;">{{ __('atrium-classifieds::classifieds.mail.open_cabinet') }}</a></p>
</body>
</html>
