{{--
    A vacancy: what, where and for how much, the texts, the contact person
    and the application form. The JobPosting structured data comes from
    JobsServiceProvider.
--}}
@extends('atrium-theme::layout', [
    'metaTitle' => $page->seoTitle(),
    'metaDescription' => $page->seoDescription(),
    'metaKeywords' => $page->seoKeywords(),
    'ogImage' => $page->ogImage(),
    'ogType' => 'article',
])

@push('head')
    {{ $jsonLd }}
@endpush

@section('content')
    <article class="vacancy">
        <h1>{{ $page->title() }}</h1>

        <dl class="properties">
            @if ($vacancy->department)
                <dt>{{ __('atrium-jobs::jobs.fields.department') }}</dt>
                <dd>{{ $vacancy->department->name }}</dd>
            @endif
            @if ($vacancy->location)
                <dt>{{ __('atrium-jobs::jobs.fields.city') }}</dt>
                <dd>{{ $vacancy->location->name }}</dd>
            @endif
            <dt>{{ __('atrium-jobs::jobs.fields.work_mode') }}</dt>
            <dd>{{ $vacancy->work_mode->label() }}</dd>
            <dt>{{ __('atrium-jobs::jobs.fields.employment_type') }}</dt>
            <dd>{{ $vacancy->employment_type->label() }}</dd>
            @if ($vacancy->experience)
                <dt>{{ __('atrium-jobs::jobs.fields.experience') }}</dt>
                <dd>{{ $vacancy->experience->label() }}</dd>
            @endif
            @if ($salary = $vacancy->salaryText())
                <dt>{{ __('atrium-jobs::jobs.public.salary') }}</dt>
                <dd>{{ $salary }}</dd>
            @endif
            @if ($vacancy->active_to)
                <dt>{{ __('atrium-jobs::jobs.public.until') }}</dt>
                <dd><time datetime="{{ $vacancy->active_to->toAtomString() }}">{{ $vacancy->active_to->locale(app()->getLocale())->isoFormat('LL') }}</time></dd>
            @endif
        </dl>

        @foreach (\Atrium\Jobs\Models\Vacancy::SECTIONS as $section)
            @if ($text = $vacancy->translated($section))
                <section class="content">
                    @if ($section !== 'description')
                        <h2>{{ __('atrium-jobs::jobs.fields.'.$section) }}</h2>
                    @endif
                    {!! $text !!}
                </section>
            @endif
        @endforeach

        @if ($vacancy->contact_name || $vacancy->contact_email || $vacancy->contact_phone)
            <section class="vacancy-contact">
                <h2>{{ __('atrium-jobs::jobs.public.contact') }}</h2>
                <p>
                    @if ($vacancy->contact_name){{ $vacancy->contact_name }}<br>@endif
                    @if ($vacancy->contact_email)<a href="mailto:{{ $vacancy->contact_email }}">{{ $vacancy->contact_email }}</a><br>@endif
                    @if ($vacancy->contact_phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $vacancy->contact_phone) }}">{{ $vacancy->contact_phone }}</a>@endif
                </p>
            </section>
        @endif
    </article>

    <section class="vacancy-apply" id="apply">
        <h2>{{ __('atrium-jobs::jobs.public.apply_title') }}</h2>
        <x-atrium-jobs::apply-form :vacancy="$vacancy" />
    </section>

    @foreach ((array) config('atrium-theme.record_views', []) as $recordView)
        @include($recordView, ['record' => $page->record, 'page' => $page])
    @endforeach
@endsection
