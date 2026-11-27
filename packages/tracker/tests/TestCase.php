<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Modules\Settings;
use Atrium\Tracker\Models\Project;
use Atrium\Tracker\Models\Task;
use Atrium\Tracker\Models\Term;
use Atrium\Tracker\TrackerServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Tighten\Ziggy\ZiggyServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            ZiggyServiceProvider::class,
            AtriumServiceProvider::class,
            TrackerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $config->set('app.name', 'Demo site');
        $config->set('app.url', 'http://localhost');
        // CI runs the suite against MySQL and PostgreSQL too.
        $target = env('DB_CONNECTION');
        $config->set('database.default', in_array($target, ['mysql', 'pgsql'], true) ? $target : 'testing');
        $config->set('cache.default', 'array');
        $config->set('mail.default', 'array');
        $config->set('atrium.demo', false);
        $config->set('atrium.auth.two_factor.enabled', false);
        $config->set('atrium.dashboard.timings.enabled', false);
        // Installed, as atrium:module:install leaves it.
        $config->set('atrium.modules', [TrackerServiceProvider::MODULE]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Term::flush();

        $this->withoutVite();
        Http::preventStrayRequests();
    }

    protected function admin(bool $super = true, bool $active = true, string $name = 'Admin'): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => $name,
            'email' => 'admin'.random_int(1, 1_000_000).'@example.com',
            'password' => 'secret-password',
            'is_active' => $active,
        ]);

        $admin->forceFill(['is_superadmin' => $super])->save();

        return $admin;
    }

    /**
     * @param  array<int,string>  $permissions
     */
    protected function reader(array $permissions): AdminUser
    {
        $user = $this->admin(false);
        $user->roles()->attach(AdminRole::query()->create(['name' => 'Role '.random_int(1, 99999), 'permissions' => $permissions])->id);

        return $user->fresh();
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function project(array $attributes = []): Project
    {
        return Project::query()->create([...['name' => 'Atrium', 'key' => 'ATR'], ...$attributes]);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function task(?Project $project = null, array $attributes = []): Task
    {
        return Task::query()->create([...['project_id' => ($project ?? Project::query()->orderBy('id')->first() ?? $this->project())->getKey(), 'title' => 'Write the docs', 'status' => 'todo'], ...$attributes]);
    }

    /**
     * Settings as saved in the admin.
     *
     * @param  array<string,mixed>  $values
     */
    protected function settings(array $values): void
    {
        $this->app->make(Settings::class)->save('tracker', $values);
        $this->app->make(Settings::class)->flush();
    }
}
