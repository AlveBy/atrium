<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Pages\Models\Page;
use Laravel\Dusk\Browser;

/**
 * The filters above a list: the pages' "Active", chosen with the keyboard,
 * kept in the address and reset with one button.
 */
final class ListFiltersTest extends AdminTestCase
{
    public function test_the_list_is_narrowed_by_a_filter_and_reset(): void
    {
        $admin = $this->admin();
        $about = Page::query()->create(['name' => 'About us', 'code' => 'about', 'active' => true]);
        $draft = Page::query()->create(['name' => 'Draft', 'code' => 'draft', 'active' => false]);

        $this->browse(function (Browser $browser) use ($admin, $about, $draft): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/pages')
                ->waitFor('[data-filters] [data-filter="active"]')
                ->assertSeeIn('[data-filters] label[for="filter-active"]', 'Active')
                ->assertSelected('[data-filter="active"]', '')
                ->assertMissing('[data-filter="active"][data-active]')
                ->assertMissing('[data-filters-reset]')
                ->assertPresent("[data-row-id=\"{$about->id}\"]")
                ->assertPresent("[data-row-id=\"{$draft->id}\"]")
                ->select('[data-filter="active"]', '0')
                ->waitUntilMissing("[data-row-id=\"{$about->id}\"]")
                ->assertPresent("[data-row-id=\"{$draft->id}\"]")
                ->assertQueryStringHas('active', '0')
                // Chosen: in bold, marked, and counted on the reset button.
                ->assertPresent('[data-filter="active"][data-active]')
                ->assertSeeIn('[data-filters-reset]', 'Reset filters (1)');

            // The address keeps the filter: a reload draws it chosen.
            $browser->refresh()
                ->waitFor('[data-filter="active"][data-active]')
                ->assertSelected('[data-filter="active"]', '0')
                ->assertMissing("[data-row-id=\"{$about->id}\"]")
                // The keyboard reaches the reset button and presses it.
                ->keys('[data-filters-reset]', '{enter}')
                ->waitFor("[data-row-id=\"{$about->id}\"]")
                ->assertPresent("[data-row-id=\"{$draft->id}\"]")
                ->assertSelected('[data-filter="active"]', '')
                ->assertQueryStringMissing('active')
                ->assertMissing('[data-filters-reset]');
        });
    }
}
