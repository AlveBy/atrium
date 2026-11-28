<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\Integrations\IntegrationsServiceProvider;
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
            IntegrationsServiceProvider::class,
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
        $config->set('atrium.modules', [IntegrationsServiceProvider::MODULE]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::preventStrayRequests();
    }

    protected function admin(bool $super = true, bool $active = true): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin'.bin2hex(random_bytes(8)).'@example.com',
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
}
