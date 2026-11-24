<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Models\AdminUser;
use Atrium\Catalog\CatalogServiceProvider;
use Atrium\Integrations\IntegrationsServiceProvider;
use Atrium\OneC\OneCServiceProvider;
use Atrium\OneC\Support\Access;
use Atrium\Theme\ThemeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Tighten\Ziggy\ZiggyServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** What the settings hold; made up, it opens nothing. */
    protected const LOGIN = 'exchange';

    protected const PASSWORD = 'not-a-real-password';

    /** A picture of one pixel. */
    protected const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            ZiggyServiceProvider::class,
            AtriumServiceProvider::class,
            ThemeServiceProvider::class,
            CatalogServiceProvider::class,
            IntegrationsServiceProvider::class,
            OneCServiceProvider::class,
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
        $config->set('atrium.modules', ['alveby/atrium-catalog', IntegrationsServiceProvider::MODULE, OneCServiceProvider::MODULE]);
    }

    /**
     * On MySQL and PostgreSQL the whole suite shares one database, and
     * RefreshDatabase migrates it once, for whichever package came first:
     * without these tables. Each side migrates afresh.
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
        Setting::save('1c', ['login' => self::LOGIN, 'password' => self::PASSWORD]);
    }

    /**
     * One call of 1C: the query, the body of a POST, the cookie it was given.
     *
     * @param  array<string,string>  $query
     */
    protected function exchange(array $query, ?string $token = null, ?string $body = null): TestResponse
    {
        $url = '/'.OneCServiceProvider::PATH.'?'.http_build_query($query);

        return $this->call($body === null ? 'GET' : 'POST', $url, [], $token === null ? [] : [Access::COOKIE => $token], [], [], $body);
    }

    /** Signs in the way 1C does and returns the cookie value it is given. */
    protected function signIn(): string
    {
        $response = $this->get('/'.OneCServiceProvider::PATH.'?type=catalog&mode=checkauth', ['Authorization' => 'Basic '.base64_encode(self::LOGIN.':'.self::PASSWORD)]);

        return explode("\n", $response->getContent())[2];
    }

    /** Puts a file in the inbox, as 1C would (in one piece). */
    protected function upload(string $token, string $name, string $content): void
    {
        $this->exchange(['type' => 'catalog', 'mode' => 'file', 'filename' => $name], $token, $content)->assertSee('success');
    }

    protected function fixture(string $name): string
    {
        return (string) file_get_contents(__DIR__.'/Fixtures/'.$name);
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
}
