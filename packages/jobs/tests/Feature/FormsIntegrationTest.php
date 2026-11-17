<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Tests\Feature;

use Atrium\Forms\Models\Form;
use Atrium\Forms\Models\FormField;
use Atrium\Forms\Models\FormSubmission;
use Atrium\Jobs\Http\Controllers\ApplyController;
use Atrium\Jobs\Mail\ApplicationMail;
use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * With the forms module installed, its "job-application" form adds
 * questions, keeps a copy of each application and adds recipients.
 */
final class FormsIntegrationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', ['alveby/atrium-jobs', 'alveby/atrium-forms']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Mail::fake();
    }

    public function test_its_questions_are_asked_and_a_copy_is_kept(): void
    {
        $form = Form::query()->create(['name' => 'Job application', 'code' => 'job-application', 'recipients' => 'recruiting@example.com']);
        FormField::query()->create(['form_id' => $form->id, 'name' => 'start', 'type' => 'select', 'label' => 'Earliest start', 'options' => "At once\nIn a month", 'required' => true]);
        FormField::query()->create(['form_id' => $form->id, 'name' => 'relocate', 'type' => 'checkbox', 'label' => 'Ready to relocate']);

        $vacancy = $this->vacancy();

        $this->get('/jobs/backend-developer')->assertOk()->assertSee('Earliest start')->assertSee('Ready to relocate');

        $this->post('/jobs/apply', $this->application($vacancy))
            ->assertSessionHasErrors(['fields.start'], errorBag: ApplyController::ERROR_BAG);

        $this->post('/jobs/apply', $this->application($vacancy, ['fields' => ['start' => 'In a month', 'relocate' => '1']]))
            ->assertSessionHasNoErrors();

        $application = Application::query()->sole();
        $this->assertSame("Earliest start: In a month\nReady to relocate: Yes", $application->answers_text);

        $submission = FormSubmission::query()->sole();
        $this->assertSame($submission->id, $application->form_submission_id);
        $this->assertSame($form->id, $submission->form_id);
        $this->assertSame('Backend developer', $submission->data[0]['value']);
        $this->assertSame('jamie-cv.pdf', collect($submission->data)->firstWhere('name', 'cv')['value']);

        Mail::assertSent(ApplicationMail::class, fn (ApplicationMail $mail): bool => $mail->hasTo('recruiting@example.com') && $mail->hasTo('hr@example.com'));

        // Deleted together: no personal data left behind in the forms module.
        $application->delete();
        $this->assertSame(0, FormSubmission::query()->count());
        Storage::disk('local')->assertMissing((string) $application->cv_path);
    }

    public function test_without_the_form_nothing_changes(): void
    {
        $vacancy = $this->vacancy();

        $this->post('/jobs/apply', $this->application($vacancy))->assertSessionHasNoErrors();

        $this->assertNull(Application::query()->sole()->form_submission_id);
        $this->assertSame(0, FormSubmission::query()->count());
    }
}
