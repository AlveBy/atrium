<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Menus\Models\Menu;
use Atrium\Menus\Models\MenuItem;
use Atrium\Pages\Models\Page;
use Laravel\Dusk\Browser;

/**
 * Buttons that modules add: "Add to header menu" from the menus module on
 * a page, and actions over the pages ticked in the list.
 */
final class RecordActionsTest extends AdminTestCase
{
    public function test_a_button_of_another_module_runs_from_the_list_and_from_the_form(): void
    {
        $admin = $this->admin();
        $about = Page::query()->create(['name' => 'About us', 'code' => 'about']);
        Menu::query()->create(['code' => 'header', 'name' => 'Header', 'active' => true]);

        $this->browse(function (Browser $browser) use ($admin, $about): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/pages')
                ->waitFor("[data-row-id=\"{$about->id}\"] [data-action=\"add-to-header-menu\"]")
                ->click("[data-row-id=\"{$about->id}\"] [data-action=\"add-to-header-menu\"]")
                ->waitFor('[data-flash="success"]')
                ->assertSeeIn('[data-flash="success"]', '“About us” is added to the end of the header menu.')
                ->assertPathIs('/admin/pages');

            $this->assertSame(['About us'], MenuItem::query()->pluck('title')->all());

            $browser->visit("/admin/pages/{$about->id}/edit")
                ->waitFor('[data-action="add-to-header-menu"]')
                ->click('[data-action="add-to-header-menu"]')
                ->waitFor('[data-flash="error"]')
                ->assertSeeIn('[data-flash="error"]', '“About us” is already in the header menu.')
                ->assertPathIs("/admin/pages/{$about->id}/edit");

            $this->assertSame(1, MenuItem::query()->count());
        });
    }

    public function test_ticked_pages_are_hidden_then_deleted_after_a_question(): void
    {
        $admin = $this->admin();
        [$about, $contacts, $news] = array_map(
            fn (string $code) => Page::query()->create(['name' => ucfirst($code), 'code' => $code, 'active' => true]),
            ['about', 'contacts', 'news'],
        );

        $this->browse(function (Browser $browser) use ($admin, $about, $contacts, $news): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/pages')
                ->waitFor("[data-row-id=\"{$about->id}\"] [data-select=\"row\"]")
                ->assertMissing('[data-bulk-bar]')
                ->click("[data-row-id=\"{$about->id}\"] [data-select=\"row\"]")
                // The keyboard ticks a row too.
                ->keys("[data-row-id=\"{$contacts->id}\"] [data-select=\"row\"]", ' ')
                ->waitFor('[data-bulk-bar]')
                ->assertSeeIn('[data-bulk-count]', '2 selected')
                ->assertPresent("[data-row-id=\"{$about->id}\"][data-selected]")
                ->assertMissing("[data-row-id=\"{$news->id}\"][data-selected]")
                ->click('[data-bulk-action="hide"]')
                ->waitFor('[data-flash="success"]')
                ->assertSeeIn('[data-flash="success"]', '2 pages are hidden from the site.');

            $this->assertSame([false, false, true], [$about->fresh()->active, $contacts->fresh()->active, $news->fresh()->active]);

            $browser->click('[data-select="page"]')
                ->waitFor('[data-bulk-bar]')
                ->assertSeeIn('[data-bulk-count]', '3 selected')
                ->click('[data-bulk-action="delete"]')
                ->waitFor('[data-action-question]')
                ->assertSeeIn('[data-action-question]', 'Delete the selected records? This cannot be undone.')
                ->click('[data-action="action-cancel"]')
                ->waitUntilMissing('[data-action-question]');

            $this->assertSame(3, Page::query()->count(), 'Cancelled: nothing is deleted.');

            $browser->click('[data-bulk-action="delete"]')
                ->waitFor('[data-action="action-confirm"]')
                ->click('[data-action="action-confirm"]')
                ->waitFor('[data-flash="success"]')
                ->assertSeeIn('[data-flash="success"]', '3 records deleted.')
                ->assertMissing('[data-bulk-bar]');

            $this->assertSame(0, Page::query()->count());
        });
    }
}
