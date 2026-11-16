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
 * Stage 4b acceptance: counters and codes are set in the admin and show up on
 * the site; robots.txt and sitemap.xml are served by the core.
 */
final class SiteCodesTest extends AdminTestCase
{
    public function test_codes_set_in_the_admin_appear_on_the_site(): void
    {
        $admin = $this->admin();
        $site = Site::query()->where('code', 'main')->sole();

        $this->browse(function (Browser $browser) use ($admin, $site): void {
            $browser->loginAs($admin, 'admin')
                ->visit("/admin/sites/{$site->id}/edit")
                ->waitFor('[data-tab="codes"]')
                ->click('[data-tab="codes"]')
                ->waitFor('#google_tag_id')
                ->type('#google_tag_id', 'g-dusk12345')
                ->type('#google_verification', '<meta name="google-site-verification" content="dusk-token" />')
                ->type('#head_code', '<meta name="dusk-head" content="ok">')
                ->type('#body_end_code', '<script>window.duskChat = 42</script>')
                // The sticky header would take a click on a tab scrolled under it.
                ->script('window.scrollTo(0, 0)');

            $browser->click('[data-tab="robots"]')
                ->waitFor('#robots_txt')
                ->type('#robots_txt', "User-agent: *\nDisallow: /search")
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/sites');

            $site->refresh();
            $this->assertSame('G-DUSK12345', $site->google_tag_id);
            $this->assertSame('dusk-token', $site->google_verification);

            // An administrator sees the site without counters, but with the rest.
            // Dusk's assertPresent looks inside <body> only; the head is asked directly.
            $browser->visit('/');
            $this->assertSame([true, true, false, 42], $browser->script(
                'return [
                    !!document.querySelector(\'head meta[name="dusk-head"]\'),
                    !!document.querySelector(\'head meta[name="google-site-verification"][content="dusk-token"]\'),
                    !!document.querySelector(\'script[src*="G-DUSK12345"]\'),
                    window.duskChat,
                ];',
            )[0]);

            // A visitor is counted.
            $browser->logout('admin')->visit('/');
            $this->assertTrue($browser->script(
                'return !!document.querySelector(\'head script[src="https://www.googletagmanager.com/gtag/js?id=G-DUSK12345"]\');',
            )[0]);

            // Not a production server: robots are turned away whatever the admin wrote.
            $browser->visit('/robots.txt')->assertSee('Disallow: /')->assertDontSee('/search');

            $browser->visit('/sitemap.xml');
            $this->assertStringContainsString('<loc>'.rtrim((string) config('app.url'), '/').'</loc>', $browser->driver->getPageSource());
        });
    }
}
