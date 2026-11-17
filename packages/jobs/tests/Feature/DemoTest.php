<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Tests\Feature;

use Atrium\Admin\Models\Language;
use Atrium\Admin\Modules\Modules;
use Atrium\Jobs\Models\Application;
use Atrium\Jobs\Models\Department;
use Atrium\Jobs\Models\ReplyTemplate;
use Atrium\Jobs\Models\Vacancy;
use Atrium\Jobs\Tests\TestCase;

/**
 * The demo vacancies of `atrium:install --demo`.
 */
final class DemoTest extends TestCase
{
    public function test_the_demo_fills_the_module_once(): void
    {
        $modules = app(Modules::class);
        $module = $modules->find('alveby/atrium-jobs');

        $this->assertNotNull($module);
        $this->assertTrue($modules->seedDemo($module));
        $this->assertSame(4, Department::query()->count());
        $this->assertSame(4, Vacancy::query()->count());
        $this->assertSame(2, ReplyTemplate::query()->count());
        $this->assertSame(4, Application::query()->count());

        $this->assertFalse($modules->seedDemo($module));
        $this->assertSame(4, Vacancy::query()->count());
    }

    public function test_the_demo_vacancies_are_on_the_site_in_english_german_and_russian(): void
    {
        app(Modules::class)->seedDemo(app(Modules::class)->find('alveby/atrium-jobs'));

        $this->get('/jobs')
            ->assertOk()
            ->assertSee('4 open vacancies')
            ->assertSeeInOrder(['Backend developer (PHP, Laravel)', 'Product designer', 'Account manager', 'Customer support intern'])
            ->assertSee('4,500–6,000 EUR per month')
            ->assertSee('from 30 PLN per hour');

        $this->get('/jobs/backend-developer')->assertOk()->assertSee('application/ld+json', false);

        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);
        Language::query()->create(['code' => 'ru', 'name' => 'Русский']);

        $this->get('/de/jobs/backend-entwickler')->assertOk()->assertSee('Backend-Entwickler (PHP, Laravel) (m/w/d)')->assertSee('Lebenslauf');
        $this->get('/ru/jobs/backend-razrabotchik')->assertOk()->assertSee('Бэкенд-разработчик (PHP, Laravel)')->assertSee('Резюме');

        $this->actingAs($this->admin(), 'admin')->get('/admin/job_applications')->assertOk();
    }
}
