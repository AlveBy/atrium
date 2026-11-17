{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    The application form. Colours come from the theme's custom properties
    (text on --bg, the button --bg on --accent: 6.7:1 light, 8.87:1 dark);
    errors are #b91c1c in light (6.47:1 on white) and #fca5a5 in dark
    (9.71:1 on #121418), and are always written out, never colour alone.
--}}
@once
<style>
    .jobs-form { --jobs-error: #b91c1c; display: grid; gap: 1rem; max-width: 40rem; }
    :root[data-theme="dark"] .jobs-form { --jobs-error: #fca5a5; }
    @media (prefers-color-scheme: dark) { :root:not([data-theme="light"]) .jobs-form { --jobs-error: #fca5a5; } }
    .jobs-form__field { display: grid; gap: .35rem; }
    .jobs-form__field input:not([type=checkbox]):not([type=radio]), .jobs-form__field select, .jobs-form__field textarea { font: inherit; padding: .5rem .6rem; border: 1px solid var(--control, #6b7280); border-radius: .375rem; background: var(--bg, #fff); color: var(--text, inherit); }
    .jobs-form__field input[type=file] { padding: .4rem; }
    .jobs-form__hint { color: var(--muted, #4f5662); font-size: .9rem; margin: 0; }
    .jobs-form__choice { display: flex; gap: .5rem; align-items: flex-start; }
    .jobs-form__choice input { margin-top: .35rem; }
    .jobs-form__error { color: var(--jobs-error); margin: 0; }
    .jobs-form__sent { margin: 0; padding: .75rem 1rem; border-left: 4px solid var(--accent, #1d4ed8); background: var(--surface, #f3f4f6); font-weight: bold; }
    .jobs-form button { justify-self: start; font: inherit; font-weight: 600; padding: .6rem 1.2rem; border: 0; border-radius: .375rem; background: var(--accent, #1d4ed8); color: var(--bg, #fff); cursor: pointer; }
    .jobs-form__trap { position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden; }
    .jobs-form fieldset { border: 0; padding: 0; margin: 0; }
</style>
@endonce
<form id="{{ $id }}" method="post" action="{{ $action }}" enctype="multipart/form-data" {{ $attributes->merge(['class' => 'jobs-form']) }}>
    @csrf
    <input type="hidden" name="_apply" value="{{ $key }}">
    @if ($vacancy)
        <input type="hidden" name="vacancy_id" value="{{ $vacancy->id }}">
    @endif
    <input type="hidden" name="{{ $timeField }}" value="{{ $token }}">

    <div class="jobs-form__trap" aria-hidden="true">
        <label>{{ __('atrium-jobs::jobs.public.honeypot') }}
            <input type="text" name="{{ $honeypot }}" value="" tabindex="-1" autocomplete="off">
        </label>
    </div>

    @if ($sent)
        <p class="jobs-form__sent" role="status">{{ __('atrium-jobs::jobs.public.sent') }}</p>
    @endif

    @if ($errors->has('form'))
        <p class="jobs-form__error" role="alert">{{ $errors->first('form') }}</p>
    @endif

    @foreach ([
        ['name', 'text', 'name', true],
        ['email', 'email', 'email', true],
        ['phone', 'tel', 'tel', false],
    ] as [$name, $type, $autocomplete, $required])
        @php $invalid = $errors->has($name); @endphp
        <div class="jobs-form__field">
            <label for="{{ $id }}-{{ $name }}">{{ __('atrium-jobs::jobs.fields.'.$name) }}</label>
            <input type="{{ $type }}" id="{{ $id }}-{{ $name }}" name="{{ $name }}" value="{{ $old($name) }}" autocomplete="{{ $autocomplete }}" maxlength="{{ $name === 'phone' ? 50 : 255 }}" @required($required) @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-{{ $name }}-error" @endif>
            @if ($invalid)
                <p class="jobs-form__error" id="{{ $id }}-{{ $name }}-error">{{ $errors->first($name) }}</p>
            @endif
        </div>
    @endforeach

    <div class="jobs-form__field">
        <label for="{{ $id }}-message">{{ __('atrium-jobs::jobs.public.cover_letter') }}</label>
        <textarea id="{{ $id }}-message" name="message" rows="6" maxlength="10000" @if ($errors->has('message')) aria-invalid="true" aria-describedby="{{ $id }}-message-error" @endif>{{ $old('message') }}</textarea>
        @if ($errors->has('message'))
            <p class="jobs-form__error" id="{{ $id }}-message-error">{{ $errors->first('message') }}</p>
        @endif
    </div>

    <div class="jobs-form__field">
        <label for="{{ $id }}-cv">{{ __('atrium-jobs::jobs.fields.cv') }}</label>
        <input type="file" id="{{ $id }}-cv" name="cv" accept="{{ $accept }}" @required($cvRequired) aria-describedby="{{ $id }}-cv-hint{{ $errors->has('cv') ? ' '.$id.'-cv-error' : '' }}" @if ($errors->has('cv')) aria-invalid="true" @endif>
        <p class="jobs-form__hint" id="{{ $id }}-cv-hint">{{ __('atrium-jobs::jobs.public.cv_hint', ['types' => $types, 'size' => $maxSize]) }}</p>
        @if ($errors->has('cv'))
            <p class="jobs-form__error" id="{{ $id }}-cv-error">{{ $errors->first('cv') }}</p>
        @endif
    </div>

    {{-- The extra questions of the forms module's form, when there is one. --}}
    @foreach ($questions as $field)
        @php
            $fid = $id.'-q-'.$field->name;
            $name = 'fields['.$field->name.']';
            $errorKey = 'fields.'.$field->name;
            $value = $old('fields', [])[$field->name] ?? '';
            $invalid = $errors->has($errorKey);
        @endphp
        <div class="jobs-form__field">
            @if ($field->type === 'checkbox')
                <label class="jobs-form__choice">
                    <input type="checkbox" id="{{ $fid }}" name="{{ $name }}" value="1" @checked($value) @required($field->required) @if ($invalid) aria-invalid="true" aria-describedby="{{ $fid }}-error" @endif>
                    <span>{{ $field->label }}</span>
                </label>
            @elseif ($field->type === 'radio')
                <fieldset @if ($invalid) aria-describedby="{{ $fid }}-error" @endif>
                    <legend>{{ $field->label }}</legend>
                    @foreach ($field->optionList() as $index => $option)
                        <label class="jobs-form__choice">
                            <input type="radio" name="{{ $name }}" value="{{ $option }}" @checked($value === $option) @required($field->required && $index === 0)>
                            <span>{{ $option }}</span>
                        </label>
                    @endforeach
                </fieldset>
            @else
                <label for="{{ $fid }}">{{ $field->label }}</label>
                @if ($field->type === 'textarea')
                    <textarea id="{{ $fid }}" name="{{ $name }}" rows="4" @required($field->required) @if ($invalid) aria-invalid="true" aria-describedby="{{ $fid }}-error" @endif>{{ $value }}</textarea>
                @elseif ($field->type === 'select')
                    <select id="{{ $fid }}" name="{{ $name }}" @required($field->required) @if ($invalid) aria-invalid="true" aria-describedby="{{ $fid }}-error" @endif>
                        <option value="">{{ __('atrium-jobs::jobs.public.choose') }}</option>
                        @foreach ($field->optionList() as $option)
                            <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="{{ $field->type }}" id="{{ $fid }}" name="{{ $name }}" value="{{ $value }}" @required($field->required) @if ($invalid) aria-invalid="true" aria-describedby="{{ $fid }}-error" @endif>
                @endif
            @endif
            @if ($invalid)
                <p class="jobs-form__error" id="{{ $fid }}-error">{{ $errors->first($errorKey) }}</p>
            @endif
        </div>
    @endforeach

    <div class="jobs-form__field">
        <label class="jobs-form__choice">
            <input type="checkbox" id="{{ $id }}-consent" name="consent" value="1" @checked($old('consent')) required @if ($errors->has('consent')) aria-invalid="true" aria-describedby="{{ $id }}-consent-error" @endif>
            <span>
                @php $days = (int) config('atrium-jobs.retention_days', 180); @endphp
                {{ $days > 0 ? __('atrium-jobs::jobs.public.consent', ['days' => $days]) : __('atrium-jobs::jobs.public.consent_kept') }}
                @if ($privacyUrl)
                    <a href="{{ $privacyUrl }}" target="_blank" rel="noopener">{{ __('atrium-jobs::jobs.public.privacy') }}</a>
                @endif
            </span>
        </label>
        @if ($errors->has('consent'))
            <p class="jobs-form__error" id="{{ $id }}-consent-error">{{ $errors->first('consent') }}</p>
        @endif
    </div>

    <button type="submit">{{ $vacancy ? __('atrium-jobs::jobs.public.apply') : __('atrium-jobs::jobs.public.send_cv') }}</button>
</form>
