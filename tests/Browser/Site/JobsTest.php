<?php

declare(strict_types=1);

namespace Tests\Browser\Site;

use Atrium\Admin\Models\AdminUser;
use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Models\Department;
use Atrium\Jobs\Models\Vacancy;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * A candidate finds a vacancy through the filter and applies with a CV;
 * HR opens the application and moves it to the interview.
 */
final class JobsTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_a_candidate_applies_and_hr_moves_the_application_on(): void
    {
        $engineering = Department::query()->create(['name' => ['en' => 'Engineering']]);
        $sales = Department::query()->create(['name' => ['en' => 'Sales']]);
        Vacancy::query()->create(['name' => ['en' => 'Backend developer'], 'code' => ['en' => 'backend-developer'], 'active' => true, 'department_id' => $engineering->id, 'duties' => ['en' => '<p>Write code</p>']]);
        Vacancy::query()->create(['name' => ['en' => 'Sales manager'], 'code' => ['en' => 'sales-manager'], 'active' => true, 'department_id' => $sales->id]);

        $dir = sys_get_temp_dir().'/atrium-jobs-dusk-'.bin2hex(random_bytes(4));
        mkdir($dir);
        $cv = $dir.'/jamie-cv.pdf';
        file_put_contents($cv, "%PDF-1.4\n%demo\n");

        $this->browse(function (Browser $browser) use ($engineering, $cv): void {
            $browser->visit('/jobs')
                ->assertSee('2 open vacancies')
                ->select('department', (string) $engineering->id)
                ->press('Show')
                ->waitForText('1 open vacancy')
                ->assertQueryStringHas('department', (string) $engineering->id)
                ->assertDontSeeLink('Sales manager')
                ->clickLink('Backend developer')
                ->assertPathIs('/jobs/backend-developer')
                ->assertSee('Write code')
                ->type('name', 'Jamie Sample')
                ->type('email', 'jamie@example.com')
                ->type('message', 'I would love to join.')
                ->attach('cv', $cv)
                ->check('consent')
                // A person takes a few seconds; the form refuses faster ones.
                ->pause(3500)
                ->press('Apply')
                ->waitForText('Thank you! Your application has been sent.');
        });

        $application = Application::query()->sole();
        $this->assertSame('Jamie Sample', $application->name);
        $this->assertSame('new', $application->status->value);
        $this->assertTrue(Storage::disk('local')->exists((string) $application->cv_path));

        $admin = AdminUser::query()->create(['name' => 'Hal HR', 'email' => 'hal@example.com', 'password' => 'correct-horse', 'is_active' => true]);
        $admin->forceFill(['is_superadmin' => true])->save();

        $this->browse(function (Browser $browser) use ($admin, $application): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/job_applications')
                ->waitForText('Jamie Sample')
                ->assertSee('jamie-cv')
                ->click('[data-action="edit"]')
                ->waitForLocation("/admin/job_applications/{$application->id}/edit")
                ->waitFor('#status')
                ->select('#status', 'interview')
                ->type('#notes', 'Strong CV, call on Monday')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/job_applications', 10);
        });

        $application->refresh();
        $this->assertSame('interview', $application->status->value);
        $this->assertSame('Strong CV, call on Monday', $application->notes);

        Storage::disk('local')->delete((string) $application->cv_path);
        @unlink($cv);
        @rmdir($dir);
    }
}
