<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Facades\Setting;
use Atrium\Admin\Modules\Settings;
use Laravel\Dusk\Browser;

/**
 * A module's settings, from the "Modules" entry of the menu to the value the
 * site reads.
 */
final class ModuleSettingsTest extends AdminTestCase
{
    public function test_set_up_the_catalog(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->clickLink('Modules')
                ->waitFor('[data-module="alveby/atrium-catalog"]')
                ->assertSeeIn('[data-module="alveby/atrium-catalog"]', 'alveby/atrium-catalog')
                ->click('[data-module="alveby/atrium-catalog"] [data-action="settings"]')
                ->waitFor('[data-settings-form]')
                ->assertSee('Products per page')
                ->assertPresent('[data-settings-site]')
                ->type('#per_page', '6')
                ->press('[data-action="save"]')
                ->waitForText('Settings saved.');
        });

        app(Settings::class)->flush();

        $this->assertSame(6, Setting::get('catalog.per_page'));
    }
}
