<?php

declare(strict_types=1);

namespace Atrium\Jobs\Tests\Feature;

use Atrium\Admin\Models\Language;
use Atrium\Jobs\Tests\TestCase;

/**
 * The careers pages: the list with its filter, a vacancy with the form and
 * its JobPosting, the "no open vacancies" state.
 */
final class PublicPageTest extends TestCase
{
    public function test_without_open_vacancies_the_page_says_so_and_offers_the_cv_form(): void
    {
        $this->vacancy('Old job', 'old-job', ['active_to' => now()->subDay()]);

        $this->get('/jobs')
            ->assertOk()
            ->assertSee('No open vacancies right now')
            ->assertSee('Send CV')
            ->assertDontSee('Old job')
            ->assertDontSee('<form class="jobs-filter"', false);
    }

    public function test_the_list_shows_open_vacancies_with_what_candidates_look_for(): void
    {
        $engineering = $this->department('Engineering');
        $berlin = $this->location('Berlin', ['country' => 'de']);

        $this->vacancy('Backend developer', 'backend-developer', [
            'department_id' => $engineering->id,
            'location_id' => $berlin->id,
            'work_mode' => 'hybrid',
            'salary_from' => 4500,
            'salary_to' => 6000,
            'salary_currency' => 'eur',
            'preview_text' => ['en' => 'Build our services.'],
        ]);
        $this->vacancy('Sales manager', 'sales-manager', ['salary_negotiable' => true]);
        $this->vacancy('Hidden', 'hidden', ['active' => false]);

        $this->get('/jobs')
            ->assertOk()
            ->assertSee('2 open vacancies')
            ->assertSee('Backend developer')
            ->assertSee('Engineering')
            ->assertSee('Berlin')
            ->assertSee('Hybrid')
            ->assertSee('4,500–6,000 EUR per month')
            ->assertSee('Build our services.')
            ->assertSee('By agreement')
            ->assertDontSee('Hidden')
            ->assertSee('Did not find a suitable vacancy?');
    }

    public function test_the_filter_narrows_the_list_and_offers_only_values_in_use(): void
    {
        $engineering = $this->department('Engineering');
        $sales = $this->department('Sales');
        $this->department('Unused department');
        $berlin = $this->location('Berlin');
        $warsaw = $this->location('Warsaw');

        $this->vacancy('Backend developer', 'backend', ['department_id' => $engineering->id, 'location_id' => $berlin->id, 'work_mode' => 'remote']);
        $this->vacancy('Frontend developer', 'frontend', ['department_id' => $engineering->id, 'location_id' => $warsaw->id, 'employment_type' => 'part_time']);
        $this->vacancy('Sales manager', 'sales', ['department_id' => $sales->id, 'location_id' => $berlin->id]);

        $this->get('/jobs')
            ->assertSee('Engineering')
            ->assertDontSee('Unused department')
            ->assertDontSee('Internship');

        $this->get('/jobs?department='.$engineering->id)
            ->assertSee('Backend developer')->assertSee('Frontend developer')->assertDontSee('Sales manager</a>', false);

        $this->get('/jobs?location='.$berlin->id.'&mode=remote')
            ->assertSee('Backend developer')->assertDontSee('Frontend developer</a>', false)->assertDontSee('Sales manager</a>', false);

        $this->get('/jobs?type=part_time')
            ->assertSee('Frontend developer')->assertDontSee('Backend developer</a>', false);

        $this->get('/jobs?department='.$sales->id.'&mode=remote')
            ->assertOk()->assertSee('No vacancies match this filter.');

        // Unknown values are ignored, not an error.
        $this->get('/jobs?type=forever&department=abc&mode[]=x')->assertOk()->assertSee('3 open vacancies');
    }

    public function test_a_vacancy_page_has_the_texts_the_form_and_the_job_posting(): void
    {
        $berlin = $this->location('Berlin', ['street' => 'Beispielstraße 12', 'postal_code' => '10115', 'country' => 'DE']);

        $this->vacancy('Backend developer', 'backend-developer', [
            'location_id' => $berlin->id,
            'work_mode' => 'hybrid',
            'employment_type' => 'full_time',
            'experience' => '3-6',
            'salary_from' => 4500,
            'salary_to' => 6000,
            'salary_currency' => 'EUR',
            'active_from' => '2026-11-01 00:00:00',
            'active_to' => now()->addMonth(),
            'description' => ['en' => '<p>About us.</p>'],
            'duties' => ['en' => '<ul><li>Write code</li></ul>'],
            'requirements' => ['en' => '<ul><li>PHP</li></ul>'],
            'contact_name' => 'Alex Example',
            'contact_email' => 'jobs@example.com',
        ]);

        $response = $this->get('/jobs/backend-developer')
            ->assertOk()
            ->assertSee('<h1>Backend developer</h1>', false)
            ->assertSee('Responsibilities')
            ->assertSee('<li>Write code</li>', false)
            ->assertSee('Alex Example')
            ->assertSee('3–6 years')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('accept=".pdf,.doc,.docx"', false)
            ->assertSee('PDF, DOC, DOCX, up to 5 MB.')
            ->assertSee('It is deleted 180 days after the last change.')
            ->assertSee('Apply');

        $posting = $this->jsonLd((string) $response->getContent());
        $this->assertSame('JobPosting', $posting['@type']);
        $this->assertSame('Backend developer', $posting['title']);
        $this->assertSame('2026-11-01', $posting['datePosted']);
        $this->assertSame('FULL_TIME', $posting['employmentType']);
        $this->assertSame('Example Labs', $posting['hiringOrganization']['name']);
        $this->assertSame('Berlin', $posting['jobLocation']['address']['addressLocality']);
        $this->assertSame('DE', $posting['jobLocation']['address']['addressCountry']);
        $this->assertSame('TELECOMMUTE', $posting['jobLocationType']);
        $this->assertEquals(['@type' => 'QuantitativeValue', 'unitText' => 'MONTH', 'minValue' => 4500, 'maxValue' => 6000], $posting['baseSalary']['value']);
        $this->assertSame(36, $posting['experienceRequirements']['monthsOfExperience']);
        $this->assertStringContainsString('<h2>Responsibilities</h2>', $posting['description']);
        $this->assertStringEndsWith('/jobs/backend-developer', $posting['url']);
        $this->assertArrayHasKey('validThrough', $posting);
    }

    public function test_a_remote_job_has_no_office_address_and_texts_cannot_break_the_script(): void
    {
        $this->vacancy('Remote </script><script>alert(1)</script>', 'remote', [
            'work_mode' => 'remote',
            'location_id' => $this->location('Warsaw', ['country' => 'PL'])->id,
        ]);

        $content = (string) $this->get('/jobs/remote')->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(1)', $content);

        $posting = $this->jsonLd($content);
        $this->assertArrayNotHasKey('jobLocation', $posting);
        $this->assertSame('TELECOMMUTE', $posting['jobLocationType']);
        $this->assertSame(['@type' => 'Country', 'name' => 'PL'], $posting['applicantLocationRequirements']);
    }

    public function test_a_closed_vacancy_has_no_page(): void
    {
        $this->vacancy('Old job', 'old-job', ['active_to' => now()->subDay()]);

        $this->get('/jobs/old-job')->assertNotFound();
    }

    public function test_each_language_shows_its_own_texts(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);

        $this->vacancy('Backend developer', 'backend-developer', [
            'name' => ['en' => 'Backend developer', 'de' => 'Backend-Entwickler'],
            'code' => ['en' => 'backend-developer', 'de' => 'backend-entwickler'],
            'duties' => ['en' => '<p>Write code</p>', 'de' => '<p>Code schreiben</p>'],
        ]);

        $this->get('/de/jobs')->assertOk()->assertSee('Backend-Entwickler');
        $this->get('/de/jobs/backend-entwickler')->assertOk()->assertSee('Code schreiben')->assertDontSee('Write code')
            ->assertSee('action="'.url('/de/jobs/apply').'"', false);
    }

    public function test_after_sending_the_form_says_thank_you(): void
    {
        $vacancy = $this->vacancy();

        $this->withSession(['atrium-jobs.sent' => $vacancy->id])
            ->get('/jobs/backend-developer')
            ->assertSee('Thank you! Your application has been sent.');

        // The general form on the list does not claim it.
        $this->withSession(['atrium-jobs.sent' => $vacancy->id])
            ->get('/jobs')
            ->assertDontSee('Thank you! Your application has been sent.');
    }

    public function test_errors_and_old_input_come_back_to_the_form(): void
    {
        $vacancy = $this->vacancy();

        $this->from('/jobs/backend-developer')
            ->followingRedirects()
            ->post('/jobs/apply', $this->application($vacancy, ['email' => 'not-an-email', 'name' => 'Jamie <b>']))
            ->assertOk()
            ->assertSee('aria-invalid="true"', false)
            ->assertSee('value="Jamie &lt;b&gt;"', false);
    }
}
