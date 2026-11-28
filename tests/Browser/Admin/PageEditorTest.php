<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Builder\Layout;
use Atrium\Admin\History\Revisions;
use Atrium\Admin\Models\Language;
use Atrium\Pages\Models\Page;
use Facebook\WebDriver\WebDriverKeys;
use Laravel\Dusk\Browser;
use Tests\Browser\Admin\Concerns\EditsPageBlocks;

/**
 * The page editor: blocks are dragged and moved from the keyboard, the theme
 * draws a preview, and a page is kept as a draft, published and taken back
 * to an earlier version.
 */
final class PageEditorTest extends AdminTestCase
{
    use EditsPageBlocks;

    public function test_blocks_are_dragged_between_columns_and_sections_and_from_the_list(): void
    {
        $admin = $this->admin();
        $page = $this->pageOf([
            [[$this->textBlock('b1', 'One'), $this->textBlock('b2', 'Two')], []],
            [[$this->textBlock('b3', 'Three')]],
        ]);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $block = fn (string $id): string => '[data-node-block="'.$id.'"] [data-control="select:'.$id.'"]';

            $browser->loginAs($admin, 'admin');
            $this->openEditor($browser, $page);
            $types = count($browser->elements('[dusk="builder-palette"] [data-palette]'));
            $browser->assertSeeIn('@layout-drop-0-1', 'Drag a block here');

            // Dropped where it was: nothing changed.
            $this->drag($browser, $block('b1'), $block('b1'));
            $this->editorIs($browser, 'published');
            $this->assertSame(['b1', 'b2'], $this->order($browser, 0, 0));

            // Into the empty column.
            $this->drag($browser, $block('b2'), '[dusk="layout-drop-0-1"]');
            $this->said($browser, 'Moved, now at: Section 1, column 2, block 1 (Text)');
            $this->assertSame(['b1'], $this->order($browser, 0, 0));
            $this->assertSame(['b2'], $this->order($browser, 0, 1));
            $browser->assertMissing('@layout-drop-0-1');
            $this->editorIs($browser, 'unsaved');

            // To another section, then above the block that is there.
            $this->drag($browser, $block('b1'), $block('b3'), 8);
            $this->said($browser, 'Moved, now at: Section 2, column 1, block 2 (Text)');
            $this->assertSame([], $this->order($browser, 0, 0));
            $this->assertSame(['b3', 'b1'], $this->order($browser, 1, 0));
            $this->drag($browser, $block('b1'), $block('b3'), -8);
            $this->said($browser, 'Moved, now at: Section 2, column 1, block 1 (Text)');
            $this->assertSame(['b1', 'b3'], $this->order($browser, 1, 0));

            // A new block from the list, into the column left empty.
            $this->drag($browser, '[dusk="builder-add-button"]', '[dusk="layout-drop-0-0"]');
            $this->said($browser, 'Added: Section 1, column 1, block 1 (Button)');
            $browser->assertAttribute('@layout-block-0-0-0', 'data-block-type', 'button')
                ->assertSeeIn('@layout-panel-title', 'Section 1, column 1, block 1 (Button)');
            // The list of types keeps all of them, each once.
            $this->assertCount($types, $browser->elements('[dusk="builder-palette"] [data-palette]'));
            $this->assertCount(4, $browser->elements('[dusk="builder-outline"] [data-node-block]'));

            $browser->type('@layout-setting-label input', 'Go')
                ->click('@layout-setting-link [data-link-kind="url"]')
                ->waitFor('@layout-setting-link [data-link-url]')
                ->type('@layout-setting-link [data-link-url]', '/go')
                ->screenshot('site-builder/dragged')
                ->tap($this->toTop(...))->click('@builder-publish');
            $this->editorIs($browser, 'published')->assertSeeIn('@builder-notice', 'Published.');
        });

        $sections = $page->fresh()->layout['sections'];
        $this->assertSame(['button'], array_column($sections[0]['columns'][0]['blocks'], 'type'));
        $this->assertSame(['b2'], array_column($sections[0]['columns'][1]['blocks'], 'id'));
        $this->assertSame(['b1', 'b3'], array_column($sections[1]['columns'][0]['blocks'], 'id'));
    }

    public function test_everything_is_done_from_the_keyboard_and_said_aloud(): void
    {
        $admin = $this->admin();
        $page = $this->pageOf([[[$this->textBlock('b1', 'One'), $this->textBlock('b2', 'Two')], []]]);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $browser->loginAs($admin, 'admin');
            $this->openEditor($browser, $page);

            $focused = fn (): ?string => $browser->script('return document.activeElement.getAttribute("data-control") || document.activeElement.getAttribute("dusk");')[0];
            $press = fn (string ...$keys) => $browser->driver->getKeyboard()->sendKeys($keys);
            // Tab from the first block of the list down to a control.
            $tabTo = function (string $control) use ($browser, $focused, $press): void {
                $browser->script('document.querySelector(\'[dusk="builder-add-text"]\').focus();');

                for ($step = 0; $step < 80 && $focused() !== $control; $step++) {
                    $press(WebDriverKeys::TAB);
                }

                $this->assertSame($control, $focused(), 'Tab does not reach '.$control);
            };

            // The second block goes up: the keyboard stays on it.
            $tabTo('up:b2');
            $press(WebDriverKeys::ENTER);
            $this->said($browser, 'Moved, now at: Section 1, column 1, block 1 (Text)');
            $this->assertSame(['b2', 'b1'], $this->order($browser, 0, 0));
            // It is first now, so its other arrow takes the keyboard.
            $this->assertSame('down:b2', $focused());
            $press(WebDriverKeys::SPACE);
            $this->said($browser, 'Moved, now at: Section 1, column 1, block 2 (Text)');
            $this->assertSame(['b1', 'b2'], $this->order($browser, 0, 0));
            $this->assertSame('up:b2', $focused());

            // Selected with Enter, moved to the other column with its list.
            $tabTo('select:b2');
            $press(WebDriverKeys::ENTER);
            $browser->waitForTextIn('@layout-panel-title', 'Section 1, column 1, block 2 (Text)')
                ->assertAttribute('@layout-block-toggle-0-0-1', 'aria-current', 'true')
                ->assertSeeIn('@layout-block-toggle-0-0-1', '▶')
                ->assertDisabled('@layout-move')
                // The list of columns takes what is typed.
                ->keys('@layout-move-to', 'Section 1, column 2')
                ->assertSelected('@layout-move-to', 'c2')
                ->assertEnabled('@layout-move');
            $press(WebDriverKeys::TAB);
            $this->assertSame('layout-move', $focused());
            $press(WebDriverKeys::ENTER);
            $this->said($browser, 'Moved, now at: Section 1, column 2, block 1 (Text)');
            $this->assertSame(['b2'], $this->order($browser, 0, 1));
            $browser->waitUntil('document.activeElement.getAttribute("data-control") === "select:b2"');

            // Deleted: the keyboard goes to the neighbour, or else to the column.
            $tabTo('remove:b1');
            $press(WebDriverKeys::ENTER);
            $this->said($browser, 'Removed: Section 1, column 1, block 1 (Text)');
            $browser->waitUntil('document.activeElement.getAttribute("data-control") === "select:c1"');

            // A block, a column and a section are added.
            $browser->keys('@builder-add-button', '{enter}');
            $this->said($browser, 'Added: Section 1, column 2, block 2 (Button)');
            $browser->keys('@layout-add-column-0', '{enter}');
            $this->said($browser, 'Added: Section 1, column 3');
            $browser->keys('@layout-add-section', '{enter}');
            $this->said($browser, 'Added: Section 2');
            $new = $browser->attribute('@layout-section-1', 'data-node');
            $tabTo("up:{$new}");
            $press(WebDriverKeys::ENTER);
            $this->said($browser, 'Moved, now at: Section 1');
            $this->assertSame("down:{$new}", $focused());
            $tabTo("remove:{$new}");
            $press(WebDriverKeys::ENTER);
            $this->said($browser, 'Removed: Section 1');
            $browser->waitUntil('document.activeElement.getAttribute("data-control") === "select:s1"')
                ->assertMissing('@layout-section-1');
        });

        $this->assertSame(['b1', 'b2'], array_column($page->fresh()->layout['sections'][0]['columns'][0]['blocks'], 'id'), 'Nothing was saved');
    }

    public function test_the_preview_is_drawn_by_the_site_and_a_click_in_it_selects(): void
    {
        $admin = $this->admin();
        Language::query()->create(['code' => 'de', 'name' => 'Deutsch']);
        $page = $this->pageOf([[
            [
                $this->textBlock('b1', 'First text<img src="/nowhere.png" onerror="document.body.setAttribute(\'data-ran\', \'yes\')">'),
                $this->buttonBlock('b2', 'Unfinished', null),
                ['id' => 'b3', 'type' => 'button', 'settings' => ['label' => ['en' => 'Book', 'de' => 'Buchen'], 'link' => ['type' => 'url', 'url' => '/elsewhere']]],
            ],
        ]]);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $browser->loginAs($admin, 'admin');
            $this->openEditor($browser, $page);

            // A block with nothing to show is named in the structure, in words.
            $browser->assertSeeIn('@layout-block-missing-0-0-1', 'Not shown on the page yet')
                ->assertMissing('@layout-block-missing-0-0-0')
                ->assertMissing('@layout-block-missing-0-0-2');

            $browser->withinFrame('@builder-frame', function (Browser $frame): void {
                $frame->assertSee('First text')
                    ->assertSee('Book')
                    ->assertPresent('.atrium-layout .b-type-text')
                    ->click('[data-block-id="b1"]');
            });
            $browser->waitForTextIn('@layout-panel-title', 'Section 1, column 1, block 1 (Text)')
                ->assertAttribute('@layout-block-toggle-0-0-0', 'aria-current', 'true');
            $browser->withinFrame('@builder-frame', function (Browser $frame): void {
                $frame->waitFor('[data-block-id="b1"][data-builder-selected="Selected: Section 1, column 1, block 1 (Text)"]')
                    // The page's own scripts do not run in the editor.
                    ->assertMissing('body[data-ran]')
                    // A link selects its block and leads nowhere.
                    ->click('[data-block-id="b3"] a');
            });
            $browser->waitForTextIn('@layout-panel-title', 'block 3 (Button)')
                ->assertPathIs("/admin/pages/{$page->id}/builder/layout");
            $browser->withinFrame('@builder-frame', function (Browser $frame): void {
                $frame->assertSee('First text')
                    ->assertPresent('[data-block-id="b3"][data-builder-selected]')
                    ->assertMissing('[data-block-id="b1"][data-builder-selected]');
            });

            // Picked in the structure, marked in the preview.
            $browser->click('@layout-select-section-0');
            $browser->withinFrame('@builder-frame', fn (Browser $frame) => $frame->waitFor('[data-section-id="s1"][data-builder-selected="Selected: Section 1"]'));

            // What is typed shows without saving.
            $browser->click('@layout-block-toggle-0-0-2')
                ->waitFor('@layout-setting-label input')
                ->clear('@layout-setting-label input')
                ->type('@layout-setting-label input', 'Reserve');
            $this->drawn($browser);
            $browser->withinFrame('@builder-frame', fn (Browser $frame) => $frame->waitForText('Reserve'))
                ->screenshot('site-builder/preview');

            // In the language of the switch.
            $browser->script('window.scrollTo(0, 0);');
            $browser->click('[data-content-locale="de"]')
                ->assertValue('@layout-setting-label input', 'Buchen')
                ->clear('@layout-setting-label input')
                ->type('@layout-setting-label input', 'Reservieren');
            $this->drawn($browser);
            $browser->withinFrame('@builder-frame', fn (Browser $frame) => $frame->waitForText('Reservieren')->assertDontSee('Reserve '));

            // Nothing was saved by any of it.
            $this->assertSame('Book', $page->fresh()->layout['sections'][0]['columns'][0]['blocks'][2]['settings']['label']['en']);

            // The page is gone meanwhile: the editor says so, in words.
            Page::query()->whereKey($page->id)->delete();
            $browser->click('@layout-add-section')
                ->waitFor('@builder-preview-error', 10)
                ->assertSeeIn('@builder-preview-error', '⚠')
                ->tap($this->toTop(...))->click('@builder-save-draft')
                ->waitFor('@builder-problem')
                ->assertSeeIn('@builder-problem', '⚠');
            $this->editorIs($browser, 'unsaved');
        });
    }

    public function test_a_page_is_kept_as_a_draft_published_and_taken_back(): void
    {
        $admin = $this->admin();
        $page = $this->pageOf([[[$this->textBlock('b1', 'Published text')]]]);
        $stored = fn (): string => $page->fresh()->layout['sections'][0]['columns'][0]['blocks'][0]['settings']['html']['en'];

        $this->browse(function (Browser $browser) use ($admin, $page, $stored): void {
            $text = '@layout-setting-html .ProseMirror';
            $write = function (string $words) use ($browser, $text): void {
                $browser->click('@layout-block-toggle-0-0-0')->waitFor($text)->click($text);
                $browser->script('document.querySelector(\'[dusk="layout-setting-html"] .ProseMirror\').editor.commands.setContent('.json_encode("<p>{$words}</p>").', { emitUpdate: true });');
                $this->editorIs($browser, 'unsaved');
            };

            $browser->loginAs($admin, 'admin');
            $this->openEditor($browser, $page);
            $this->editorIs($browser, 'published')
                ->assertSeeIn('@builder-status', '✓ Published')
                ->assertDisabled('@builder-publish')
                ->assertDisabled('@builder-save-draft')
                ->assertMissing('@builder-discard')
                ->assertMissing('@builder-versions');

            // A draft is kept, the site stays.
            $write('Draft text');
            $browser->assertSeeIn('@builder-status', '● Unsaved changes')->tap($this->toTop(...))->click('@builder-save-draft');
            $this->editorIs($browser, 'draft')
                ->assertSeeIn('@builder-status', '◐ Draft saved on')
                ->assertSeeIn('@builder-status', 'by Ada Admin, not published')
                ->assertSeeIn('@builder-notice', 'The draft is saved.')
                ->assertDisabled('@builder-save-draft')
                ->assertEnabled('@builder-publish');
            $this->assertSame('<p>Published text</p>', $stored());
            $browser->visit('/camp')->assertSee('Published text')->assertDontSee('Draft text');

            // The editor opens with the draft; discarding it asks first.
            $this->openEditor($browser, $page);
            $this->editorIs($browser, 'draft');
            $browser->click('@layout-block-toggle-0-0-0')->waitForTextIn($text, 'Draft text')
                ->tap($this->toTop(...))->click('@builder-discard')
                ->waitFor('@builder-discard-confirm')
                ->press('Cancel')
                ->waitUntilMissing('@builder-discard-confirm')
                ->assertSeeIn($text, 'Draft text')
                ->tap($this->toTop(...))->click('@builder-discard')
                ->waitFor('@builder-discard-confirm')
                ->click('@builder-discard-confirm');
            $this->editorIs($browser, 'published')
                ->waitForTextIn($text, 'Published text')
                ->assertSeeIn('@builder-notice', 'The changes are discarded.');
            $this->assertNull(app(Revisions::class)->draft($page, 'layout'));

            // Published twice.
            $write('Second text');
            $browser->tap($this->toTop(...))->click('@builder-publish');
            // The editor goes on showing what it has just published.
            $this->editorIs($browser, 'published')
                ->assertSeeIn('@builder-notice', 'Published.')
                ->assertSeeIn($text, 'Second text')
                ->assertDisabled('@builder-publish');
            $this->assertSame('<p>Second text</p>', $stored());
            $write('Third text');
            $browser->tap($this->toTop(...))->click('@builder-publish');
            $this->editorIs($browser, 'published');
            $browser->visit('/camp')->assertSee('Third text');

            // The version before opens in the editor and is published from there.
            $this->openEditor($browser, $page);
            $browser->tap($this->toTop(...))->click('@builder-versions')
                ->waitFor('@builder-version-2')
                ->assertSeeIn('@builder-version-0', 'published now')
                ->assertDontSeeIn('@builder-version-1', 'published now')
                ->assertSeeIn('@builder-version-0', 'Ada Admin')
                ->assertSeeIn('@builder-version-2', 'Before the history began')
                ->assertMissing('@builder-version-3')
                ->screenshot('site-builder/versions')
                ->click('@builder-open-version-1')
                ->waitFor('@builder-notice')
                ->assertSeeIn('@builder-notice', 'The version is open in the editor.')
                ->click('@layout-block-toggle-0-0-0')
                ->waitForTextIn($text, 'Second text');
            $this->editorIs($browser, 'unsaved');
            $this->assertSame('<p>Third text</p>', $stored());
            $browser->tap($this->toTop(...))->click('@builder-publish');
            $this->editorIs($browser, 'published');
            $this->assertSame('<p>Second text</p>', $stored());

            // With changes not published, going back to what is is one button too.
            $write('Typed and regretted');
            $browser->tap($this->toTop(...))->click('@builder-discard')->waitFor('@builder-discard-confirm')->click('@builder-discard-confirm');
            $this->editorIs($browser, 'published')->waitForTextIn($text, 'Second text');
        });
    }

    public function test_an_unfinished_block_can_wait_in_a_draft_but_is_not_published(): void
    {
        $admin = $this->admin();
        $page = $this->pageOf([[[$this->textBlock('b1', 'Kept')]]]);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $browser->loginAs($admin, 'admin');
            $this->openEditor($browser, $page);
            $browser->click('@builder-add-button')
                ->waitFor('@layout-setting-label input')
                ->type('@layout-setting-label input', 'Book a place')
                ->click('@layout-select-column-0-0')
                ->tap($this->toTop(...))->click('@builder-publish')
                ->waitFor('@builder-problem')
                ->assertSeeIn('@builder-problem', 'Section 1, column 1, block 2 (Button)')
                // The node is selected and says so in words.
                ->assertAttribute('@layout-block-toggle-0-0-1', 'aria-current', 'true')
                ->assertSeeIn('@layout-block-failed-0-0-1', 'Error')
                ->assertSeeIn('@layout-panel-problem', 'Link')
                ->assertValue('@layout-setting-label input', 'Book a place')
                ->screenshot('site-builder/refused');
            $this->editorIs($browser, 'unsaved');
            $this->assertCount(1, $page->fresh()->layout['sections'][0]['columns'][0]['blocks']);

            $browser->tap($this->toTop(...))->click('@builder-save-draft');
            $this->editorIs($browser, 'draft')->assertMissing('@builder-problem')->assertMissing('@layout-block-failed-0-0-1');

            $browser->click('@layout-setting-link [data-link-kind="url"]')
                ->waitFor('@layout-setting-link [data-link-url]')
                ->type('@layout-setting-link [data-link-url]', '/contacts')
                ->tap($this->toTop(...))->click('@builder-publish');
            $this->editorIs($browser, 'published')->assertMissing('@builder-problem');
        });

        $this->assertCount(2, $page->fresh()->layout['sections'][0]['columns'][0]['blocks']);
    }

    public function test_changes_made_elsewhere_are_not_replaced_without_asking(): void
    {
        $admin = $this->admin();
        $page = $this->pageOf([[[$this->textBlock('b1', 'Start')]]]);
        $theirs = fn (string $text): array => $this->layoutOf([[[$this->textBlock('b1', $text)]]]);
        $stored = fn (): array => $page->fresh()->layout;

        $this->browse(function (Browser $browser) use ($admin, $page, $theirs, $stored): void {
            $drafts = app(Revisions::class);
            $keep = fn (string $text) => $drafts->keepDraft($page->fresh(), 'layout', $theirs($text), Layout::fingerprint($page->fresh()->layout), 'Bob');

            $browser->loginAs($admin, 'admin');
            $this->openEditor($browser, $page);

            // Someone saves a draft while this one is open: saving over it asks.
            $keep('Bob was here');
            $browser->click('@layout-add-section')->tap($this->toTop(...))->click('@builder-save-draft')
                ->waitFor('@builder-conflict')
                ->assertSeeIn('@builder-conflict', 'Someone saved or discarded the draft')
                ->click('@builder-conflict-force')
                ->waitUntilMissing('@builder-conflict');
            $this->editorIs($browser, 'draft')->assertSeeIn('@builder-status', 'Ada Admin');
            $this->assertCount(2, $drafts->draft($page, 'layout')->values['layout']['sections']);

            // Publishing over their draft asks too; their page can be loaded.
            $keep('Bob again');
            $browser->tap($this->toTop(...))->click('@builder-publish')
                ->waitFor('@builder-conflict-reload')
                ->click('@builder-conflict-reload')
                ->waitUntilMissing('@builder-conflict-reload');
            $this->editorIs($browser, 'draft')->assertSeeIn('@builder-status', 'Bob')
                ->click('@layout-block-toggle-0-0-0')
                ->waitForTextIn('@layout-setting-html .ProseMirror', 'Bob again')
                ->assertMissing('@builder-stale');

            // The published page changes under the editor.
            $page->forceFill(['layout' => $theirs('Published by Carol')])->save();
            $browser->tap($this->toTop(...))->click('@builder-publish')
                ->waitFor('@builder-conflict')
                ->assertSeeIn('@builder-conflict', 'The published page changed')
                ->screenshot('site-builder/conflict');
            $this->assertEquals($theirs('Published by Carol'), $stored());

            // Opened again, the older draft says it is behind.
            $this->openEditor($browser, $page);
            $browser->assertSeeIn('@builder-stale', 'The published page changed after this draft was started.')
                ->assertMissing('@builder-hidden')
                ->tap($this->toTop(...))->click('@builder-publish')
                ->waitFor('@builder-conflict-force')
                ->click('@builder-conflict-force');
            $this->editorIs($browser, 'published')->assertMissing('@builder-stale');
            $this->assertEquals($theirs('Bob again'), $stored());

            // A page that is switched off says that publishing does not show it.
            $page->forceFill(['active' => false])->save();
            $this->openEditor($browser, $page);
            $browser->assertSeeIn('@builder-hidden', 'This page is switched off');
        });
    }

    public function test_leaving_with_changes_not_kept_asks_first(): void
    {
        $admin = $this->admin();
        $page = $this->pageOf([[[$this->textBlock('b1', 'Start')]]]);

        $this->browse(function (Browser $browser) use ($admin, $page): void {
            $editor = "/admin/pages/{$page->id}/builder/layout";

            $browser->loginAs($admin, 'admin');
            $this->openEditor($browser, $page);
            $browser->click('@layout-add-section')
                ->tap($this->toTop(...))->click('@builder-back')
                ->waitForDialog()
                ->assertDialogOpened('The changes are not saved. Leave the editor and lose them?')
                ->dismissDialog()
                ->assertPathIs($editor)
                ->assertPresent('@layout-section-1')
                ->tap($this->toTop(...))->click('@builder-back')
                ->waitForDialog()
                ->acceptDialog()
                ->waitForLocation("/admin/pages/{$page->id}/edit")
                ->waitFor('@layout-summary')
                ->assertSeeIn('@layout-summary', '1 section, 1 block');

            // With nothing to lose, nothing is asked.
            $this->openEditor($browser, $page);
            $browser->tap($this->toTop(...))->click('@builder-back')->waitForLocation("/admin/pages/{$page->id}/edit");
        });

        $this->assertCount(1, $page->fresh()->layout['sections']);
    }
}
