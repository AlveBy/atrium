<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * "Made by AlveSoft Solutions" at the bottom of the sidebar and under the
 * sign-in form: a link to the English site, the white logo on a dark
 * background, and gone when the site sets ATRIUM_CREDIT=false.
 */
final class CreditTest extends AdminTestCase
{
    private const SIDEBAR = '[data-credit-on="sidebar"]';

    private const PAGE = '[data-credit-on="page"]';

    /**
     * The logo each theme shows in the sidebar: white where the sidebar is
     * dark, which it is in every theme but day.
     *
     * @return array<string, array{string, string}>
     */
    public static function sidebarLogos(): array
    {
        return [
            'graphite' => ['graphite', 'logo-white'],
            'day' => ['day', 'logo'],
            'night' => ['night', 'logo-white'],
            'sand' => ['sand', 'logo-white'],
            'contrast' => ['contrast', 'logo-white'],
        ];
    }

    /**
     * The logo under the sign-in form, on the page background of the
     * configured theme.
     *
     * @return array<string, array{string, string}>
     */
    public static function pageLogos(): array
    {
        return [
            'graphite' => ['graphite', 'logo'],
            'day' => ['day', 'logo'],
            'night' => ['night', 'logo-white'],
            'sand' => ['sand', 'logo'],
            'contrast' => ['contrast', 'logo'],
        ];
    }

    public function test_the_panel_shows_the_credit_with_a_link_to_the_english_site(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor(self::SIDEBAR.' [data-credit-link]')
                ->assertSeeIn(self::SIDEBAR, 'Made by AlveSoft Solutions');

            $this->assertLinksToAlveSoft($browser, self::SIDEBAR);
            $browser->screenshot('credit/sidebar');
        });
    }

    public function test_the_sign_in_page_shows_the_credit_under_the_form(): void
    {
        $this->browse(function (Browser $browser): void {
            $browser->visit('/admin/login')
                ->waitFor(self::PAGE.' [data-credit-link]')
                ->assertSeeIn(self::PAGE, 'Made by AlveSoft Solutions');

            $this->assertLinksToAlveSoft($browser, self::PAGE);

            $below = $browser->script(<<<'JS'
                return document.querySelector('[data-credit-on="page"]').getBoundingClientRect().top
                    >= document.querySelector('form').getBoundingClientRect().bottom;
            JS)[0];
            $this->assertTrue($below, 'the credit is not under the sign-in form');

            $browser->screenshot('credit/login');
        });
    }

    public function test_the_line_follows_the_panel_language(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['locale' => 'ru'])->save();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor(self::SIDEBAR)
                ->assertSeeIn(self::SIDEBAR, 'Сделано в AlveSoft Solutions');
        });
    }

    #[DataProvider('sidebarLogos')]
    public function test_the_sidebar_logo_suits_the_theme(string $theme, string $logo): void
    {
        $admin = $this->admin();
        $admin->forceFill(['theme' => $theme])->save();

        $this->browse(function (Browser $browser) use ($admin, $theme, $logo): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor(self::SIDEBAR.' [data-credit-logo]');

            $this->assertSame($theme, $browser->script('return document.documentElement.dataset.theme;')[0]);
            $this->assertLogo($browser, self::SIDEBAR, $logo);
        });
    }

    #[DataProvider('pageLogos')]
    public function test_the_sign_in_logo_suits_the_theme(string $theme, string $logo): void
    {
        $this->withEnv(['ATRIUM_ADMIN_THEME' => $theme], function () use ($theme, $logo): void {
            $this->browse(function (Browser $browser) use ($theme, $logo): void {
                $browser->visit('/admin/login')->waitFor(self::PAGE.' [data-credit-logo]');

                $this->assertSame($theme, $browser->script('return document.documentElement.dataset.theme;')[0]);
                $this->assertLogo($browser, self::PAGE, $logo);
            });
        });
    }

    public function test_the_collapsed_sidebar_keeps_the_logo_and_names_the_link(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->resize(1280, 800)
                ->loginAs($admin, 'admin')
                ->visit('/admin')
                ->waitFor(self::SIDEBAR.' [data-credit-text]')
                ->click('[data-action="collapse-sidebar"]')
                ->waitUntilMissing(self::SIDEBAR.' [data-credit-text]')
                ->assertAttribute(self::SIDEBAR.' [data-credit-link]', 'aria-label', 'Made by AlveSoft Solutions')
                ->assertAttribute(self::SIDEBAR.' [data-credit-link]', 'title', 'Made by AlveSoft Solutions')
                ->assertVisible(self::SIDEBAR.' [data-credit-logo]')
                ->click('[data-action="collapse-sidebar"]')
                ->waitFor(self::SIDEBAR.' [data-credit-text]')
                ->assertAttributeMissing(self::SIDEBAR.' [data-credit-link]', 'aria-label');
        });
    }

    public function test_a_site_can_hide_the_credit(): void
    {
        $admin = $this->admin();

        $this->withEnv(['ATRIUM_CREDIT' => 'false'], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $browser->visit('/admin/login')
                    ->waitFor('form')
                    ->assertMissing('[data-credit]')
                    ->assertDontSee('AlveSoft')
                    ->loginAs($admin, 'admin')
                    ->visit('/admin')
                    ->waitFor('[data-action="user-menu"]')
                    ->assertMissing('[data-credit]')
                    ->assertDontSee('AlveSoft');
            });
        });
    }

    private function assertLinksToAlveSoft(Browser $browser, string $surface): void
    {
        $link = "{$surface} [data-credit-link]";

        $browser->assertAttribute($link, 'href', 'https://alve.by/en/')
            ->assertAttribute($link, 'target', '_blank')
            ->assertAttribute($link, 'rel', 'noopener');
    }

    /**
     * The logo shown is the expected file, and the file loads.
     */
    private function assertLogo(Browser $browser, string $surface, string $logo): void
    {
        $url = (string) $browser->script(<<<JS
            const image = getComputedStyle(document.querySelector('{$surface} [data-credit-logo]')).backgroundImage;
            return (image.match(/url\\("?([^")]+)"?\\)/) || [])[1] || '';
        JS)[0];

        $this->assertMatchesRegularExpression("#/{$logo}-[\\w-]+\\.svg$#", $url, "{$surface} shows {$url}, expected {$logo}");

        $loaded = $browser->driver->executeAsyncScript(<<<'JS'
            const done = arguments[arguments.length - 1];
            const image = new Image();
            image.onload = () => done(image.naturalWidth > 0);
            image.onerror = () => done(false);
            image.src = arguments[0];
        JS, [$url]);

        $this->assertTrue($loaded, "{$url} does not load");
    }
}
