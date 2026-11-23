<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\News\Models\News;
use Laravel\Dusk\Browser;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * "Improve" in the visual editor of a field (alveby/atrium-ai): the toolbar
 * menu, the selected piece and only that piece swapped, the whole text when
 * nothing is selected, and the links under the field in the plain modes.
 * The provider is a stand-in served on this machine (tests/Browser/Fakes): it
 * corrects "veiw" and "teh" in the text it is sent and answers with that text.
 */
final class AiImproveTest extends AdminTestCase
{
    private const TEXT = '<p>Spring has teh best <strong>veiw</strong> of the lake. Second teh line stays.</p>';

    private const EDITOR = '[data-content-field="detail_text"] .ProseMirror';

    public function test_a_selected_piece_is_improved_and_only_that_piece_is_replaced(): void
    {
        $admin = $this->admin();
        $news = News::query()->create(['name' => 'Spring', 'code' => 'spring', 'active' => true, 'sort' => 500, 'detail_text' => self::TEXT]);

        $this->withProvider(function () use ($admin, $news): void {
            $this->browse(function (Browser $browser) use ($admin, $news): void {
                $this->edit($browser, $admin, $news)
                    // The buttons are in the toolbar of the editor, not under it.
                    ->assertMissing('[data-ai-generate="detail_text"]')
                    ->assertMissing('[data-ai-improve="detail_text"]')
                    ->doubleClick(self::EDITOR.' strong')
                    ->click('[data-content-field="detail_text"] [dusk="ai-toolbar"]')
                    ->waitFor('@ai-menu')
                    ->assertSeeIn('@ai-menu', 'Generate')
                    ->assertSeeIn('@ai-menu', 'Improve the selection')
                    // One language: nothing to translate into.
                    ->assertDontSeeIn('@ai-menu', 'Translate')
                    ->click('@ai-item-improve')
                    ->waitFor('@ai-result', 15)
                    ->assertMissing('@ai-working');

                // The dialog holds the corrected piece, tags and all, and nothing around it.
                $result = $browser->value('@ai-result');
                $this->assertStringContainsString('<strong>view</strong>', $result);
                $this->assertStringNotContainsString('Spring', $result);
                $this->assertStringNotContainsString('Second', $result);
                $browser->screenshot('ai-improve/dialog');

                // Nothing is replaced until the editor accepts.
                $browser->assertSeeIn(self::EDITOR, 'veiw')
                    ->click('@ai-accept')
                    ->waitUntilMissing('@ai-result')
                    ->assertSeeIn(self::EDITOR, 'Spring has teh best view of the lake.')
                    ->assertSeeIn(self::EDITOR, 'Second teh line stays.')
                    ->assertDontSeeIn(self::EDITOR, 'veiw');

                // Still one paragraph, and the bold kept.
                $this->assertSame(1, count($browser->elements(self::EDITOR.' p')));
                $this->assertSame('view', trim($browser->text(self::EDITOR.' strong')));
                $browser->screenshot('ai-improve/accepted');
            });
        });

        // Not saved until the form is.
        $this->assertSame(self::TEXT, $news->fresh()->detail_text);
        $this->assertDatabaseHas('ai_usage', ['task' => 'improve', 'admin_user_id' => $admin->id, 'provider' => 'ollama', 'succeeded' => true]);
    }

    public function test_without_a_selection_the_whole_text_is_improved_and_saved_with_the_form(): void
    {
        $admin = $this->admin();
        $news = News::query()->create(['name' => 'Spring', 'code' => 'spring', 'active' => true, 'sort' => 500, 'detail_text' => self::TEXT]);

        $this->withProvider(function () use ($admin, $news): void {
            $this->browse(function (Browser $browser) use ($admin, $news): void {
                $this->edit($browser, $admin, $news)
                    ->click('[data-content-field="detail_text"] [dusk="ai-toolbar"]')
                    ->waitFor('@ai-menu')
                    ->assertSeeIn('@ai-menu', 'Improve')
                    ->assertDontSeeIn('@ai-menu', 'Improve the selection')
                    ->click('@ai-item-improve')
                    ->waitFor('@ai-result', 15);

                $this->assertStringContainsString('Spring has the best <strong>view</strong> of the lake. Second the line stays.', $browser->value('@ai-result'));

                $browser->click('@ai-accept')
                    ->waitUntilMissing('@ai-result')
                    ->assertSeeIn(self::EDITOR, 'Spring has the best view of the lake. Second the line stays.')
                    ->press('Save')
                    ->waitForLocation('/admin/news');
            });
        });

        $this->assertStringContainsString('Spring has the best <strong>view</strong> of the lake. Second the line stays.', $news->fresh()->detail_text);
    }

    public function test_an_empty_field_can_only_be_written_not_improved(): void
    {
        $admin = $this->admin();
        $news = News::query()->create(['name' => 'Spring', 'code' => 'spring', 'active' => true, 'sort' => 500, 'detail_text' => '']);

        $this->withProvider(function () use ($admin, $news): void {
            $this->browse(function (Browser $browser) use ($admin, $news): void {
                $this->edit($browser, $admin, $news)
                    ->click('[data-content-field="detail_text"] [dusk="ai-toolbar"]')
                    ->waitFor('@ai-menu')
                    ->assertSeeIn('@ai-menu', 'Generate')
                    ->assertMissing('@ai-item-improve');

                // Typing into the field brings the item.
                $browser->click('[data-content-field="detail_text"] [dusk="ai-toolbar"]')
                    ->waitUntilMissing('@ai-menu')
                    ->click(self::EDITOR)
                    ->keys(self::EDITOR, 'Some teh text')
                    ->click('[data-content-field="detail_text"] [dusk="ai-toolbar"]')
                    ->waitFor('@ai-item-improve')
                    ->assertPresent('@ai-item-improve');
            });
        });
    }

    public function test_the_plain_modes_keep_the_links_under_the_field(): void
    {
        $admin = $this->admin();
        $news = News::query()->create(['name' => 'Spring', 'code' => 'spring', 'active' => true, 'sort' => 500, 'detail_text' => self::TEXT]);

        $this->withProvider(function () use ($admin, $news): void {
            $this->browse(function (Browser $browser) use ($admin, $news): void {
                $this->edit($browser, $admin, $news)
                    ->assertPresent('[data-content-field="detail_text"] [dusk="ai-toolbar"]')
                    ->click('[data-content-field="detail_text"] [data-content-mode="html"]')
                    ->waitFor('[data-ai-improve="detail_text"]')
                    ->assertMissing('[data-content-field="detail_text"] [dusk="ai-toolbar"]')
                    ->assertPresent('[data-ai-generate="detail_text"]')
                    ->click('[data-ai-improve="detail_text"]')
                    ->waitFor('@ai-result', 15);

                $this->assertStringContainsString('Spring has the best <strong>view</strong> of the lake.', $browser->value('@ai-result'));

                $browser->click('@ai-accept')
                    ->waitUntilMissing('@ai-result')
                    ->assertInputValue('[data-content-field="detail_text"] textarea', '<p>Spring has the best <strong>view</strong> of the lake. Second the line stays.</p>')
                    // And back to the visual editor, with the toolbar.
                    ->click('[data-content-field="detail_text"] [data-content-mode="wysiwyg"]')
                    ->waitFor('[data-content-field="detail_text"] [dusk="ai-toolbar"]')
                    ->assertMissing('[data-ai-improve="detail_text"]');
            });
        });
    }

    public function test_a_provider_that_fails_leaves_the_text_as_it_was(): void
    {
        $admin = $this->admin();
        $news = News::query()->create(['name' => 'Spring', 'code' => 'spring', 'active' => true, 'sort' => 500, 'detail_text' => '<p>Text that is broken</p>']);

        $this->withProvider(function () use ($admin, $news): void {
            $this->browse(function (Browser $browser) use ($admin, $news): void {
                $this->edit($browser, $admin, $news)
                    ->click('[data-content-field="detail_text"] [dusk="ai-toolbar"]')
                    ->waitFor('@ai-item-improve')
                    ->click('@ai-item-improve')
                    ->waitFor('@ai-error', 15)
                    ->assertSeeIn('@ai-error', 'The AI provider did not answer. Try again later.')
                    ->assertMissing('@ai-accept');
            });
        });

        $this->assertSame('<p>Text that is broken</p>', $news->fresh()->detail_text);
    }

    public function test_without_a_provider_there_are_no_buttons(): void
    {
        $admin = $this->admin();
        $news = News::query()->create(['name' => 'Spring', 'code' => 'spring', 'active' => true, 'sort' => 500, 'detail_text' => self::TEXT]);

        $this->withEnv(['ATRIUM_AI_TEXT' => 'gemini', 'GEMINI_API_KEY' => ''], function () use ($admin, $news): void {
            $this->browse(function (Browser $browser) use ($admin, $news): void {
                $this->edit($browser, $admin, $news, toolbar: false)
                    ->assertMissing('@ai-toolbar')
                    ->click('[data-content-field="detail_text"] [data-content-mode="html"]')
                    ->waitFor('[data-content-field="detail_text"] textarea')
                    ->assertMissing('[data-ai-generate="detail_text"]')
                    ->assertMissing('[data-ai-improve="detail_text"]');
            });
        });
    }

    /**
     * Opens the news form in the visual editor.
     */
    private function edit(Browser $browser, mixed $admin, News $news, bool $toolbar = true): Browser
    {
        $browser->loginAs($admin, 'admin')->visit('/admin');
        $browser->script("localStorage.removeItem('content-field-mode-detail_text'); localStorage.removeItem('content-field-mode-preview_text');");

        $browser->visit("/admin/news/{$news->id}/edit")->waitFor(self::EDITOR);

        return $toolbar ? $browser->waitFor('[data-content-field="detail_text"] [dusk="ai-toolbar"]') : $browser;
    }

    /**
     * Runs the test with the stand-in provider up and the app talking to it.
     */
    private function withProvider(callable $test): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        $server = new Process([PHP_BINARY, '-S', "127.0.0.1:{$port}", base_path('tests/Browser/Fakes/ollama.php')]);
        $server->start();

        try {
            $deadline = microtime(true) + 10;

            while (@fsockopen('127.0.0.1', $port) === false) {
                if (microtime(true) > $deadline) {
                    throw new RuntimeException("The stand-in provider did not start on port {$port}.");
                }

                usleep(50_000);
            }

            $this->withEnv(['ATRIUM_AI_TEXT' => 'ollama:fake', 'OLLAMA_URL' => "http://127.0.0.1:{$port}"], $test);
        } finally {
            $server->stop();
        }
    }
}
