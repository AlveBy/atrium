<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\Language;
use Atrium\Pages\Models\Page;
use Laravel\Dusk\Browser;
use Tests\Browser\Admin\Concerns\EditsPageBlocks;

/**
 * Blocks from a page's form: a new page is built on it, a saved one in the
 * page editor, and the site draws the page from them.
 */
final class SiteBuilderTest extends AdminTestCase
{
    use EditsPageBlocks;

    private const SAVE = '[data-action="save"]';

    public function test_a_new_page_gets_its_blocks_on_the_form(): void
    {
        $admin = $this->admin();

        $this->browse(function (Browser $browser) use ($admin): void {
            $browser->loginAs($admin, 'admin')
                ->visit('/admin/pages/create')
                ->waitFor('@layout-editor')
                ->assertVisible('@layout-empty')
                // No preview and no editor of its own before the page is saved.
                ->assertMissing('@builder-preview')
                ->assertMissing('@layout-open-editor')
                ->type('#name', 'Camp')
                // The first block makes its section and column.
                ->click('@builder-add-button')
                ->waitFor('@layout-setting-label input')
                ->assertMissing('@layout-empty')
                ->type('@layout-setting-label input', 'Book a place')
                ->assertMissing('@layout-error')
                // No link.
                ->press(self::SAVE)
                ->waitFor('@layout-error')
                ->assertSeeIn('@layout-error', 'Section 1, column 1, block 1 (Button)')
                ->assertPathIs('/admin/pages/create')
                // What was typed is still there.
                ->assertValue('@layout-setting-label input', 'Book a place')
                ->click('@layout-setting-link [data-link-kind="url"]')
                ->waitFor('@layout-setting-link [data-link-url]')
                ->type('@layout-setting-link [data-link-url]', '/contacts')
                ->select('@layout-setting-style select', 'secondary')
                // The section is set up too.
                ->click('@layout-select-section-0')
                ->waitFor('@layout-setting-tone select')
                ->select('@layout-setting-tone select', 'tinted')
                ->screenshot('site-builder/form')
                ->press(self::SAVE)
                ->waitForLocation('/admin/pages');
        });

        $page = Page::query()->where('name->en', 'Camp')->firstOrFail();
        $section = $page->layout['sections'][0];
        $this->assertSame('tinted', $section['settings']['tone']);
        $this->assertSame('button', $section['columns'][0]['blocks'][0]['type']);
        $this->assertSame('secondary', $section['columns'][0]['blocks'][0]['settings']['style']);

        $this->browse(function (Browser $browser) use ($page): void {
            $browser->visit('/'.$page->code)
                ->assertSeeIn('a.b-button-secondary', 'Book a place')
                ->assertAttributeContains('a.b-button-secondary', 'href', '/contacts')
                ->assertPresent('section.b-tone-tinted');
        });
    }

    public function test_a_saved_page_opens_the_editor_and_its_form_leaves_the_blocks_alone(): void
    {
        $admin = $this->admin();
        $page = $this->pageOf([[[$this->textBlock('b1', 'As the form opened')], []]]);
        $newer = $this->layoutOf([[[$this->textBlock('b1', 'Published from the editor')]]]);

        $this->browse(function (Browser $browser) use ($admin, $page, $newer): void {
            $browser->loginAs($admin, 'admin')
                ->visit("/admin/pages/{$page->id}/edit")
                ->waitFor('@layout-summary')
                ->assertSeeIn('@layout-summary', '1 section, 1 block')
                ->assertMissing('@builder-outline')
                ->click('@layout-open-editor')
                ->waitForLocation("/admin/pages/{$page->id}/builder/layout")
                ->waitFor('@builder-outline')
                ->tap($this->toTop(...))->click('@builder-back')
                ->waitForLocation("/admin/pages/{$page->id}/edit")
                ->waitFor('#name');

            // Published elsewhere while the form is open: saving the form keeps it.
            $page->forceFill(['layout' => $newer])->save();

            $browser->clear('#name')
                ->type('#name', 'Spring camp')
                ->press(self::SAVE)
                ->waitForLocation('/admin/pages');
        });

        $this->assertSame('Spring camp', $page->fresh()->name);
        $this->assertEquals($newer, $page->fresh()->layout);
    }

    public function test_the_editor_builds_a_page_of_blocks_and_the_site_draws_it(): void
    {
        $admin = $this->admin();
        $page = Page::query()->create(['name' => 'Camp', 'code' => 'camp', 'content' => 'The old text']);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $text = '@layout-setting-html .ProseMirror';

            $browser->loginAs($admin, 'admin');
            $this->openEditor($browser, $page)
                ->assertVisible('@layout-empty')
                // A section comes with one column over the whole row.
                ->click('@layout-add-section')
                ->waitFor('@layout-column-0-0')
                ->assertMissing('@layout-empty')
                ->click('@layout-select-column-0-0')
                ->waitFor('@layout-setting-span input')
                ->assertValue('@layout-setting-span input', '12')
                // A second column: the two share the row.
                ->click('@layout-add-column-0')
                ->waitFor('@layout-column-0-1')
                ->assertValue('@layout-setting-span input', '6')
                ->click('@layout-select-column-0-0')
                ->assertValue('@layout-setting-span input', '6')

                // A block goes into the selected column.
                ->click('@builder-add-text')
                ->waitFor($text)
                ->click($text)
                ->keys($text, 'Welcome to the spring camp')
                ->click('@layout-select-column-0-1')
                ->click('@builder-add-button')
                ->waitFor('@layout-setting-label input')
                ->type('@layout-setting-label input', 'Book a place')
                ->click('@layout-setting-link [data-link-kind="url"]')
                ->waitFor('@layout-setting-link [data-link-url]')
                ->type('@layout-setting-link [data-link-url]', '/contacts')
                ->select('@layout-setting-style select', 'secondary')

                // A second, tinted section goes above the first one.
                ->click('@layout-add-section')
                ->waitFor('@layout-section-1')
                ->click('@layout-select-section-1')
                ->waitFor('@layout-setting-tone select')
                ->select('@layout-setting-tone select', 'tinted')
                ->assertDisabled('@layout-section-down-1')
                ->click('@layout-section-up-1')
                ->assertDisabled('@layout-section-up-0')
                ->click('@layout-select-section-0')
                ->assertSelected('@layout-setting-tone select', 'tinted')

                // A third one is added by mistake and deleted.
                ->click('@layout-add-section')
                ->waitFor('@layout-section-2')
                ->click('@layout-section-remove-2')
                ->waitUntilMissing('@layout-section-2');
            $this->drawn($browser)
                ->screenshot('site-builder/editor')
                ->tap($this->toTop(...))->click('@builder-publish');
            $this->editorIs($browser, 'published');

            $sections = $page->fresh()->layout['sections'];
            $this->assertSame('tinted', $sections[0]['settings']['tone']);
            $this->assertSame([], $sections[0]['columns'][0]['blocks']);

            [$left, $right] = $sections[1]['columns'];
            $this->assertSame([6, 6], [(int) $left['settings']['span'], (int) $right['settings']['span']]);
            $this->assertSame('<p>Welcome to the spring camp</p>', $left['blocks'][0]['settings']['html']['en']);
            $this->assertSame('Book a place', $right['blocks'][0]['settings']['label']['en']);
            $this->assertSame('/contacts', $right['blocks'][0]['settings']['link']['url']);

            $browser->visit('/camp')
                ->assertSeeIn('.b-column.b-span-6 .b-type-text', 'Welcome to the spring camp')
                ->assertSeeIn('a.b-button-secondary', 'Book a place')
                ->assertAttributeContains('a.b-button-secondary', 'href', '/contacts')
                ->assertDontSeeIn('main', 'The old text')
                ->screenshot('site-builder/site');

            // Opened again, the editor shows what was saved.
            $this->openEditor($browser, $page)
                ->click('@layout-block-toggle-1-1-0')
                ->waitFor('@layout-setting-label input')
                ->assertValue('@layout-setting-label input', 'Book a place')
                ->click('@layout-block-toggle-1-0-0')
                ->waitForTextIn($text, 'Welcome to the spring camp');
            $this->editorIs($browser, 'published');
        });
    }

    public function test_blocks_are_reordered_and_deleted_and_a_missing_type_is_named(): void
    {
        $admin = $this->admin();
        $page = $this->pageOf([[
            [['id' => 'b1', 'type' => 'carousel', 'settings' => ['speed' => 2]], $this->textBlock('b2', 'Kept text')],
            [],
        ]]);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $browser->loginAs($admin, 'admin');
            $this->openEditor($browser, $page)
                ->assertSeeIn('@layout-block-missing-0-0-0', 'Not shown on the page yet')
                ->click('@layout-block-toggle-0-0-0')
                ->waitFor('@layout-block-unknown')
                ->assertSeeIn('@layout-block-unknown', 'carousel')
                ->assertDisabled('@layout-block-up-0-0-0')
                ->assertDisabled('@layout-block-down-0-0-1')
                // The text goes above it.
                ->click('@layout-block-up-0-0-1')
                ->waitFor('[dusk="layout-block-0-0-1"][data-block-type="carousel"]')
                ->assertAttribute('@layout-block-0-0-0', 'data-block-type', 'text')
                // The empty column goes; the one left takes the row.
                ->click('@layout-column-remove-0-1')
                ->waitUntilMissing('@layout-column-0-1')
                ->click('@layout-select-column-0-0')
                ->waitFor('@layout-setting-span input')
                ->assertValue('@layout-setting-span input', '12')
                // A block that nobody can draw does not stop the publishing.
                ->tap($this->toTop(...))->click('@builder-publish');
            $this->editorIs($browser, 'published');

            [$column] = $page->fresh()->layout['sections'][0]['columns'];
            $this->assertSame(['b2', 'b1'], array_column($column['blocks'], 'id'));
            $this->assertSame(['speed' => 2], $column['blocks'][1]['settings']);

            $browser->visit('/camp')->assertSee('Kept text');

            $this->openEditor($browser, $page)
                ->click('@layout-block-remove-0-0-1')
                ->waitUntilMissing('@layout-block-0-0-1')
                ->tap($this->toTop(...))->click('@builder-publish');
            $this->editorIs($browser, 'published');
        });

        $this->assertSame(['b2'], array_column($page->fresh()->layout['sections'][0]['columns'][0]['blocks'], 'id'));
    }

    public function test_the_texts_of_a_block_are_written_in_each_language(): void
    {
        $admin = $this->admin();
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);
        $page = $this->pageOf([[[$this->buttonBlock('b1', 'Book a place')]]]);
        $page->update(['name' => ['en' => 'Camp', 'de' => 'Lager']]);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $label = '@layout-setting-label input';

            $browser->loginAs($admin, 'admin');
            $this->openEditor($browser, $page)
                ->click('@layout-block-toggle-0-0-0')
                ->waitFor($label)
                ->assertValue($label, 'Book a place');

            $this->language($browser, 'de');
            $browser->assertValue($label, '')
                ->type($label, 'Platz buchen');

            $this->language($browser, 'en');
            $browser->assertValue($label, 'Book a place')->tap($this->toTop(...))->click('@builder-publish');
            $this->editorIs($browser, 'published')
                ->visit('/camp')
                ->assertSeeIn('.b-button-link', 'Book a place')
                ->visit('/de/camp')
                ->assertSeeIn('.b-button-link', 'Platz buchen');
        });

        $this->assertSame(
            ['en' => 'Book a place', 'de' => 'Platz buchen'],
            $page->fresh()->layout['sections'][0]['columns'][0]['blocks'][0]['settings']['label'],
        );
    }

    /** The switch is at the top of the editor, under the panel's sticky header once scrolled. */
    private function language(Browser $browser, string $code): void
    {
        $browser->script('window.scrollTo(0, 0);');
        $browser->click('[data-content-locale="'.$code.'"]')->waitFor('[data-content-locale="'.$code.'"][aria-pressed="true"]');
    }
}
