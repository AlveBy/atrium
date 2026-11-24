<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Modules\Settings;
use Atrium\OneC\Models\Run;
use Laravel\Dusk\Browser;

/**
 * Setting up the exchange with 1C: the screen names the addresses 1C is
 * given, the password is stored hidden and shown only as a mask, and the
 * exchange screen lists what came.
 */
final class OneCTest extends AdminTestCase
{
    public function test_the_login_and_the_password_are_saved_and_the_password_stays_hidden(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/modules/1c/settings')
                ->waitFor('[data-settings-form]')
                ->assertSee('/atrium-1c/exchange')
                ->assertSee('/bitrix/admin/1c_exchange.php')
                ->type('#login', 'exchange')
                ->type('#password', 'dusk-only-password')
                ->press('[data-action="save"]')
                ->waitForText('Settings saved.');

            $browser->visit('/admin/modules/1c/settings')
                ->waitFor('[data-settings-form]')
                ->assertInputValue('#login', 'exchange')
                ->assertInputValue('#password', '')
                ->assertDontSee('dusk-only-password')
                ->screenshot('onec-settings');
        });

        app(Settings::class)->flush();

        $this->assertSame('exchange', Setting::get('1c.login'));
        $this->assertSame('dusk-only-password', Setting::get('1c.password'));
    }

    public function test_the_exchange_screen_lists_what_came_and_opens_a_run(): void
    {
        $admin = $this->admin();
        $run = Run::query()->create([
            'file' => 'import.xml', 'status' => Run::DONE, 'started_at' => now(),
            'products_created' => 4, 'errors' => 1, 'error' => 'Item 7 cannot be read.',
        ]);
        Run::query()->create(['file' => 'offers.xml', 'status' => Run::FAILED, 'started_at' => now()]);

        $this->browse(function (Browser $browser) use ($admin, $run): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/onec_runs')
                ->waitForText('import.xml')
                ->assertSee('offers.xml')
                ->assertSee('Failed')
                ->screenshot('onec-runs')
                ->visit('/admin/onec_runs/'.$run->id.'/edit')
                ->waitFor('#error')
                ->assertInputValue('#error', 'Item 7 cannot be read.')
                ->assertInputValue('#file', 'import.xml');
        });
    }
}
