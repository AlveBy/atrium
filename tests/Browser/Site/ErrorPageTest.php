<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Site;

use Atrium\Admin\Models\Language;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * A visitor who follows a dead link lands on the theme's 404 page: the
 * site's frame and menu, a way home, in the language of the address.
 */
final class ErrorPageTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_a_dead_link_shows_the_themes_404_page_and_leads_home(): void
    {
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);

        $this->browse(function (Browser $browser): void {
            $browser->visit('/')->script("localStorage.setItem('atrium-theme', 'light')");

            $browser->visit('/no/such/page')
                ->assertSeeIn('.error-code', '404')
                ->assertSeeIn('h1', 'Page not found')
                ->assertPresent('.site-header .menu')
                ->assertSeeIn('.site-footer .credit', 'Made with Atrium')
                ->screenshot('error-404-light');

            $browser->script("localStorage.setItem('atrium-theme', 'dark')");
            $browser->refresh()
                ->assertSeeIn('h1', 'Page not found')
                ->screenshot('error-404-dark');

            $browser->visit('/de/gibt-es-nicht')
                ->assertSeeIn('h1', 'Seite nicht gefunden')
                ->assertScript('document.documentElement.lang', 'de')
                ->screenshot('error-404-de')
                ->clickLink('Zur Startseite')
                ->assertPathIs('/de');

            $browser->script('localStorage.clear()');
        });
    }
}
