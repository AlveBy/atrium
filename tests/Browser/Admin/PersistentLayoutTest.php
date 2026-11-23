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
 * The panel is a single-page app, but every page used to wrap itself in the
 * frame, so each click rebuilt the sidebar, the header and the helper: the
 * menu jumped to the top and its groups closed. The frame is now the layout
 * of the app and lives between visits.
 */
final class PersistentLayoutTest extends AdminTestCase
{
    /**
     * Menu entries that open a plain HTML screen, so a whole page load: the
     * two-factor and social-login modules' own screens (both answer a visit
     * with "load this address as a page", ReloadInertiaVisits). Remove them
     * as the screens become pages of the panel.
     */
    private const HTML_SCREENS = [
        'admin.two-factor.security',
        'admin.two-factor.log',
        'admin.two-factor.users',
        'admin.social.accounts',
        'admin.social.providers',
    ];

    public function test_the_menu_keeps_its_scroll_and_its_open_groups_and_the_active_entry_follows(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            // Short, so that the menu has to scroll.
            $browser->resize(1280, 420)
                ->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('aside nav');

            $this->assertGreaterThan(0, $browser->script('const n = document.querySelector("aside nav"); return n.scrollHeight - n.clientHeight;')[0], 'the menu must overflow for this test');

            // A group the person opened by hand, away from the page they are on.
            $browser->script('document.querySelector("aside nav button[aria-expanded=false]").click();');
            $opened = $browser->script('return document.querySelector("aside nav button[aria-expanded=true]").textContent.trim();')[0];

            // Scroll to the bottom and take the last entry that can be seen.
            [$scrolled, $route] = $browser->script(<<<'JS'
                const nav = document.querySelector('aside nav');
                nav.scrollTop = nav.scrollHeight;
                const links = [...nav.querySelectorAll('a[data-menu-route]')].filter((a) => a.offsetParent !== null);
                return [nav.scrollTop, links[links.length - 1].dataset.menuRoute];
            JS)[0];
            $this->assertGreaterThan(0, $scrolled);

            $selector = "aside a[data-menu-route=\"{$route}\"]";
            $browser->assertAttributeMissing($selector, 'aria-current')->click($selector);
            $browser->waitUntil("document.querySelector('{$selector}').getAttribute('aria-current') === 'page'");

            $this->assertSame([$scrolled], $browser->script('return document.querySelector("aside nav").scrollTop;'));
            $this->assertSame([$opened], $browser->script('return document.querySelector("aside nav button[aria-expanded=true]").textContent.trim();'));
            // One entry is selected, the dashboard no longer is.
            $this->assertCount(1, $browser->elements('aside [aria-current="page"]'));
            $browser->assertAttributeMissing('aside a[data-sidebar-brand]', 'aria-current');
        });
    }

    public function test_the_frame_is_built_once_and_not_again_after_a_visit(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('aside nav');

            // Mark the very elements of the frame: a rebuilt frame has no mark.
            $browser->script('document.querySelector("aside").dataset.built = "once"; document.querySelector("header").dataset.built = "once";');

            $route = $this->openAnotherPage($browser);

            $browser->waitFor('[data-page-title]')
                ->assertAttribute('aside', 'data-built', 'once')
                ->assertAttribute('header', 'data-built', 'once')
                // One frame, not two around the page.
                ->assertScript('return document.querySelectorAll("aside").length;', 1)
                ->assertScript('return document.querySelectorAll("header").length;', 1)
                ->assertScript('return document.querySelectorAll("[data-page-content]").length;', 1);

            // The title still comes from the page: more than the brand alone.
            $this->assertStringContainsString(' — ', $browser->driver->getTitle(), $route);
        });
    }

    public function test_the_site_picked_in_the_header_stays_after_a_visit(): void
    {
        $admin = $this->admin();
        $second = Site::query()->create(['name' => 'Second site', 'code' => 'second']);

        $this->browse(function (Browser $browser) use ($admin, $second): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('[data-site-switcher]')
                ->select('[data-site-switcher]', (string) $second->id)
                ->waitUsing(10, 100, fn () => $browser->script('return document.querySelector("[data-site-switcher]").value;')[0] === (string) $second->id);

            $this->openAnotherPage($browser);

            $browser->assertSeeIn('header', 'Second site')
                ->assertScript('return document.querySelector("[data-site-switcher]").value;', (string) $second->id);
        });
    }

    public function test_the_search_box_starts_afresh_after_a_visit(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->resize(1440, 900)
                ->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor('aside nav');

            if ($browser->element('#admin-global-search') === null) {
                $this->markTestSkipped('The search module is not on in this app.');
            }

            $browser->type('#admin-global-search', 'ad')
                ->click('[data-menu-route="admin.articles.index"]')
                ->waitForLocation('/admin/articles')
                ->assertInputValue('#admin-global-search', '');
        });
    }

    public function test_the_sign_in_page_has_no_frame(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/admin/login')
                ->waitFor('form')
                ->assertMissing('aside')
                ->assertMissing('header')
                ->assertMissing('[data-page-content]');
        });
    }

    public function test_a_page_of_a_module_has_one_frame(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/articles')
                ->waitFor('[data-page-content]')
                ->assertScript('return document.querySelectorAll("aside").length;', 1)
                ->assertScript('return document.querySelectorAll("header").length;', 1)
                ->assertScript('return document.querySelectorAll("[data-page-content]").length;', 1)
                ->assertScript('return document.querySelectorAll("[data-sidebar-brand]").length;', 1);
        });
    }

    /**
     * "The page reloads on every click": a real reload loses what the page
     * holds in memory, a visit does not. Every entry of the menu, groups
     * opened, is clicked; a marker set on the window must survive each click,
     * and the click must go out as an Inertia request, not as a page load.
     *
     * Entries of a module whose screens are plain HTML (not Inertia pages)
     * are loaded as whole pages on purpose; they are listed here, and the
     * list must be exactly them: a screen moved into the panel has to leave
     * it, a new entry that reloads fails the test.
     */
    public function test_every_entry_of_the_menu_is_a_visit_and_not_a_page_load(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $prepare = function () use ($browser): array {
                $browser->visit('/admin')->waitFor('aside nav');
                $browser->script('document.querySelectorAll("aside nav button[aria-expanded=false]").forEach((b) => b.click());');

                // Both ways a request can leave the page are watched for the header.
                $browser->script(<<<'JS'
                    window.__marker = 1;
                    window.__inertia = [];
                    const open = XMLHttpRequest.prototype.open;
                    const header = XMLHttpRequest.prototype.setRequestHeader;
                    XMLHttpRequest.prototype.open = function (...args) { this.__url = args[1]; return open.apply(this, args); };
                    XMLHttpRequest.prototype.setRequestHeader = function (name, value) {
                        if (name.toLowerCase() === 'x-inertia') window.__inertia.push(this.__url);
                        return header.call(this, name, value);
                    };
                    const request = window.fetch;
                    window.fetch = function (input, init) {
                        const headers = new Headers((init && init.headers) || (input && input.headers) || {});
                        if (headers.has('x-inertia')) window.__inertia.push(String(input.url || input));
                        return request.apply(this, arguments);
                    };
                JS);

                return $browser->script('return [...document.querySelectorAll("aside nav a[data-menu-route]")].map((a) => a.dataset.menuRoute);')[0];
            };

            $browser->resize(1440, 1400)->loginAs($admin, 'admin');
            $routes = $prepare();
            $this->assertGreaterThan(5, count($routes));

            $reloaded = [];

            foreach ($routes as $route) {
                $selector = "aside a[data-menu-route=\"{$route}\"]";
                $before = $browser->script('return window.__inertia.length;')[0];

                // Groups the frame closed again would hide the entry.
                $browser->script('document.querySelectorAll("aside nav button[aria-expanded=false]").forEach((b) => b.click());');
                $browser->click($selector)->waitUsing(10, 100, fn () => $browser->script(
                    "return window.__marker === undefined || document.querySelector('{$selector}')?.getAttribute('aria-current') === 'page';"
                )[0] === true);

                if ($browser->script('return window.__marker ?? null;') === [null]) {
                    // A whole page was loaded: the marker is gone with the old one.
                    $reloaded[] = $route;
                    $prepare();

                    continue;
                }

                $this->assertGreaterThan($before, $browser->script('return window.__inertia.length;')[0], "{$route} did not go out as an Inertia request");
            }

            $this->assertSame(self::HTML_SCREENS, $reloaded);
        });
    }

    public function test_the_drawer_of_a_phone_closes_after_a_visit(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $this->phone($browser, true);

            try {
                $browser->loginAs($admin, 'admin')
                    ->visit('/admin')
                    ->waitFor('header');

                $closed = 'return document.querySelector("aside").classList.contains("-translate-x-full");';
                $this->assertSame([true], $browser->script($closed));

                $browser->script('document.querySelector("header button").click();');
                $browser->waitUsing(5, 100, fn () => $browser->script($closed) === [false]);

                $browser->script(<<<'JS'
                    [...document.querySelectorAll('aside nav a[data-menu-route]')]
                        .find((a) => a.offsetParent !== null && a.getAttribute('aria-current') !== 'page')
                        .click();
                JS);
                $browser->waitUsing(5, 100, fn () => $browser->script($closed) === [true]);
            } finally {
                $this->phone($browser, false);
            }
        });
    }

    /**
     * Clicks the first entry of the menu that can be seen and is not the page
     * we are on, and waits until the menu marks it as the current one.
     */
    private function openAnotherPage(Browser $browser): string
    {
        $route = $browser->script(<<<'JS'
            const link = [...document.querySelectorAll('aside nav a[data-menu-route]')]
                .find((a) => a.offsetParent !== null && a.getAttribute('aria-current') !== 'page');
            return link.dataset.menuRoute;
        JS)[0];

        $selector = "aside a[data-menu-route=\"{$route}\"]";
        $browser->click($selector)
            ->waitUntil("document.querySelector('{$selector}').getAttribute('aria-current') === 'page'");

        return $route;
    }

    /** A phone, not a narrow window: its own width and touch, as in HeaderTest. */
    private function phone(Browser $browser, bool $on): void
    {
        $browser->driver->executeCustomCommand('/session/:sessionId/goog/cdp/execute', 'POST', $on
            ? ['cmd' => 'Emulation.setDeviceMetricsOverride', 'params' => ['width' => 375, 'height' => 812, 'deviceScaleFactor' => 2, 'mobile' => true]]
            : ['cmd' => 'Emulation.clearDeviceMetricsOverride', 'params' => (object) []]);
    }
}
