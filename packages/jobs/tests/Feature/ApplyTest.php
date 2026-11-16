<?php

declare(strict_types=1);

namespace Atrium\Jobs\Tests\Feature;

use Atrium\Jobs\Http\Controllers\ApplyController;
use Atrium\Jobs\Mail\ApplicationMail;
use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * A candidate applies: the CV goes to the private disk, the application
 * into the pipeline, HR gets a letter.
 */
final class ApplyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Mail::fake();
    }

    public function test_an_application_is_stored_with_a_private_cv_and_hr_is_told(): void
    {
        $vacancy = $this->vacancy(attributes: ['contact_email' => 'Lead@Example.com']);

        $this->from('/jobs/backend-developer')
            ->post('/jobs/apply', $this->application($vacancy))
            ->assertSessionHasNoErrors()
            ->assertRedirect('/jobs/backend-developer#apply')
            ->assertSessionHas('atrium-jobs.sent', $vacancy->id);

        $application = Application::query()->sole();
        $this->assertSame($vacancy->id, $application->vacancy_id);
        $this->assertSame('Backend developer', $application->vacancy_name);
        $this->assertSame('new', $application->status->value);
        $this->assertSame('Jamie Sample', $application->name);
        $this->assertSame('jamie-cv.pdf', $application->cv_name);
        $this->assertNotNull($application->consent_at);
        $this->assertSame('en', $application->locale);
        $this->assertStringStartsWith('jobs/cv/', (string) $application->cv_path);
        Storage::disk('local')->assertExists((string) $application->cv_path);
        // Not on the public disk, not under a guessable name.
        $this->assertStringNotContainsString('jamie', (string) $application->cv_path);

        Mail::assertSent(ApplicationMail::class, function (ApplicationMail $mail) use ($application): bool {
            return $mail->application->is($application)
                && $mail->hasTo('hr@example.com')
                && $mail->hasTo('lead@example.com')
                && $mail->hasReplyTo('jamie@example.com')
                && $mail->attachments === [];
        });
    }

    public function test_a_general_cv_goes_without_a_vacancy(): void
    {
        $this->post('/jobs/apply', $this->application())->assertSessionHasNoErrors();

        $application = Application::query()->sole();
        $this->assertNull($application->vacancy_id);
        $this->assertNull($application->vacancy_name);
        Mail::assertSent(ApplicationMail::class, fn (ApplicationMail $mail): bool => $mail->hasTo('hr@example.com'));
    }

    public function test_the_fields_are_checked(): void
    {
        $vacancy = $this->vacancy();

        $this->post('/jobs/apply', $this->application($vacancy, [
            'name' => '',
            'email' => 'not-an-email',
            'phone' => 'call me',
            'consent' => '',
            'cv' => UploadedFile::fake()->create('cv.exe', 10, 'application/octet-stream'),
        ]))->assertSessionHasErrors(['name', 'email', 'phone', 'consent', 'cv'], errorBag: ApplyController::ERROR_BAG);

        $this->assertSame(0, Application::query()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        Mail::assertNothingSent();
    }

    public function test_the_cv_size_limit_and_requirement_come_from_the_config(): void
    {
        config(['atrium-jobs.cv_max_size' => 100]);

        $this->post('/jobs/apply', $this->application(fields: ['cv' => UploadedFile::fake()->create('cv.pdf', 101, 'application/pdf')]))
            ->assertSessionHasErrors(['cv'], errorBag: ApplyController::ERROR_BAG);

        $data = $this->application();
        unset($data['cv']);
        $this->post('/jobs/apply', $data)->assertSessionHasErrors(['cv'], errorBag: ApplyController::ERROR_BAG);

        config(['atrium-jobs.cv_required' => false]);
        $this->post('/jobs/apply', $data)->assertSessionHasNoErrors();
        $this->assertNull(Application::query()->sole()->cv_path);
    }

    public function test_doc_and_docx_are_accepted(): void
    {
        $this->post('/jobs/apply', $this->application(fields: ['cv' => UploadedFile::fake()->create('cv.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')]))
            ->assertSessionHasNoErrors();
        $this->post('/jobs/apply', $this->application(fields: ['cv' => UploadedFile::fake()->create('cv.doc', 50, 'application/msword')]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Application::query()->count());
    }

    public function test_a_closed_or_unknown_vacancy_takes_no_applications(): void
    {
        $closed = $this->vacancy(attributes: ['active_to' => now()->subDay()]);
        $off = $this->vacancy('Designer', 'designer', ['active' => false]);

        foreach ([$closed->id, $off->id, 999, 'abc'] as $id) {
            $this->post('/jobs/apply', [...$this->application(), 'vacancy_id' => $id])
                ->assertSessionHasErrors(['form'], errorBag: ApplyController::ERROR_BAG);
        }

        $this->assertSame(0, Application::query()->count());
    }

    public function test_bots_are_turned_away(): void
    {
        // The trap: told it worked, nothing stored.
        $this->post('/jobs/apply', $this->application(fields: ['website' => 'http://spam.example']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('atrium-jobs.sent');

        // Sent at once, or with a forged time.
        $this->post('/jobs/apply', $this->application(fields: ['_t' => Crypt::encryptString((string) time())]))
            ->assertSessionHasErrors(['form'], errorBag: ApplyController::ERROR_BAG);
        $this->post('/jobs/apply', $this->application(fields: ['_t' => (string) (time() - 60)]))
            ->assertSessionHasErrors(['form'], errorBag: ApplyController::ERROR_BAG);

        $this->assertSame(0, Application::query()->count());
        Mail::assertNothingSent();
    }

    public function test_too_many_applications_from_one_address_are_stopped(): void
    {
        config(['atrium-jobs.per_hour' => 2]);

        $this->post('/jobs/apply', $this->application())->assertSessionHasNoErrors();
        $this->post('/jobs/apply', $this->application())->assertSessionHasNoErrors();
        $this->post('/jobs/apply', $this->application())->assertSessionHasErrors(['form'], errorBag: ApplyController::ERROR_BAG);
        $this->postJson('/jobs/apply', $this->application())->assertStatus(429);

        $this->assertSame(2, Application::query()->count());
    }

    public function test_json_requests_get_json(): void
    {
        $this->postJson('/jobs/apply', $this->application())->assertOk()->assertJsonPath('message', 'Thank you! Your application has been sent.');
        $this->postJson('/jobs/apply', $this->application(fields: ['email' => '']))->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_a_mail_server_that_is_down_does_not_lose_the_application(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->post('/jobs/apply', $this->application())->assertSessionHasNoErrors();

        $this->assertSame(1, Application::query()->count());
    }
}
