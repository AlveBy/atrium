<?php

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\AdminUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\DuskTestCase;

abstract class AdminTestCase extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Dusk keeps one browser for the whole class. Cookies would carry the
     * previous test's admin session into the next one, after the database
     * under it has been rebuilt.
     */
    protected function setUp(): void
    {
        parent::setUp();

        foreach (static::$browsers as $browser) {
            $browser->driver->manage()->deleteAllCookies();
        }
    }

    protected function admin(): AdminUser
    {
        return AdminUser::query()->create([
            'name' => 'Ada Admin',
            'email' => 'ada@example.com',
            'password' => 'correct-horse',
            'is_active' => true,
        ]);
    }
}
