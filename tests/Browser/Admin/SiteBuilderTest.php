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

/**
 * The block editor on a page's form: sections, columns and blocks are added,
 * ordered and deleted, the blocks are filled in, and the site draws the page
 * from them.
 */
final class SiteBuilderTest extends AdminTestCase
{
    private const SAVE = '[data-action="save"]';

    public function test_the_editor_builds_a_page_of_blocks_and_the_site_draws_it(): void
    {
        $admin = $this->admin();
        $page = Page::query()->create(['name' => 'Camp', 'code' => 'camp', 'content' => 'The old text']);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $text = '@layout-setting-0-0-0-html .ProseMirror';

            $browser->loginAs($admin, 'admin')
                ->visit("/admin/pages/{$page->id}/edit")
                ->waitFor('@layout-editor')
                ->assertVisible('@layout-empty')

                // A section comes with one column over the whole row.
                ->click('@layout-add-section')
                ->waitFor('@layout-column-0-0')
                ->assertMissing('@layout-empty')
                ->assertValue('@layout-column-setting-0-0-span input', '12')
                // A second column: the two share the row.
                ->click('@layout-add-column-0')
                ->waitFor('@layout-column-0-1')
                ->assertValue('@layout-column-setting-0-0-span input', '6')
                ->assertValue('@layout-column-setting-0-1-span input', '6')

                // Nothing to add until a type is chosen.
                ->assertDisabled('@layout-add-block-0-0')
                ->select('@layout-block-type-0-0', 'text')
                ->click('@layout-add-block-0-0')
                ->waitFor($text)
                ->click($text)
                ->keys($text, 'Welcome to the spring camp')

                ->select('@layout-block-type-0-1', 'button')
                ->click('@layout-add-block-0-1')
                ->waitFor('@layout-block-settings-0-1-0')
                ->type('@layout-setting-0-1-0-label input', 'Book a place')
                ->click('@layout-setting-0-1-0-link [data-link-kind="url"]')
                ->waitFor('@layout-setting-0-1-0-link [data-link-url]')
                ->type('@layout-setting-0-1-0-link [data-link-url]', '/contacts')
                ->select('@layout-setting-0-1-0-style select', 'secondary')

                // A second, tinted section goes above the first one.
                ->click('@layout-add-section')
                ->waitFor('@layout-section-1')
                ->select('@layout-section-setting-1-tone select', 'tinted')
                ->assertDisabled('@layout-section-down-1')
                ->click('@layout-section-up-1')
                ->waitFor('@layout-section-0 @layout-section-setting-0-tone')
                ->assertSelected('@layout-section-setting-0-tone select', 'tinted')
                ->assertSelected('@layout-section-setting-1-tone select', 'plain')
                ->assertDisabled('@layout-section-up-0')

                // A third one is added by mistake and deleted.
                ->click('@layout-add-section')
                ->waitFor('@layout-section-2')
                ->click('@layout-section-remove-2')
                ->waitUntilMissing('@layout-section-2')

                ->screenshot('site-builder/editor')
                ->press(self::SAVE)
                ->waitForLocation('/admin/pages');

            $sections = $page->fresh()->layout['sections'];
            $this->assertSame(['tinted', 'plain'], array_column(array_column($sections, 'settings'), 'tone'));
            $this->assertSame([], $sections[0]['columns'][0]['blocks']);

            [$left, $right] = $sections[1]['columns'];
            $this->assertSame([6, 6], [(int) $left['settings']['span'], (int) $right['settings']['span']]);
            $this->assertSame('text', $left['blocks'][0]['type']);
            $this->assertSame('<p>Welcome to the spring camp</p>', $left['blocks'][0]['settings']['html']['en']);
            $this->assertSame('button', $right['blocks'][0]['type']);
            $this->assertSame('Book a place', $right['blocks'][0]['settings']['label']['en']);
            $this->assertSame('/contacts', $right['blocks'][0]['settings']['link']['url']);

            $browser->visit('/camp')
                ->assertSeeIn('.b-column.b-span-6 .b-type-text', 'Welcome to the spring camp')
                ->assertSeeIn('a.b-button-secondary', 'Book a place')
                ->assertAttributeContains('a.b-button-secondary', 'href', '/contacts')
                ->assertDontSeeIn('main', 'The old text')
                ->screenshot('site-builder/site');

            // Opened again, the form shows what was saved.
            $browser->visit("/admin/pages/{$page->id}/edit")
                ->waitFor('@layout-section-1')
                ->click('@layout-block-toggle-1-1-0')
                ->waitFor('@layout-block-settings-1-1-0')
                ->assertValue('@layout-setting-1-1-0-label input', 'Book a place')
                ->click('@layout-block-toggle-1-0-0')
                ->waitFor('@layout-setting-1-0-0-html .ProseMirror')
                ->assertSeeIn('@layout-setting-1-0-0-html .ProseMirror', 'Welcome to the spring camp');
        });
    }

    public function test_a_block_that_is_not_filled_is_not_saved_and_the_form_says_where(): void
    {
        $admin = $this->admin();
        $page = Page::query()->create(['name' => 'Camp', 'code' => 'camp']);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $browser->loginAs($admin, 'admin')
                ->visit("/admin/pages/{$page->id}/edit")
                ->waitFor('@layout-editor')
                ->click('@layout-add-section')
                ->waitFor('@layout-column-0-0')
                ->select('@layout-block-type-0-0', 'button')
                ->click('@layout-add-block-0-0')
                ->waitFor('@layout-block-settings-0-0-0')
                ->type('@layout-setting-0-0-0-label input', 'Book a place')
                ->assertMissing('@layout-error')
                // No link.
                ->press(self::SAVE)
                ->waitFor('@layout-error')
                ->assertSeeIn('@layout-error', 'Section 1, column 1, block 1 (Button)')
                ->assertPathIs("/admin/pages/{$page->id}/edit")
                // What was typed is still there.
                ->assertValue('@layout-setting-0-0-0-label input', 'Book a place');
        });

        $this->assertNull($page->fresh()->layout);
    }

    public function test_blocks_are_reordered_and_deleted_and_a_missing_type_is_named(): void
    {
        $admin = $this->admin();
        $page = Page::query()->create(['name' => 'Camp', 'code' => 'camp', 'layout' => ['version' => 1, 'sections' => [[
            'id' => 's1',
            'settings' => [],
            'columns' => [
                ['id' => 'c1', 'settings' => ['span' => 6], 'blocks' => [
                    ['id' => 'b1', 'type' => 'carousel', 'settings' => ['speed' => 2]],
                    ['id' => 'b2', 'type' => 'text', 'settings' => ['html' => ['en' => '<p>Kept text</p>']]],
                ]],
                ['id' => 'c2', 'settings' => ['span' => 6], 'blocks' => []],
            ],
        ]]]]);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $browser->loginAs($admin, 'admin')
                ->visit("/admin/pages/{$page->id}/edit")
                ->waitFor('@layout-block-unknown-0-0-0')
                ->assertSeeIn('@layout-block-unknown-0-0-0', 'carousel')
                ->assertDisabled('@layout-block-up-0-0-0')
                ->assertDisabled('@layout-block-down-0-0-1')
                // The text goes above it.
                ->click('@layout-block-up-0-0-1')
                ->waitFor('@layout-block-unknown-0-0-1')
                ->assertAttribute('@layout-block-0-0-0', 'data-block-type', 'text')
                // The empty column goes; the one left takes the row.
                ->click('@layout-column-remove-0-1')
                ->waitUntilMissing('@layout-column-0-1')
                ->assertValue('@layout-column-setting-0-0-span input', '12')
                // A block that nobody can draw does not stop the saving.
                ->press(self::SAVE)
                ->waitForLocation('/admin/pages');

            [$column] = $page->fresh()->layout['sections'][0]['columns'];
            $this->assertSame(['b2', 'b1'], array_column($column['blocks'], 'id'));
            $this->assertSame(['speed' => 2], $column['blocks'][1]['settings']);

            $browser->visit('/camp')->assertSee('Kept text');

            $browser->visit("/admin/pages/{$page->id}/edit")
                ->waitFor('@layout-block-0-0-1')
                ->click('@layout-block-remove-0-0-1')
                ->waitUntilMissing('@layout-block-0-0-1')
                ->press(self::SAVE)
                ->waitForLocation('/admin/pages');
        });

        $this->assertSame(['b2'], array_column($page->fresh()->layout['sections'][0]['columns'][0]['blocks'], 'id'));
    }

    public function test_the_texts_of_a_block_are_written_in_each_language(): void
    {
        $admin = $this->admin();
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);
        $page = Page::query()->create(['name' => ['en' => 'Camp', 'de' => 'Lager'], 'code' => 'camp', 'layout' => ['version' => 1, 'sections' => [[
            'id' => 's1',
            'settings' => [],
            'columns' => [['id' => 'c1', 'settings' => [], 'blocks' => [
                ['id' => 'b1', 'type' => 'button', 'settings' => ['label' => ['en' => 'Book a place'], 'link' => ['type' => 'url', 'url' => '/contacts']]],
            ]]],
        ]]]]);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $label = '@layout-setting-0-0-0-label input';

            $browser->loginAs($admin, 'admin')
                ->visit("/admin/pages/{$page->id}/edit")
                ->waitFor('@layout-block-toggle-0-0-0')
                ->assertSeeIn('@layout-language', 'English')
                ->click('@layout-block-toggle-0-0-0')
                ->waitFor($label)
                ->assertValue($label, 'Book a place');

            $this->language($browser, 'de', 'Deutsch');
            $browser->assertValue($label, '')
                ->type($label, 'Platz buchen');

            $this->language($browser, 'en', 'English');
            $browser
                ->assertValue($label, 'Book a place')
                ->press(self::SAVE)
                ->waitForLocation('/admin/pages')
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

    /** The switch is at the top of the form, under the panel's sticky header once scrolled. */
    private function language(Browser $browser, string $code, string $name): void
    {
        $browser->script('window.scrollTo(0, 0);');
        $browser->click('[data-content-locale="'.$code.'"]')->waitForTextIn('@layout-language', $name);
    }
}
