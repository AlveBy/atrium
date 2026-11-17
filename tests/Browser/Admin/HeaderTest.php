<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\Site;
use Laravel\Dusk\Browser;

/**
 * The two selects in the header used to show only "Atrium" and "English":
 * nobody could tell that one picks the site being edited and the other the
 * language of the panel, not of the content. Each now says what it is.
 */
final class HeaderTest extends AdminTestCase
{
    public function test_the_site_and_language_switchers_say_what_they_pick(): void
    {
        $admin = $this->admin();
        Site::query()->create(['name' => 'Second site', 'code' => 'second']);

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('[data-site-switcher]')
                ->assertSeeIn('header', __('atrium::layout.site', [], 'en'))
                ->assertSeeIn('header', __('atrium::layout.admin_language', [], 'en'))
                ->assertSeeIn('header', 'English')
                ->assertAttribute('[data-site-switcher]', 'aria-label', __('atrium::layout.site', [], 'en'))
                ->assertAttribute('[data-site-switcher]', 'title', __('atrium::layout.site_hint', [], 'en'))
                ->assertAttribute('[data-locale-switcher]', 'aria-label', __('atrium::layout.admin_language', [], 'en'))
                ->assertAttribute('[data-locale-switcher]', 'title', __('atrium::layout.admin_language_hint', [], 'en'));
        });
    }

    public function test_the_header_fits_a_phone_screen(): void
    {
        $admin = $this->admin();
        Site::query()->create(['name' => 'Second site with a long name', 'code' => 'second']);

        $this->browse(function (Browser $browser) use ($admin): void {
            $this->phone($browser, true);
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('[data-site-switcher]');

            // A header wider than the screen made the phone zoom the whole page out.
            [$page, $screen] = $browser->script([
                'return document.documentElement.scrollWidth;',
                'return window.innerWidth;',
            ]);
            $this->assertLessThanOrEqual($screen, $page);

            // The captions stay on a phone, whole: they are the point.
            $cut = $browser->script(
                'return [...document.querySelectorAll("[data-switcher-caption]")]'
                .'.filter(c => !c.offsetParent || c.scrollWidth > c.clientWidth).map(c => c.textContent);'
            )[0];
            $this->assertSame([], $cut);
            $browser->assertSeeIn('header', __('atrium::layout.site', [], 'en'))
                ->assertSeeIn('header', __('atrium::layout.admin_language', [], 'en'))
                // The language itself is shown by its code.
                ->assertSeeIn('header', 'EN');
            $this->phone($browser, false);
        });
    }

    /**
     * A phone 375 pixels wide, not a narrow desktop window: a desktop
     * window has a floor of 500 pixels on macOS, and on Linux its scroll
     * bar takes 15 of them as soon as the dashboard is taller than the
     * screen. A phone draws its scroll bar over the page.
     */
    private function phone(Browser $browser, bool $on): void
    {
        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', $on
            ? ['cmd' => 'Emulation.setDeviceMetricsOverride', 'params' => ['width' => 375, 'height' => 812, 'deviceScaleFactor' => 2, 'mobile' => true]]
            : ['cmd' => 'Emulation.clearDeviceMetricsOverride', 'params' => (object) []]);
    }
}
