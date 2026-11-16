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
 * "Subpage" on a page, in the list and on its form, opens a new page with
 * that page as its parent.
 */
final class SubpageTest extends AdminTestCase
{
    public function test_a_subpage_is_added_from_the_list_and_from_the_form(): void
    {
        $admin = $this->admin();
        $about = Page::query()->create(['name' => 'About us', 'code' => 'about']);

        $this->browse(function (Browser $browser) use ($admin, $about): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/pages')
                ->waitFor("[data-row-id=\"{$about->id}\"] [data-action=\"subpage\"]")
                ->click("[data-row-id=\"{$about->id}\"] [data-action=\"subpage\"]")
                ->waitForLocation('/admin/pages/create')
                ->waitFor('#name')
                ->type('#name', 'Team')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/pages');

            $team = Page::query()->where('code', 'team')->sole();
            $this->assertSame($about->id, $team->parent_id);

            $browser->visit("/admin/pages/{$about->id}/edit")
                ->waitFor('[data-action="subpage"]')
                ->click('[data-action="subpage"]')
                ->waitForLocation('/admin/pages/create')
                ->waitFor('#name')
                ->type('#name', 'History')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/pages');

            $this->assertSame($about->id, Page::query()->where('code', 'history')->sole()->parent_id);
        });
    }
}
