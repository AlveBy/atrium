<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Tests\Feature;

use Atrium\Jobs\Mail\ReplyMail;
use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Models\ReplyTemplate;
use Atrium\Jobs\Models\Vacancy;
use Atrium\Jobs\Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

/**
 * HR's side: vacancies, departments and offices like any module, the
 * applications as a pipeline with notes and replies, the CV behind the
 * right to see applications.
 */
final class AdminTest extends TestCase
{
    public function test_the_installed_module_brings_its_lists(): void
    {
        $this->actingAs($this->admin(), 'admin');

        foreach ([
            'vacancies' => 'Vacancies',
            'job_applications' => 'Job applications',
            'job_departments' => 'Departments',
            'job_locations' => 'Offices and cities',
            'job_reply_templates' => 'Reply templates',
        ] as $prefix => $title) {
            $this->get("/admin/{$prefix}")->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('title', $title));
        }
    }

    public function test_a_vacancy_is_created_with_every_field(): void
    {
        $department = $this->department('Engineering');
        $location = $this->location('Berlin');

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/vacancies', [
                'name' => ['en' => 'Backend developer'],
                'code' => ['en' => 'backend-developer'],
                'active' => true,
                'active_to' => '2027-01-31 18:00',
                'department_id' => $department->id,
                'location_id' => $location->id,
                'work_mode' => 'hybrid',
                'employment_type' => 'contract',
                'experience' => '6+',
                'salary_from' => 5000,
                'salary_to' => 7000,
                'salary_currency' => 'EUR',
                'salary_period' => 'month',
                'salary_negotiable' => false,
                'preview_text' => ['en' => 'Short.'],
                'duties' => ['en' => '<p>Code</p>'],
                'contact_name' => 'Alex Example',
                'contact_email' => 'jobs@example.com',
                'contact_phone' => '+49 30 1234567',
                'sort' => 10,
            ])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('admin.vacancies.index'));

        $vacancy = Vacancy::query()->sole();
        $this->assertSame('hybrid', $vacancy->work_mode->value);
        $this->assertSame('contract', $vacancy->employment_type->value);
        $this->assertSame('6+', $vacancy->experience?->value);
        $this->assertSame('5000.00', $vacancy->salary_from);
        $this->assertSame($department->id, $vacancy->department_id);
        $this->assertNotNull($vacancy->site_id);
    }

    public function test_wrong_values_are_refused(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/vacancies', [
                'name' => ['en' => 'X'],
                'code' => ['en' => 'x'],
                'work_mode' => 'moon',
                'employment_type' => 'full_time',
                'salary_from' => -1,
                'salary_currency' => 'XXX',
                'contact_email' => 'nope',
            ])
            ->assertSessionHasErrors(['work_mode', 'salary_from', 'salary_currency', 'contact_email']);

        $this->post('/admin/job_locations', ['name' => ['en' => 'Berlin'], 'country' => 'Germany'])->assertSessionHasErrors(['country']);
        $this->assertSame(0, Vacancy::query()->count());
    }

    public function test_applications_cannot_be_created_in_the_admin(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/job_applications')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('can.create', false));
    }

    public function test_the_list_is_filtered_by_stage_and_vacancy(): void
    {
        $vacancy = $this->vacancy();
        $this->stored('Ann', 'new', $vacancy);
        $this->stored('Bob', 'interview', $vacancy);
        $this->stored('Cid', 'interview');

        $this->actingAs($this->admin(), 'admin');

        $names = fn (string $query): array => collect($this->get('/admin/job_applications'.$query)->assertOk()->viewData('page')['props']['items']['data'])->pluck('name')->sort()->values()->all();

        $this->assertSame(['Ann', 'Bob', 'Cid'], $names(''));
        $this->assertSame(['Bob', 'Cid'], $names('?status=interview'));
        $this->assertSame(['Bob'], $names('?status=interview&vacancy_id='.$vacancy->id));
        $this->assertSame(['Ann', 'Bob', 'Cid'], $names('?status=bogus'));
    }

    public function test_hr_moves_an_application_through_the_pipeline_with_notes(): void
    {
        $application = $this->stored('Ann', 'new');

        $this->actingAs($this->admin(), 'admin')
            ->put("/admin/job_applications/{$application->id}", ['status' => 'interview', 'notes' => 'Call on Monday', 'name' => 'Hacker', 'email' => 'x@example.com'])
            ->assertSessionDoesntHaveErrors();

        $application->refresh();
        $this->assertSame('interview', $application->status->value);
        $this->assertSame('Call on Monday', $application->notes);
        // What the candidate sent is not editable.
        $this->assertSame('Ann', $application->name);
        $this->assertSame('ann@example.com', $application->email);

        $this->put("/admin/job_applications/{$application->id}", ['status' => 'hired-ish'])->assertSessionHasErrors(['status']);
    }

    public function test_a_reply_template_is_sent_in_the_candidates_language_and_moves_the_stage(): void
    {
        Mail::fake();
        $application = $this->stored('Anna', 'reviewing');
        $application->forceFill(['locale' => 'de', 'vacancy_name' => 'Backend developer'])->save();

        $template = ReplyTemplate::query()->create([
            'name' => 'Invitation',
            'subject' => ['en' => 'Your application for :vacancy', 'de' => 'Ihre Bewerbung als :vacancy'],
            'body' => ['en' => 'Hello :name', 'de' => "Hallo :name,\nbis bald. :company"],
            'status' => 'interview',
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->get("/admin/job_applications/{$application->id}/edit")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('item.cv_url', null));

        $this->put("/admin/job_applications/{$application->id}", ['status' => 'reviewing', 'notes' => 'Strong CV', 'reply_template_id' => $template->id])
            ->assertSessionDoesntHaveErrors();

        Mail::assertSent(ReplyMail::class, function (ReplyMail $mail): bool {
            $mail->render();

            return $mail->hasTo('anna@example.com')
                && $mail->hasSubject('Ihre Bewerbung als Backend developer')
                && str_contains($mail->render(), 'Hallo Anna,<br />')
                && str_contains($mail->render(), 'Example Labs');
        });

        $application->refresh();
        $this->assertSame('interview', $application->status->value);
        $this->assertStringStartsWith('Strong CV', (string) $application->notes);
        $this->assertStringContainsString('Reply "Invitation" sent by Admin', (string) $application->notes);
    }

    public function test_the_cv_is_downloaded_only_with_the_right_to_see_applications(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('jobs/cv/2026/11/abc.pdf', '%PDF-1.4 demo');
        $application = $this->stored('Ann', 'new');
        $application->forceFill(['cv_path' => 'jobs/cv/2026/11/abc.pdf', 'cv_name' => 'ann-cv.pdf', 'cv_size' => 13])->save();

        $url = "/admin/job_applications/{$application->id}/cv";

        $this->get($url)->assertRedirect();

        $this->actingAs($this->editor(['vacancies.view']), 'admin')->get($url)->assertForbidden();

        $this->actingAs($this->editor(['job_applications.view'], 'hr@example.com'), 'admin')
            ->get($url)
            ->assertOk()
            ->assertDownload('ann-cv.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertSame(url($url), $application->fresh()?->cv_url);
        $this->assertSame('ann-cv.pdf (13 B)', $application->fresh()?->cv_label);

        // Deleting the application deletes the file.
        $this->actingAs($this->admin(), 'admin')->delete("/admin/job_applications/{$application->id}");
        Storage::disk('local')->assertMissing('jobs/cv/2026/11/abc.pdf');
    }

    public function test_the_dashboard_shows_the_pipeline_to_those_who_may_see_it(): void
    {
        $this->stored('Ann', 'new');
        $this->stored('Bob', 'new');
        $this->stored('Cid', 'offer');

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('gadgets', fn ($gadgets): bool => collect($gadgets)->contains(fn (array $gadget): bool => $gadget['title'] === 'Job applications'
                    && $gadget['data']['items'][0]['name'] === 'New: 2'
                    && $gadget['data']['items'][3]['name'] === 'Offer: 1'
                    && str_ends_with($gadget['data']['items'][0]['url'], '/admin/job_applications?status=new'))));

        $this->actingAs($this->editor(['vacancies.view']), 'admin')
            ->get('/admin')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('gadgets', fn ($gadgets): bool => ! collect($gadgets)->contains('title', 'Job applications')));
    }

    private function stored(string $name, string $status, ?Vacancy $vacancy = null): Application
    {
        $application = new Application;
        $application->forceFill([
            'vacancy_id' => $vacancy?->id,
            'vacancy_name' => $vacancy?->getTranslation('name', 'en'),
            'status' => $status,
            'name' => $name,
            'email' => strtolower($name).'@example.com',
            'consent_at' => now(),
            'locale' => 'en',
        ])->save();

        return $application;
    }
}
