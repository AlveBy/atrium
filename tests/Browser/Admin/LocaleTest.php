<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;

/**
 * Expected texts come from the lang files, so fixing a translation never
 * breaks the test — only a switch that stops working does.
 */
final class LocaleTest extends AdminTestCase
{
    public function test_the_language_picked_on_the_sign_in_page_stays_after_sign_in_and_can_be_changed_in_the_header(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser): void {
            $browser->visit('/admin/login')
                ->waitFor('[data-locale-switcher]')
                ->assertSee(__('atrium::auth.login.subtitle', [], 'en'))
                ->select('[data-locale-switcher]', 'ru')
                ->waitForText(__('atrium::auth.login.subtitle', [], 'ru'))
                ->type('#email', 'ada@example.com')
                ->type('#password', 'correct-horse')
                ->press('button[type="submit"]')
                ->waitForLocation('/admin')
                ->assertSeeLink(__('atrium::menu.dashboard', [], 'ru'))
                ->select('[data-locale-switcher]', 'de')
                ->waitForLink(__('atrium::menu.dashboard', [], 'de'));

            $this->assertSame('de', $browser->script('return document.documentElement.lang;')[0]);
        });

        $this->assertSame('de', $admin->fresh()?->locale);
    }

    public function test_an_administrator_card_opens_and_saves(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit("/admin/users/{$admin->id}/edit")
                ->waitFor('#name')
                ->assertInputValue('#name', 'Ada Admin')
                ->clear('#name')
                ->type('#name', 'Ada Lovelace')
                ->press('button[type="submit"]')
                ->waitForText('Ada Lovelace');
        });

        $this->assertSame('Ada Lovelace', $admin->fresh()?->name);
    }
}
