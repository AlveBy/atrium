<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Support\PublicUrl;
use Atrium\Forms\FormsServiceProvider;
use Atrium\Jobs\JobsServiceProvider;
use Atrium\Jobs\Models\Department;
use Atrium\Jobs\Models\Location;
use Atrium\Jobs\Models\Vacancy;
use Atrium\Theme\ThemeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Laravel\Sanctum\Sanctum;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionClass;
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
            ThemeServiceProvider::class,
            FormsServiceProvider::class,
            JobsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $config->set('app.name', 'Example Labs');
        // CI runs the suite against MySQL and PostgreSQL too: the texts are
        // JSON columns, read differently by each.
        $target = env('DB_CONNECTION');
        $config->set('database.default', in_array($target, ['mysql', 'pgsql'], true) ? $target : 'testing');
        $config->set('atrium.demo', false);
        $config->set('atrium.modules', ['alveby/atrium-jobs']);
        $config->set('atrium.auth.two_factor.enabled', false);
        $config->set('atrium.dashboard.timings.enabled', false);
        $config->set('atrium-jobs.notify', 'hr@example.com');
    }

    /**
     * On MySQL and PostgreSQL the whole suite shares one database, and
     * RefreshDatabase migrates it once, for whichever package came first.
     * Each side migrates afresh with its own tables.
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
        $this->loadMigrationsFrom(__DIR__.'/../../forms/database/migrations');
        $this->loadMigrationsFrom(dirname((string) (new ReflectionClass(Sanctum::class))->getFileName(), 2).'/database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        PublicUrl::forgetPaths();
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
    protected function editor(array $permissions, string $email = 'editor@example.com'): AdminUser
    {
        $user = AdminUser::query()->create([
            'name' => 'Editor',
            'email' => $email,
            'password' => 'secret-password',
            'is_active' => true,
        ]);
        $user->roles()->attach(AdminRole::query()->create(['name' => 'Role '.$email, 'permissions' => $permissions]));

        return $user;
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function vacancy(string $name = 'Backend developer', string $code = 'backend-developer', array $attributes = []): Vacancy
    {
        return Vacancy::query()->create([
            'name' => ['en' => $name],
            'code' => ['en' => $code],
            'active' => true,
            ...$attributes,
        ]);
    }

    protected function department(string $name): Department
    {
        return Department::query()->create(['name' => ['en' => $name]]);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function location(string $city, array $attributes = []): Location
    {
        return Location::query()->create(['name' => ['en' => $city], ...$attributes]);
    }

    /**
     * What a candidate sends a few seconds after the page opened.
     *
     * @param  array<string,mixed>  $fields
     * @return array<string,mixed>
     */
    protected function application(?Vacancy $vacancy = null, array $fields = []): array
    {
        return [
            '_apply' => (string) ($vacancy?->id ?? 'general'),
            'vacancy_id' => $vacancy?->id,
            '_t' => Crypt::encryptString((string) (time() - 10)),
            'website' => '',
            'name' => 'Jamie Sample',
            'email' => 'jamie@example.com',
            'phone' => '+49 30 1234567',
            'message' => 'I would love to join.',
            'cv' => UploadedFile::fake()->create('jamie-cv.pdf', 120, 'application/pdf'),
            'consent' => '1',
            ...$fields,
        ];
    }

    /**
     * The JobPosting data of a page, decoded.
     *
     * @return array<string,mixed>
     */
    protected function jsonLd(string $html): array
    {
        $this->assertSame(1, preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches), 'One JobPosting script expected');

        return json_decode($matches[1][0], true, flags: JSON_THROW_ON_ERROR);
    }
}
