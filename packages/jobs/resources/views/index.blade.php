{{--
    This file is part of Atrium (https://github.com/AlveBy/atrium).
    Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
--}}
{{--
    The vacancies: a filter (department, city, type, remote) over the open
    ones, cards with what a candidate looks for first, and the "send us
    your CV" form when nothing is open. The theme passes the frame's data;
    the filtered `records`, `filters` and `options` come from
    JobsServiceProvider.
--}}
@extends('atrium-theme::layout', ['metaTitle' => $title])

@once
@push('head')
<style>
    .jobs-filter { display: flex; flex-wrap: wrap; gap: .75rem 1rem; align-items: end; margin: 1rem 0 1.5rem; padding: 1rem 1.25rem; background: var(--surface); border-radius: .5rem; }
    .jobs-filter label { display: grid; gap: .25rem; font-size: .9rem; color: var(--muted); }
    .jobs-filter select { font: inherit; color: var(--text); background: var(--bg); padding: .4rem .5rem; border: 1px solid var(--control); border-radius: .375rem; min-width: 11rem; }
    .jobs-filter button { font: inherit; font-weight: 600; padding: .45rem 1rem; border: 0; border-radius: .375rem; background: var(--accent); color: var(--bg); cursor: pointer; }
    .jobs-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 1rem; }
    .jobs-card { padding: 1rem 1.25rem; background: var(--surface); border-radius: .6rem; }
    .jobs-card h2 { font-size: 1.15rem; margin: 0 0 .35rem; }
    .jobs-meta { display: flex; flex-wrap: wrap; gap: .25rem 1rem; margin: 0; padding: 0; list-style: none; color: var(--muted); font-size: .95rem; }
    .jobs-salary { font-weight: 600; color: var(--text); }
    .jobs-card p.jobs-teaser { margin: .5rem 0 0; }
    .jobs-empty { padding: 1.25rem; background: var(--surface); border-radius: .6rem; }
    .jobs-empty h2 { margin-top: 0; }
</style>
@endpush
@endonce

@section('content')
    <h1>{{ $title }}</h1>

    @if ($anyOpen)
        @if (count(array_filter($options)) > 0)
            <form class="jobs-filter" method="get" role="search" aria-label="{{ __('atrium-jobs::jobs.public.filter') }}">
                @foreach ([
                    'department' => __('atrium-jobs::jobs.fields.department'),
                    'location' => __('atrium-jobs::jobs.fields.city'),
                    'type' => __('atrium-jobs::jobs.fields.employment_type'),
                    'mode' => __('atrium-jobs::jobs.fields.work_mode'),
                ] as $name => $label)
                    @continue($options[$name] === [])
                    <label>{{ $label }}
                        <select name="{{ $name }}">
                            <option value="">{{ __('atrium-jobs::jobs.public.any') }}</option>
                            @foreach ($options[$name] as $value => $text)
                                <option value="{{ $value }}" @selected((string) $filters[$name] === (string) $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
                <button type="submit">{{ __('atrium-jobs::jobs.public.show') }}</button>
                @if ($filtered)
                    <a href="{{ url()->current() }}">{{ __('atrium-jobs::jobs.public.reset') }}</a>
                @endif
            </form>
        @endif

        @if ($records->isNotEmpty())
            <p class="jobs-count" role="status">{{ trans_choice('atrium-jobs::jobs.public.count', $records->total(), ['count' => $records->total()]) }}</p>
            <ul class="jobs-list">
                @foreach ($records as $record)
                    @continue(! ($url = $record->url()))
                    @php $vacancy = $record->record; @endphp
                    <li class="jobs-card">
                        <h2><a href="{{ $url }}">{{ $record->title() }}</a></h2>
                        <ul class="jobs-meta">
                            @if ($vacancy->department)
                                <li>{{ $vacancy->department->name }}</li>
                            @endif
                            @if ($vacancy->location)
                                <li>{{ $vacancy->location->name }}</li>
                            @endif
                            <li>{{ $vacancy->work_mode->label() }}</li>
                            <li>{{ $vacancy->employment_type->label() }}</li>
                            @if ($salary = $vacancy->salaryText())
                                <li class="jobs-salary">{{ $salary }}</li>
                            @endif
                        </ul>
                        @if ($teaser = $vacancy->translated('preview_text'))
                            <p class="jobs-teaser">{{ $teaser }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
            @include('atrium-theme::partials.pagination', ['paginator' => $records])
        @else
            <p class="empty" role="status">{{ __('atrium-jobs::jobs.public.no_match') }} <a href="{{ url()->current() }}">{{ __('atrium-jobs::jobs.public.reset') }}</a></p>
        @endif

        <section class="jobs-general" id="apply">
            <h2>{{ __('atrium-jobs::jobs.public.general_title') }}</h2>
            <p>{{ __('atrium-jobs::jobs.public.general_text') }}</p>
            <x-atrium-jobs::apply-form />
        </section>
    @else
        <section class="jobs-empty" id="apply">
            <h2>{{ __('atrium-jobs::jobs.public.none_title') }}</h2>
            <p>{{ __('atrium-jobs::jobs.public.none_text') }}</p>
            <x-atrium-jobs::apply-form />
        </section>
    @endif
@endsection
