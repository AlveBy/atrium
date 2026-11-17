<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Support;

use Atrium\Admin\Support\Sites;
use Atrium\Jobs\Enums\Experience;
use Atrium\Jobs\Enums\WorkMode;
use Atrium\Jobs\Models\Vacancy;
use Illuminate\Support\HtmlString;

/**
 * The schema.org JobPosting of a vacancy: what Google for Jobs and other
 * job search engines read to list it.
 *
 * https://developers.google.com/search/docs/appearance/structured-data/job-posting
 */
final class JobPosting
{
    /**
     * @return array<string,mixed>
     */
    public function schema(Vacancy $vacancy, ?string $url = null): array
    {
        $site = app(Sites::class)->current();
        $organization = (string) ($site->name ?: config('app.name'));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $vacancy->translated('name'),
            'description' => $this->description($vacancy),
            'datePosted' => ($vacancy->active_from ?? $vacancy->created_at ?? now())->toDateString(),
            'employmentType' => $vacancy->employment_type->schema(),
            'hiringOrganization' => array_filter([
                '@type' => 'Organization',
                'name' => $organization,
                'sameAs' => rtrim(url('/'), '/'),
            ]),
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => $organization,
                'value' => (string) $vacancy->id,
            ],
            'directApply' => true,
        ];

        if ($url !== null) {
            $schema['url'] = $url;
        }

        if ($vacancy->active_to !== null) {
            $schema['validThrough'] = $vacancy->active_to->toAtomString();
        }

        $location = $vacancy->location;

        if ($location !== null && $vacancy->work_mode !== WorkMode::Remote) {
            $schema['jobLocation'] = [
                '@type' => 'Place',
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $location->street,
                    'addressLocality' => (string) $location->name,
                    'addressRegion' => $location->region,
                    'postalCode' => $location->postal_code,
                    'addressCountry' => $location->country,
                ]),
            ];
        }

        // Remote, or partly remote: Google wants both the office and this.
        if ($vacancy->work_mode !== WorkMode::Office) {
            $schema['jobLocationType'] = 'TELECOMMUTE';

            if ($location?->country !== null) {
                $schema['applicantLocationRequirements'] = ['@type' => 'Country', 'name' => $location->country];
            }
        }

        if (($salary = $this->salary($vacancy)) !== null) {
            $schema['baseSalary'] = $salary;
        }

        if ($vacancy->experience !== null) {
            $schema['experienceRequirements'] = $vacancy->experience === Experience::None
                ? 'no requirements'
                : ['@type' => 'OccupationalExperienceRequirements', 'monthsOfExperience' => $vacancy->experience->months()];
        }

        return $schema;
    }

    /**
     * The script tag. The JSON escapes "<", so a text cannot close it.
     */
    public function jsonLd(Vacancy $vacancy, ?string $url = null): HtmlString
    {
        $json = json_encode($this->schema($vacancy, $url), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);

        return new HtmlString('<script type="application/ld+json">'.$json.'</script>');
    }

    /**
     * Every text of the vacancy, as HTML under its heading.
     */
    private function description(Vacancy $vacancy): string
    {
        $parts = [];

        foreach (Vacancy::SECTIONS as $section) {
            $text = $vacancy->translated($section);

            if ($text === '') {
                continue;
            }

            $parts[] = ($section === 'description' ? '' : '<h2>'.e(__('atrium-jobs::jobs.fields.'.$section)).'</h2>').$text;
        }

        return $parts === [] ? e($vacancy->translated('preview_text') ?: $vacancy->translated('name')) : implode("\n", $parts);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function salary(Vacancy $vacancy): ?array
    {
        if ($vacancy->salary_currency === null || ($vacancy->salary_from === null && $vacancy->salary_to === null)) {
            return null;
        }

        $from = $vacancy->salary_from !== null ? (float) $vacancy->salary_from : null;
        $to = $vacancy->salary_to !== null ? (float) $vacancy->salary_to : null;

        $value = ['@type' => 'QuantitativeValue', 'unitText' => $vacancy->salary_period->schema()];

        if ($from !== null && $to !== null && $from !== $to) {
            $value += ['minValue' => $from, 'maxValue' => $to];
        } else {
            $value['value'] = $from ?? $to;
        }

        return ['@type' => 'MonetaryAmount', 'currency' => $vacancy->salary_currency, 'value' => $value];
    }
}
