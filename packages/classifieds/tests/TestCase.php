<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Admin\AtriumServiceProvider;
use Atrium\Admin\Models\AdminUser;
use Atrium\Admin\Support\PublicUrl;
use Atrium\Admin\Support\Sites;
use Atrium\Classifieds\Board;
use Atrium\Classifieds\ClassifiedsServiceProvider;
use Atrium\Classifieds\Enums\AdStatus;
use Atrium\Classifieds\Models\Ad;
use Atrium\Classifieds\Models\Category;
use Atrium\Classifieds\Models\City;
use Atrium\Classifieds\Models\Region;
use Atrium\Classifieds\Support\Owner;
use Atrium\Classifieds\Tests\Fixtures\User;
use Atrium\Theme\ThemeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Tighten\Ziggy\ZiggyServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return array_values(array_filter([
            InertiaServiceProvider::class,
            ZiggyServiceProvider::class,
            AtriumServiceProvider::class,
            class_exists(ThemeServiceProvider::class) ? ThemeServiceProvider::class : null,
            ClassifiedsServiceProvider::class,
        ]));
    }

    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $config->set('app.name', 'Demo board');
        $target = env('DB_CONNECTION');
        $config->set('database.default', in_array($target, ['mysql', 'pgsql'], true) ? $target : 'testing');
        $config->set('atrium.demo', false);
        $config->set('atrium.auth.two_factor.enabled', false);
        $config->set('atrium.dashboard.timings.enabled', false);
        $config->set('atrium.modules', [Board::MODULE]);
        $config->set('auth.providers.users.model', User::class);
        $config->set('cache.default', 'array');
        $config->set('mail.default', 'array');
    }

    protected function defineRoutes($router): void
    {
        Route::get('/login', static fn () => 'login')->name('login');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        PublicUrl::forgetPaths();
        Cache::flush();
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function user(string $name = 'Robin Vale'): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'.'.uniqid().'@example.com',
            'password' => 'secret-password',
        ]);
    }

    protected function owner(User $user): Owner
    {
        return new Owner((int) $user->id, null, (string) $user->name);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function category(string $code = 'transport', ?Category $parent = null, array $attributes = []): Category
    {
        return Category::query()->create([
            'name' => ['en' => ucfirst($code), 'ru' => 'Категория '.$code],
            'code' => $code,
            'parent_id' => $parent?->id,
            ...$attributes,
        ]);
    }

    protected function city(string $code = 'northbridge', ?Region $region = null): City
    {
        return City::query()->create([
            'name' => ['en' => ucfirst($code)],
            'code' => $code,
            'region_id' => $region?->id,
            'lat' => 40.7,
            'lng' => -74.0,
        ]);
    }

    protected function region(string $code = 'lake-county'): Region
    {
        return Region::query()->create(['name' => ['en' => ucwords(str_replace('-', ' ', $code))], 'code' => $code]);
    }

    /**
     * @param  array<string,mixed>  $attributes
     */
    protected function ad(User|int $user, Category $category, array $attributes = []): Ad
    {
        $ad = new Ad([
            'account_user_id' => $user instanceof User ? $user->id : $user,
            'category_id' => $category->id,
            'title' => 'Blue bicycle',
            'text' => 'Seven gears, lights.',
            'price' => 150,
            'contact_name' => 'Robin',
            'contact_phone' => '+1 202 555 0101',
            'contact_email' => 'robin@example.com',
            'status' => AdStatus::Active,
            ...$attributes,
        ]);
        $ad->save();

        return $ad->refresh();
    }

    protected function admin(): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Moderator',
            'email' => 'moderator@example.com',
            'password' => 'secret-password',
            'is_active' => true,
        ]);

        $admin->forceFill(['is_superadmin' => true])->save();

        return $admin;
    }

    protected function setting(string $key, mixed $value): void
    {
        app(\Atrium\Admin\Modules\Settings::class)->save('classifieds', [$key => $value]);
    }

    protected function site(): int
    {
        return (int) app(Sites::class)->current()->id;
    }
}
