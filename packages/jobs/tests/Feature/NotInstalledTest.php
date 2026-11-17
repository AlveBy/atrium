<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Tests\Feature;

use Atrium\Jobs\Tests\TestCase;

/**
 * Required with Composer but not installed with atrium:module:install: the
 * vacancies are nowhere, neither in the panel nor on the site.
 */
final class NotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', []);
    }

    public function test_the_module_stays_out_of_the_panel_and_the_site(): void
    {
        $this->actingAs($this->admin(), 'admin')->get('/admin/vacancies')->assertNotFound();
        $this->get('/admin/job_applications/1/cv')->assertNotFound();
        $this->get('/jobs')->assertNotFound();
        $this->post('/jobs/apply', $this->application())->assertNotFound();
    }
}
