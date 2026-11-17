{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    The page of an ad. The contacts are not in the page: the button asks for
    them (rate limited), so a crawler reading the board gets no phones.
--}}
@php($board = app(\Atrium\Classifieds\Board::class))
@extends(view()->exists('atrium-theme::layout') ? 'atrium-theme::layout' : 'atrium-classifieds::layout', ['switch' => $alternates, 'ogType' => 'product'])

@include('atrium-classifieds::partials.head')

@section('content')
    @if (session('atrium-classifieds.status'))
        <p class="cl-status" role="status">{{ session('atrium-classifieds.status') }}</p>
    @endif

    @if (! $ad->isPublished())
        <p class="cl-status" role="status">{{ __('atrium-classifieds::classifieds.not_on_site', ['status' => $ad->status->label()]) }}
            @if ($ad->reject_reason) — {{ $ad->reject_reason }}@endif</p>
    @endif

    <h1>{{ $ad->title }}
        @if ($ad->isVip()) <span class="cl-badge">{{ __('atrium-classifieds::classifieds.badge.vip') }}</span>@endif
    </h1>

    <div class="cl-ad">
        <div>
            <div class="cl-price">{{ $ad->priceText() }}</div>
            <div class="cl-meta">
                {{ $ad->city?->name }}
                @if ($ad->published_at) · {{ __('atrium-classifieds::classifieds.published', ['date' => $ad->published_at->translatedFormat('j F Y')]) }}@endif
                · {{ trans_choice('atrium-classifieds::classifieds.views', $ad->views, ['count' => $ad->views]) }}
            </div>

            @if ($ad->photoUrls() !== [])
                <div class="cl-gallery">
                    @foreach ($ad->photoUrls() as $index => $photo)
                        <a href="{{ $photo }}"><img src="{{ $photo }}" alt="{{ $index === 0 ? $ad->title : '' }}" @if ($index > 0) loading="lazy" @endif></a>
                    @endforeach
                </div>
            @endif

            @if ($properties !== [])
                <dl class="cl-props">
                    @foreach ($properties as $label => $value)
                        <dt>{{ $label }}</dt><dd>{{ $value }}</dd>
                    @endforeach
                </dl>
            @endif

            @if ($ad->text)
                <div class="content">{!! nl2br(e($ad->text)) !!}</div>
            @endif

            @if ($ad->lat() !== null && $ad->lng() !== null)
                @php($d = 0.01)
                <h2>{{ __('atrium-classifieds::classifieds.fields.location') }}</h2>
                @if (! empty($ad->location['address']))<p>{{ $ad->location['address'] }}</p>@endif
                <iframe class="cl-map" loading="lazy" title="{{ __('atrium-classifieds::classifieds.map') }}"
                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $ad->lng() - $d }}%2C{{ $ad->lat() - $d }}%2C{{ $ad->lng() + $d }}%2C{{ $ad->lat() + $d }}&amp;layer=mapnik&amp;marker={{ $ad->lat() }}%2C{{ $ad->lng() }}"></iframe>
            @endif
        </div>

        <aside>
            <div class="cl-box">
                @if ($company !== null)
                    <p><a href="{{ $board->route('company', ['company' => $ad->account_company_id]) }}">{{ $company }}</a><br>
                    <span class="cl-meta">{{ __('atrium-classifieds::classifieds.company_ads') }}</span></p>
                @endif
                @if ($ad->isPublished())
                    <form method="post" action="{{ $board->route('phone', ['ad' => $ad->id]) }}" data-cl-phone>
                        @csrf
                        <button type="submit" class="cl-button">{{ __('atrium-classifieds::classifieds.show_phone') }}</button>
                        <p class="cl-contacts" aria-live="polite"></p>
                    </form>
                @endif
            </div>

            @if ($mine)
                <p><a class="cl-button secondary" href="{{ $board->route('cabinet.edit', ['ad' => $ad->id]) }}">{{ __('atrium-classifieds::classifieds.cabinet.edit') }}</a></p>
            @elseif ($ad->isPublished())
                <div class="cl-box">
                    @if ($signedIn)
                        <form method="post" action="{{ $board->route('favourite', ['ad' => $ad->id]) }}">
                            @csrf
                            <button type="submit" class="cl-button secondary" aria-pressed="{{ $favourite ? 'true' : 'false' }}">
                                {{ $favourite ? __('atrium-classifieds::classifieds.favourite.remove') : __('atrium-classifieds::classifieds.favourite.add') }}
                            </button>
                        </form>

                        <form class="cl-form" method="post" action="{{ $board->route('message', ['ad' => $ad->id]) }}">
                            @csrf
                            <label for="cl-message">{{ __('atrium-classifieds::classifieds.write_seller') }}</label>
                            <textarea id="cl-message" name="body" rows="4" maxlength="2000" required>{{ old('body') }}</textarea>
                            @error('body')<p class="cl-error">{{ $message }}</p>@enderror
                            <p><button type="submit" class="cl-button">{{ __('atrium-classifieds::classifieds.send') }}</button></p>
                        </form>
                    @else
                        <p>{{ __('atrium-classifieds::classifieds.sign_in_to_write') }}</p>
                    @endif
                </div>

                <details class="cl-box" id="complain">
                    <summary>{{ __('atrium-classifieds::classifieds.complaint.title') }}</summary>
                    <form class="cl-form" method="post" action="{{ $board->route('complain', ['ad' => $ad->id]) }}">
                        @csrf
                        <label for="cl-reason">{{ __('atrium-classifieds::classifieds.complaint.reason') }}</label>
                        <select id="cl-reason" name="reason" required>
                            @foreach ($reasons as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <label for="cl-complaint">{{ __('atrium-classifieds::classifieds.complaint.text') }}</label>
                        <textarea id="cl-complaint" name="text" rows="3" maxlength="1000"></textarea>
                        <p><button type="submit" class="cl-button secondary">{{ __('atrium-classifieds::classifieds.complaint.send') }}</button></p>
                    </form>
                </details>
            @endif
        </aside>
    </div>

    <script>
        document.querySelectorAll('[data-cl-phone]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var out = form.querySelector('.cl-contacts');
                fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
                    .then(function (result) {
                        out.textContent = '';
                        if (! result.ok) { out.textContent = result.data.message || ''; return; }
                        [result.data.name, result.data.phone, result.data.email].forEach(function (value, index) {
                            if (! value) { return; }
                            var line = document.createElement(index === 0 ? 'strong' : 'a');
                            line.textContent = value;
                            if (index === 1) { line.href = 'tel:' + value.replace(/[^0-9+]/g, ''); }
                            if (index === 2) { line.href = 'mailto:' + value; }
                            out.appendChild(line);
                            out.appendChild(document.createElement('br'));
                        });
                        form.querySelector('button').hidden = true;
                    });
            });
        });
    </script>
@endsection
