<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\Blocks\BlocksServiceProvider;
use Atrium\Blocks\Models\Block;
use Atrium\Forms\FormsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
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
            McpServiceProvider::class,
            AtriumServiceProvider::class,
            FormsServiceProvider::class,
            BlocksServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        // CI runs the suite against MySQL and PostgreSQL too: the text is a
        // JSON column, read differently by each.
        $target = env('DB_CONNECTION');
        $config->set('database.default', in_array($target, ['mysql', 'pgsql'], true) ? $target : 'testing');
        $config->set('cache.default', 'array');
        $config->set('atrium.demo', false);
        $config->set('atrium.modules', ['alveby/atrium-blocks', 'alveby/atrium-forms']);
        $config->set('atrium.auth.two_factor.enabled', false);
        $config->set('atrium.dashboard.timings.enabled', false);
    }

    protected function defineRoutes($router): void
    {
        // A page of the site that draws a block in its template.
        Route::middleware('web')->get('shop-page', fn () => Blade::render("<footer>@block('phone')</footer>"));
    }

    /**
     * On MySQL and PostgreSQL the whole suite shares one database, set up by
     * whichever package's tests run first: each side migrates afresh.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        RefreshDatabaseState::$migrated = false;
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function admin(): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $admin->forceFill(['is_superadmin' => true])->save();

        return $admin;
    }

    /**
     * @param  array<int,string>  $permissions
     */
    protected function editor(array $permissions): AdminUser
    {
        $user = AdminUser::query()->create(['name' => 'Editor', 'email' => 'editor@example.com', 'password' => 'secret-password', 'is_active' => true]);
        $user->roles()->attach(AdminRole::query()->create(['name' => 'Role', 'permissions' => $permissions]));

        return $user->fresh();
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function block(string $code, string $content, array $attributes = []): Block
    {
        return Block::query()->create(['name' => ucfirst($code), 'code' => $code, 'content' => ['en' => $content], ...$attributes]);
    }

    protected function placeholder(int|string $id): string
    {
        return '<div data-atrium-embed="block" data-id="'.$id.'"></div>';
    }
}
