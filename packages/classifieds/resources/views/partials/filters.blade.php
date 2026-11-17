{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    The filters of a list: words, place, price, date, and the properties of
    the category with how many ads each option has. A plain GET form, so
    every filtered list is an address that can be shared.
--}}
@php($action = strtok((string) $canonical, '?'))
<form class="cl-filters" method="get" action="{{ $action }}" role="search">
    <fieldset>
        <legend>{{ __('atrium-classifieds::classifieds.filters.search') }}</legend>
        <label>
            <span class="skip">{{ __('atrium-classifieds::classifieds.filters.words') }}</span>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('atrium-classifieds::classifieds.filters.words') }}">
        </label>
    </fieldset>

    @if ($city === null)
        <fieldset>
            <legend>{{ __('atrium-classifieds::classifieds.filters.place') }}</legend>
            <label>{{ __('atrium-classifieds::classifieds.fields.region') }}
                <select name="region">
                    <option value="">{{ __('atrium-classifieds::classifieds.filters.any') }}</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}" @selected(($filters['region'] ?? null) === $region->id)>{{ $region->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>{{ __('atrium-classifieds::classifieds.fields.city') }}
                <select name="city">
                    <option value="">{{ __('atrium-classifieds::classifieds.filters.any') }}</option>
                    @foreach ($cities as $option)
                        <option value="{{ $option->id }}" @selected(($filters['city'] ?? null) === $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </label>
        </fieldset>
    @endif

    <fieldset>
        <legend>{{ __('atrium-classifieds::classifieds.fields.price') }}</legend>
        <div class="cl-range">
            <label><span class="skip">{{ __('atrium-classifieds::classifieds.filters.from') }}</span>
                <input type="number" min="0" step="any" name="price[min]" value="{{ $filters['price']['min'] ?? '' }}" placeholder="{{ __('atrium-classifieds::classifieds.filters.from') }}"></label>
            <label><span class="skip">{{ __('atrium-classifieds::classifieds.filters.to') }}</span>
                <input type="number" min="0" step="any" name="price[max]" value="{{ $filters['price']['max'] ?? '' }}" placeholder="{{ __('atrium-classifieds::classifieds.filters.to') }}"></label>
        </div>
    </fieldset>

    @foreach ($fields as $field)
        @continue(! $field['filterable'])
        @php($code = $field['code'])
        @php($value = $filters['props'][$code] ?? null)
        <fieldset>
            <legend>{{ $field['label'] }}</legend>
            @if (in_array($field['kind'], ['select', 'multiselect'], true))
                @foreach ($field['options'] as $key => $label)
                    <label>
                        <input type="checkbox" name="props[{{ $code }}][]" value="{{ $key }}" @checked(in_array((string) $key, array_map('strval', (array) $value), true))>
                        {{ $label }}@isset($counts[$code][$key]) <span class="cl-meta">({{ $counts[$code][$key] }})</span>@endisset
                    </label>
                @endforeach
            @elseif ($field['kind'] === 'boolean')
                <label><input type="checkbox" name="props[{{ $code }}]" value="1" @checked((string) $value === '1')> {{ __('atrium-classifieds::classifieds.yes') }}@isset($counts[$code][1]) <span class="cl-meta">({{ $counts[$code][1] }})</span>@endisset</label>
            @elseif (in_array($field['kind'], ['number', 'date'], true))
                <div class="cl-range">
                    <label><span class="skip">{{ __('atrium-classifieds::classifieds.filters.from') }}</span>
                        <input type="{{ $field['kind'] }}" step="any" name="props[{{ $code }}][min]" value="{{ $value['min'] ?? '' }}" placeholder="{{ __('atrium-classifieds::classifieds.filters.from') }}"></label>
                    <label><span class="skip">{{ __('atrium-classifieds::classifieds.filters.to') }}</span>
                        <input type="{{ $field['kind'] }}" step="any" name="props[{{ $code }}][max]" value="{{ $value['max'] ?? '' }}" placeholder="{{ __('atrium-classifieds::classifieds.filters.to') }}"></label>
                </div>
            @else
                <label><span class="skip">{{ $field['label'] }}</span><input type="text" name="props[{{ $code }}]" value="{{ is_string($value) ? $value : '' }}"></label>
            @endif
        </fieldset>
    @endforeach

    <fieldset>
        <legend>{{ __('atrium-classifieds::classifieds.filters.period') }}</legend>
        <select name="period" aria-label="{{ __('atrium-classifieds::classifieds.filters.period') }}">
            <option value="">{{ __('atrium-classifieds::classifieds.filters.any_time') }}</option>
            @foreach (array_keys(\Atrium\Classifieds\Support\AdSearch::PERIODS) as $period)
                <option value="{{ $period }}" @selected(($filters['period'] ?? null) === $period)>{{ __('atrium-classifieds::classifieds.filters.periods.'.$period) }}</option>
            @endforeach
        </select>
    </fieldset>

    <fieldset>
        <legend>{{ __('atrium-classifieds::classifieds.filters.sort') }}</legend>
        <select name="sort" aria-label="{{ __('atrium-classifieds::classifieds.filters.sort') }}">
            @foreach (\Atrium\Classifieds\Support\AdSearch::SORTS as $sort)
                <option value="{{ $sort }}" @selected(($filters['sort'] ?? 'new') === $sort)>{{ __('atrium-classifieds::classifieds.filters.sorts.'.$sort) }}</option>
            @endforeach
        </select>
    </fieldset>

    <button type="submit" class="cl-button">{{ __('atrium-classifieds::classifieds.filters.apply') }}</button>
    <a href="{{ $action }}">{{ __('atrium-classifieds::classifieds.filters.reset') }}</a>
</form>

@if ($signedIn)
    <form class="cl-filters" method="post" action="{{ app(\Atrium\Classifieds\Board::class)->route('searches.store') }}" style="margin-top:1rem">
        @csrf
        @foreach (\Illuminate\Support\Arr::dot($filters) as $key => $value)
            @php($parts = explode('.', (string) $key))
            <input type="hidden" name="filters[{{ implode('][', $parts) }}]" value="{{ $value }}">
        @endforeach
        <fieldset>
            <legend>{{ __('atrium-classifieds::classifieds.searches.save') }}</legend>
            <label>{{ __('atrium-classifieds::classifieds.searches.name') }}
                <input type="text" name="name" required maxlength="191" value="{{ $heading }}"></label>
            <label><input type="hidden" name="notify" value="0"><input type="checkbox" name="notify" value="1" checked> {{ __('atrium-classifieds::classifieds.searches.notify') }}</label>
            <button type="submit" class="cl-button secondary">{{ __('atrium-classifieds::classifieds.searches.save') }}</button>
        </fieldset>
    </form>
@endif
