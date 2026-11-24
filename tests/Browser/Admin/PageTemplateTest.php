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
 * The Template list of a page's form: the default one is first, the theme's
 * Full width is offered, and the page on the site is drawn with the one picked.
 */
final class PageTemplateTest extends AdminTestCase
{
    public function test_the_editor_picks_full_width_and_the_page_is_drawn_across_the_window(): void
    {
        $admin = $this->admin();
        $contacts = Page::query()->create(['name' => 'Contacts', 'code' => 'contacts']);

        $this->browse(function (Browser $browser) use ($admin, $contacts): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/pages/'.$contacts->id.'/edit')
                ->waitFor('#template')
                ->assertSelected('#template', 'default');

            $labels = $browser->script('return Array.from(document.querySelectorAll("#template option")).map(o => o.value + "=" + o.textContent.trim());')[0];
            $this->assertSame('default', explode('=', $labels[0])[0], 'the default template is the first');
            $this->assertContains('full-width=Full width', $labels);

            $browser->select('#template', 'full-width')
                ->press('[data-action="save"]')
                ->waitForLocation('/admin/pages');

            $this->assertSame('full-width', $contacts->fresh()->template);

            $browser->visit('/contacts')->assertPresent('main#content.wrap-full');
        });
    }
}
