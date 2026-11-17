<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Models\AdminRole;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Support\PublicUrl;
use Atrium\ImageMap\ImageMapServiceProvider;
use Atrium\ImageMap\Models\Area;
use Atrium\ImageMap\Models\ImageMap;
use Atrium\ImageMap\Models\Layer;
use Atrium\ImageMap\Models\Status;
use Atrium\Theme\ThemeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Storage;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Tighten\Ziggy\ZiggyServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** Modules installed for the test; a test class may set others. */
    protected array $modules = ['alveby/atrium-image-map'];

    /**
     * On MySQL and PostgreSQL the whole suite shares one database: each
     * class migrates it again with the modules it installs.
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

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            ZiggyServiceProvider::class,
            AtriumServiceProvider::class,
            ThemeServiceProvider::class,
            ImageMapServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $config->set('app.name', 'Mall');
        // CI runs the suite against MySQL and PostgreSQL too: the texts and
        // the geometry are JSON columns, read differently by each.
        $target = env('DB_CONNECTION');
        $config->set('database.default', in_array($target, ['mysql', 'pgsql'], true) ? $target : 'testing');
        $config->set('atrium.demo', false);
        $config->set('atrium.modules', $this->modules);
        $config->set('atrium.auth.two_factor.enabled', false);
        $config->set('atrium.dashboard.timings.enabled', false);
        $config->set('atrium.uploads.disk', 'public');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        PublicUrl::forgetPaths();
        Storage::fake('public');
    }

    protected function admin(): AdminUser
    {
        $admin = $this->user('admin@example.com');
        $admin->forceFill(['is_superadmin' => true])->save();

        return $admin;
    }

    /**
     * @param  array<int,string>  $permissions
     */
    protected function editor(array $permissions): AdminUser
    {
        $user = $this->user('editor'.random_int(1, PHP_INT_MAX).'@example.com');
        $user->roles()->attach(AdminRole::query()->create(['name' => 'Role '.random_int(1, PHP_INT_MAX), 'permissions' => $permissions]));

        return $user->fresh();
    }

    private function user(string $email): AdminUser
    {
        return AdminUser::query()->create([
            'name' => 'Admin',
            'email' => $email,
            'password' => 'secret-password',
            'is_active' => true,
        ]);
    }

    /**
     * A picture of the given size on the uploads disk, as the image field keeps it.
     */
    protected function picture(int $width = 1000, int $height = 600, string $name = 'plan.svg'): string
    {
        $path = 'image-maps/'.$name;
        Storage::disk('public')->put($path, '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'"></svg>');

        return $path;
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function map(string $code = 'mall', array $attributes = []): ImageMap
    {
        return ImageMap::query()->create(['name' => ['en' => 'Mall'], 'code' => $code, ...$attributes]);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function layer(?ImageMap $map = null, array $attributes = []): Layer
    {
        return Layer::query()->create([
            'map_id' => ($map ?? $this->map())->id,
            'name' => ['en' => 'Floor 1'],
            'image' => $this->picture(),
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function area(Layer $layer, array $attributes = []): Area
    {
        return Area::query()->create([
            'layer_id' => $layer->id,
            'shape' => 'rect',
            'geometry' => ['x' => 10, 'y' => 10, 'width' => 100, 'height' => 50],
            'title' => ['en' => 'Shop'],
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function status(string $code = 'free', array $attributes = []): Status
    {
        return Status::query()->create(['name' => ['en' => ucfirst($code)], 'code' => $code, 'color' => '#009E73', ...$attributes]);
    }
}
