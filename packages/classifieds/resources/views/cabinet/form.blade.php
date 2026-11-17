{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    Posting and editing. The properties of the category come from custom
    fields: change the category and press "Update fields" to get its own.
--}}
@extends($cabinet ? 'atrium-account::cabinet' : 'atrium-classifieds::cabinet.frame')

@include('atrium-classifieds::partials.head')

@section('cabinet')
    @php($board = app(\Atrium\Classifieds\Board::class))
    @php($f = fn (string $key): string => __('atrium-classifieds::classifieds.fields.'.$key))
    @include('atrium-classifieds::cabinet.status')

    <form class="cl-form" method="post" enctype="multipart/form-data"
          action="{{ $ad->exists ? $board->route('cabinet.update', ['ad' => $ad->id]) : $board->route('cabinet.store') }}">
        @csrf
        @if ($ad->exists) @method('PUT') @endif

        <label for="cl-title">{{ $f('title') }}</label>
        <input id="cl-title" type="text" name="title" maxlength="150" required value="{{ old('title', $ad->title) }}">

        <label for="cl-category">{{ $f('category') }}</label>
        <select id="cl-category" name="category_id" required>
            <option value="">—</option>
            @foreach ($categories as $id => $name)
                <option value="{{ $id }}" @selected((int) old('category_id', $ad->category_id) === $id)>{{ $name }}</option>
            @endforeach
        </select>
        @if (! $ad->exists)
            <button type="submit" class="cl-button secondary" formmethod="get" formaction="{{ $board->route('cabinet.create') }}" formnovalidate>{{ __('atrium-classifieds::classifieds.cabinet.refresh_fields') }}</button>
        @endif

        @foreach ($fields as $field)
            @php($name = 'props['.$field['code'].']')
            @php($value = old('props.'.$field['code'], $values[$field['code']] ?? null))
            <label for="cl-prop-{{ $field['code'] }}">{{ $field['label'] }}@if ($field['required']) *@endif
                @if ($field['hint'])<span class="hint">{{ $field['hint'] }}</span>@endif</label>
            @switch($field['kind'])
                @case('select')
                    <select id="cl-prop-{{ $field['code'] }}" name="{{ $name }}" @required($field['required'])>
                        <option value="">—</option>
                        @foreach ($field['options'] as $key => $label)
                            <option value="{{ $key }}" @selected((string) $value === (string) $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @break
                @case('multiselect')
                    <div id="cl-prop-{{ $field['code'] }}">
                        @foreach ($field['options'] as $key => $label)
                            <label style="font-weight:400;margin:.2rem 0"><input type="checkbox" name="{{ $name }}[]" value="{{ $key }}" @checked(in_array((string) $key, array_map('strval', (array) $value), true))> {{ $label }}</label>
                        @endforeach
                    </div>
                    @break
                @case('boolean')
                    <input type="hidden" name="{{ $name }}" value="0">
                    <input id="cl-prop-{{ $field['code'] }}" type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value)>
                    @break
                @case('text')
                    <textarea id="cl-prop-{{ $field['code'] }}" name="{{ $name }}" rows="3" @required($field['required'])>{{ $value }}</textarea>
                    @break
                @default
                    <input id="cl-prop-{{ $field['code'] }}" type="{{ in_array($field['kind'], ['number', 'date'], true) ? $field['kind'] : 'text' }}" @if ($field['kind'] === 'number') step="any" @endif
                        name="{{ $name }}" value="{{ is_scalar($value) ? $value : '' }}" @required($field['required'])>
            @endswitch
        @endforeach

        <label for="cl-text">{{ $f('text') }}</label>
        <textarea id="cl-text" name="text" rows="8" maxlength="5000">{{ old('text', $ad->text) }}</textarea>

        <label for="cl-price">{{ $f('price') }} <span class="hint">{{ $board->currency() }}</span></label>
        <input id="cl-price" type="number" name="price" min="0" step="0.01" value="{{ old('price', $ad->price) }}">
        <label style="font-weight:400"><input type="hidden" name="negotiable" value="0"><input type="checkbox" name="negotiable" value="1" @checked(old('negotiable', $ad->negotiable))> {{ $f('negotiable') }}</label>

        <label for="cl-city">{{ $f('city') }}</label>
        <select id="cl-city" name="city_id">
            <option value="">—</option>
            @foreach ($cities as $city)
                <option value="{{ $city->id }}" @selected((int) old('city_id', $ad->city_id) === $city->id)>{{ $city->name }}</option>
            @endforeach
        </select>

        <fieldset style="border:0;padding:0;margin:0">
            <legend><label style="margin-bottom:0">{{ $f('location') }} <span class="hint">{{ __('atrium-classifieds::classifieds.hints.location') }}</span></label></legend>
            <div class="cl-range">
                <input type="number" step="any" name="lat" aria-label="{{ $f('lat') }}" placeholder="{{ $f('lat') }}" value="{{ old('lat', $ad->lat()) }}">
                <input type="number" step="any" name="lng" aria-label="{{ $f('lng') }}" placeholder="{{ $f('lng') }}" value="{{ old('lng', $ad->lng()) }}">
            </div>
            <input type="text" name="address" aria-label="{{ $f('address') }}" placeholder="{{ $f('address') }}" value="{{ old('address', $ad->location['address'] ?? '') }}" style="margin-top:.5rem">
        </fieldset>

        <label for="cl-photos">{{ $f('photos') }} <span class="hint">{{ __('atrium-classifieds::classifieds.hints.photos', ['count' => $maxPhotos]) }}</span></label>
        @foreach ($ad->photos ?? [] as $path)
            <label style="font-weight:400;display:inline-block;margin-right:1rem"><input type="checkbox" name="keep[]" value="{{ $path }}" checked>
                <img src="{{ \Atrium\Admin\Support\MediaUrl::to($path) }}" alt="" width="96" height="72" style="object-fit:cover;vertical-align:middle"></label>
        @endforeach
        <input id="cl-photos" type="file" name="photos[]" accept="image/*" multiple>

        <h2>{{ __('atrium-classifieds::classifieds.tabs.contacts') }}</h2>
        <p class="hint">{{ __('atrium-classifieds::classifieds.hints.contacts') }}</p>
        <label for="cl-cname">{{ $f('contact_name') }}</label>
        <input id="cl-cname" type="text" name="contact_name" maxlength="100" value="{{ old('contact_name', $ad->contact_name) }}">
        <label for="cl-phone">{{ $f('contact_phone') }}</label>
        <input id="cl-phone" type="tel" name="contact_phone" maxlength="40" value="{{ old('contact_phone', $ad->contact_phone) }}">
        <label for="cl-email">{{ $f('contact_email') }}</label>
        <input id="cl-email" type="email" name="contact_email" maxlength="191" value="{{ old('contact_email', $ad->contact_email) }}">

        <p class="cl-actions" style="margin-top:1.5rem">
            <button type="submit" class="cl-button" name="submit" value="1">{{ $premoderation ? __('atrium-classifieds::classifieds.cabinet.save_submit') : __('atrium-classifieds::classifieds.cabinet.save_publish') }}</button>
            <button type="submit" class="cl-button secondary" name="submit" value="0">{{ __('atrium-classifieds::classifieds.cabinet.save_draft') }}</button>
        </p>
    </form>
@endsection
