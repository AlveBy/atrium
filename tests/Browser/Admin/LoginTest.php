<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;

final class LoginTest extends AdminTestCase
{
    public function test_an_administrator_signs_in_and_out(): void
    {
        $this->admin();

        $this->browse(function (Browser $browser): void {
            $browser->visit('/admin')
                ->assertPathIs('/admin/login')
                ->waitFor('#email')
                ->type('#email', 'ada@example.com')
                ->type('#password', 'correct-horse')
                ->press('button[type="submit"]')
                ->waitForLocation('/admin')
                ->assertSee('Ada Admin')
                ->assertSeeLink('Articles');
        });
    }

    public function test_a_wrong_password_keeps_the_visitor_out(): void
    {
        $this->admin();

        $this->browse(function (Browser $browser): void {
            $browser->visit('/admin/login')
                ->waitFor('#email')
                ->type('#email', 'ada@example.com')
                ->type('#password', 'wrong-password')
                ->press('button[type="submit"]')
                ->waitFor('.text-red-600')
                ->assertPathIs('/admin/login');
        });
    }
}
