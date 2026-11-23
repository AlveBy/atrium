<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\AdminUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
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

    /**
     * A superadmin: may do everything, like every administrator before roles.
     */
    protected function admin(): AdminUser
    {
        $admin = AdminUser::query()->create([
            'name' => 'Ada Admin',
            'email' => 'ada@example.com',
            'password' => 'correct-horse',
            'is_active' => true,
        ]);

        $admin->forceFill(['is_superadmin' => true])->save();

        return $admin;
    }

    /**
     * The page sits inside the panel: the sidebar with its menu is there and
     * the entry of the page is the selected one.
     */
    protected function assertInPanel(Browser $browser, string $menuRoute): void
    {
        $browser->waitFor('aside nav')
            ->assertPresent('aside [data-menu-route="admin.dashboard"]')
            ->assertPresent('aside [data-menu-route="'.$menuRoute.'"][aria-current="page"]')
            ->assertPresent('[data-page-content]');
    }

    /**
     * Runs the test with lines added to the .env the served app reads on
     * every request (Dusk has swapped it for .env.dusk.*), then puts it back.
     *
     * @param  array<string, string>  $values
     */
    protected function withEnv(array $values, callable $test): void
    {
        $file = base_path('.env');
        $original = (string) file_get_contents($file);
        // The first line of a name wins, so the old ones go.
        $kept = preg_grep('/^\s*('.implode('|', array_keys($values)).')\s*=/', explode("\n", $original), PREG_GREP_INVERT) ?: [];
        $lines = array_map(fn (string $key, string $value): string => "{$key}={$value}", array_keys($values), $values);

        file_put_contents($file, rtrim(implode("\n", $kept))."\n".implode("\n", $lines)."\n");

        try {
            $test();
        } finally {
            file_put_contents($file, $original);
        }
    }
}
