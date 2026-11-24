<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Forms\Models\Form;
use Atrium\News\Models\News;
use Laravel\Dusk\Browser;

/**
 * The "Insert form or block" button in the visual editor: pick a type, pick an
 * item (searching by name), and the editor holds a chip that is saved as
 * <div data-atrium-embed="form" data-id="…"></div>, at the caret and not at the
 * start of the field.
 */
final class EmbedsTest extends AdminTestCase
{
    private const EDITOR = '[data-content-field="detail_text"] .ProseMirror';

    // The news form has two visual editors, each with its own button and menu.
    private const BUTTON = '[data-content-field="detail_text"] [data-editor-button="embed"]';

    private const MENU = '[data-content-field="detail_text"] [data-embed-menu]';

    public function test_a_form_is_inserted_at_the_caret_and_saved_as_a_placeholder(): void
    {
        $admin = $this->admin();
        $form = Form::query()->create(['name' => 'Contact us', 'code' => 'contact']);
        Form::query()->create(['name' => 'Quiet form', 'code' => 'quiet']);
        $news = News::query()->create(['name' => 'Spring', 'code' => 'spring', 'active' => true, 'sort' => 500, 'detail_text' => '<p>First</p><p>Second</p>']);

        $this->browse(function (Browser $browser) use ($admin, $news, $form): void {
            $browser->loginAs($admin, 'admin')->visit('/admin');
            $browser->script("localStorage.removeItem('content-field-mode-detail_text');");

            $browser->visit("/admin/news/{$news->id}/edit")
                ->waitFor(self::EDITOR)
                // The caret goes to the end of the first paragraph.
                ->click(self::EDITOR.' p:first-child')
                ->keys(self::EDITOR, ['{end}'])
                ->click(self::BUTTON)
                ->waitFor(self::MENU)
                ->assertPresent('[data-embed-type="form"]')
                ->click('[data-embed-type="form"]')
                ->waitFor('[data-embed-item="'.$form->id.'"]')
                ->assertSeeIn(self::MENU, 'Quiet form')
                // Searching narrows the list.
                ->type('[data-embed-search]', 'Contact')
                ->waitUntilMissing('[data-embed-item="'.($form->id + 1).'"]')
                ->assertSeeIn(self::MENU, 'Contact us')
                ->click('[data-embed-item="'.$form->id.'"]')
                ->waitUntilMissing(self::MENU)
                ->waitFor(self::EDITOR.' .atrium-embed')
                ->assertSeeIn(self::EDITOR.' .atrium-embed', 'Contact us');

            // Between the paragraphs, not before them.
            $order = $browser->script("return Array.from(document.querySelector('".self::EDITOR."').children).map(e => e.classList.contains('atrium-embed') ? 'embed' : e.tagName.toLowerCase()).join(',')");
            $this->assertSame('p,embed,p', $order[0]);

            $browser->screenshot('embeds/inserted')
                ->press('Save')
                ->waitForLocation('/admin/news');
        });

        $this->assertSame(
            '<p>First</p><div data-atrium-embed="form" data-id="'.$form->id.'"></div><p>Second</p>',
            $news->fresh()->detail_text,
        );

        // Opened again, the placeholder is still a chip and survives another save.
        $this->browse(function (Browser $browser) use ($news, $form): void {
            $browser->visit("/admin/news/{$news->id}/edit")
                ->waitFor(self::EDITOR.' .atrium-embed')
                ->assertSeeIn(self::EDITOR.' .atrium-embed', 'Contact us')
                ->press('Save')
                ->waitForLocation('/admin/news');

            $this->assertStringContainsString('data-id="'.$form->id.'"', $news->fresh()->detail_text);
        });
    }

    public function test_the_menu_closes_on_escape_and_inserts_nothing(): void
    {
        $admin = $this->admin();
        Form::query()->create(['name' => 'Contact us', 'code' => 'contact']);
        $news = News::query()->create(['name' => 'Spring', 'code' => 'spring', 'active' => true, 'sort' => 500, 'detail_text' => '<p>First</p>']);

        $this->browse(function (Browser $browser) use ($admin, $news): void {
            $browser->loginAs($admin, 'admin')->visit('/admin');
            $browser->script("localStorage.removeItem('content-field-mode-detail_text');");

            $browser->visit("/admin/news/{$news->id}/edit")
                ->waitFor(self::EDITOR)
                ->click(self::BUTTON)
                ->waitFor(self::MENU)
                ->keys(self::BUTTON, ['{escape}'])
                ->waitUntilMissing(self::MENU)
                ->assertMissing(self::EDITOR.' .atrium-embed');
        });
    }
}
