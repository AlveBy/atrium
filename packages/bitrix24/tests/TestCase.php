<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Bitrix24\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\Bitrix24\Bitrix24ServiceProvider;
use Atrium\Forms\FormsServiceProvider;
use Atrium\Forms\Models\Form;
use Atrium\Forms\Models\FormSubmission;
use Atrium\Integrations\IntegrationsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Tighten\Ziggy\ZiggyServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** A webhook as Bitrix24 shows it; made up, it opens nothing. */
    protected const WEBHOOK = 'https://portal.example.test/rest/1/abc123def456/';

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            ZiggyServiceProvider::class,
            McpServiceProvider::class,
            AtriumServiceProvider::class,
            FormsServiceProvider::class,
            IntegrationsServiceProvider::class,
            Bitrix24ServiceProvider::class,
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
        $config->set('atrium.modules', ['alveby/atrium-forms', IntegrationsServiceProvider::MODULE, Bitrix24ServiceProvider::MODULE]);
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->group(function (): void {
            Route::get('page', fn () => response('<html><body><p>Hello</p></body></html>', 200, ['Content-Type' => 'text/html; charset=UTF-8']));
            Route::get('fragment', fn () => response('<p>no body tag</p>', 200, ['Content-Type' => 'text/html']));
            Route::get('data', fn () => response()->json(['ok' => true]));
            Route::get('moved', fn () => redirect('/page'));
            Route::get('missing', fn () => response('<html><body>Not here</body></html>', 404, ['Content-Type' => 'text/html']));
            Route::get('file', fn () => response('<html><body>x</body></html>', 200, ['Content-Type' => 'text/html', 'Content-Disposition' => 'attachment; filename="a.html"']));
        });
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
        Http::preventStrayRequests();
    }

    protected function connect(bool $sendForms = true): void
    {
        Setting::save('bitrix24', ['webhook' => self::WEBHOOK, 'send_forms' => $sendForms]);
    }

    protected function admin(): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Admin',
            'email' => 'admin'.bin2hex(random_bytes(8)).'@example.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $admin->forceFill(['is_superadmin' => true])->save();

        return $admin;
    }

    /**
     * @param  array<int,string>  $permissions
     */
    protected function reader(array $permissions): AdminUser
    {
        $user = $this->admin();
        $user->forceFill(['is_superadmin' => false])->save();
        $user->roles()->attach(AdminRole::query()->create(['name' => 'Role '.random_int(1, 99999), 'permissions' => $permissions])->id);

        return $user->fresh();
    }

    protected function form(string $name = 'Contact us'): Form
    {
        return Form::query()->create(['name' => $name, 'code' => 'form-'.random_int(1, 1_000_000)]);
    }

    /**
     * What a visitor sent: name, e-mail, phone and a message, with their types.
     *
     * @param  array<int,array{name:string,type:string,label:string,value:mixed}>|null  $data
     */
    protected function submit(?Form $form = null, ?array $data = null, ?string $pageUrl = 'https://site.example.test/contacts'): FormSubmission
    {
        $form ??= $this->form();

        return FormSubmission::query()->forceCreate([
            'form_id' => $form->id,
            'form_name' => $form->name,
            'page_url' => $pageUrl,
            'data' => $data ?? [
                ['name' => 'name', 'type' => 'text', 'label' => 'Your name', 'value' => 'Ann'],
                ['name' => 'email', 'type' => 'email', 'label' => 'E-mail', 'value' => 'ann@example.com'],
                ['name' => 'phone', 'type' => 'tel', 'label' => 'Phone', 'value' => '+100 200 300'],
                ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'value' => 'Where is my parcel?'],
            ],
        ]);
    }
}
