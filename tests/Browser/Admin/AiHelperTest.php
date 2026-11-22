<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Atrium\Admin\Models\AdminUser;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * The helper in the corner of the admin (alveby/atrium-ai): the mascot opens
 * a chat, a question gets its answer, the conversation goes on across pages.
 * The provider is a stand-in served on this machine (tests/Browser/Fakes).
 */
final class AiHelperTest extends AdminTestCase
{
    public function test_a_question_is_answered_and_the_conversation_goes_on_across_pages(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openAdmin($browser, $admin)
                    ->waitFor('@ai-mascot')
                    ->assertMissing('@ai-chat')
                    ->click('@ai-mascot')
                    ->waitFor('@ai-chat')
                    ->assertSeeIn('@ai-chat', 'Hi! I know the Atrium guides')
                    ->assertAttribute('@ai-send', 'disabled', 'true')
                    ->type('@ai-input', 'How many articles?')
                    ->click('@ai-send')
                    ->assertSeeIn('@ai-question', 'How many articles?')
                    ->waitFor('@ai-answer', 15)
                    ->assertSeeIn('@ai-answer strong', 'Question 1')
                    ->assertSeeIn('@ai-answer', 'Question 1: How many articles?')
                    ->assertAttributeContains('@ai-link', 'href', '/admin/articles')
                    ->assertSeeIn('@ai-answer', 'Elsewhere')
                    ->assertMissing('@ai-thinking')
                    ->assertValue('@ai-input', '');

                // A link to another site is left as text.
                $this->assertCount(1, $browser->elements('@ai-answer a'));
                $browser->screenshot('ai-helper/answer');

                // Enter sends; the earlier turn goes along with the question.
                $browser->type('@ai-input', 'And the stores?')
                    ->keys('@ai-input', '{enter}')
                    ->waitForTextIn('@ai-chat', 'Question 2: And the stores?', 15)
                    ->click('@ai-link')
                    ->waitForLocation('/admin/articles')
                    ->waitFor('@ai-mascot')
                    ->assertMissing('@ai-chat')
                    ->click('@ai-mascot')
                    ->waitFor('@ai-chat')
                    ->assertSeeIn('@ai-chat', 'Question 1: How many articles?')
                    ->assertSeeIn('@ai-chat', 'Question 2: And the stores?')
                    ->click('@ai-clear')
                    ->assertDontSeeIn('@ai-chat', 'Question 1')
                    ->assertMissing('@ai-answer')
                    ->keys('@ai-input', '{escape}')
                    ->waitUntilMissing('@ai-chat')
                    ->click('@ai-mascot')
                    ->waitFor('@ai-chat')
                    ->click('@ai-close')
                    ->waitUntilMissing('@ai-chat');
            });
        });

        $this->assertDatabaseHas('ai_usage', ['task' => 'help', 'admin_user_id' => $admin->id, 'provider' => 'ollama', 'model' => 'fake', 'input_tokens' => 12, 'output_tokens' => 34, 'succeeded' => true]);
        $this->assertDatabaseCount('ai_usage', 2);
    }

    public function test_markup_in_a_question_and_its_answer_is_shown_as_text(): void
    {
        $admin = $this->admin();
        $question = '<img src="x" onerror="window.pwned = 1"> <b>bold</b>';

        $this->withProvider([], function () use ($admin, $question): void {
            $this->browse(function (Browser $browser) use ($admin, $question): void {
                $this->openAdmin($browser, $admin)
                    ->waitFor('@ai-mascot')
                    ->click('@ai-mascot')
                    ->waitFor('@ai-chat')
                    ->type('@ai-input', $question)
                    ->click('@ai-send')
                    ->waitFor('@ai-answer', 15)
                    ->assertSeeIn('@ai-question', $question)
                    ->assertSeeIn('@ai-answer', $question);

                $this->assertCount(0, $browser->elements('@ai-chat img'));
                $this->assertCount(0, $browser->elements('@ai-chat b'));
                $this->assertNull($browser->script('return window.pwned ?? null;')[0]);
            });
        });
    }

    public function test_a_provider_that_fails_leaves_a_message_and_the_chat_can_go_on(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openAdmin($browser, $admin)
                    ->waitFor('@ai-mascot')
                    ->click('@ai-mascot')
                    ->waitFor('@ai-chat')
                    ->type('@ai-input', 'Is it broken?')
                    ->click('@ai-send')
                    ->waitFor('@ai-error', 15)
                    ->assertSeeIn('@ai-error', 'The AI provider did not answer. Try again later.')
                    ->assertMissing('@ai-answer')
                    ->type('@ai-input', 'And now?')
                    ->click('@ai-send')
                    // The question that failed stays in the conversation; the error does not.
                    ->waitFor('@ai-answer', 15)
                    ->assertSeeIn('@ai-answer', 'Question 2: And now?');
            });
        });

        $this->assertDatabaseHas('ai_usage', ['task' => 'help', 'succeeded' => false]);
    }

    public function test_without_a_provider_there_is_no_helper(): void
    {
        $admin = $this->admin();

        $this->withEnv(['ATRIUM_AI_TEXT' => 'gemini', 'GEMINI_API_KEY' => ''], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $browser->loginAs($admin, 'admin')
                    ->visit('/admin')
                    ->waitFor('[data-action="user-menu"]')
                    ->assertMissing('@ai-mascot');
            });
        });
    }

    public function test_a_site_can_turn_the_helper_off(): void
    {
        $admin = $this->admin();

        $this->withProvider(['ATRIUM_AI_HELP' => 'false'], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $browser->loginAs($admin, 'admin')
                    ->visit('/admin')
                    ->waitFor('[data-action="user-menu"]')
                    ->assertMissing('@ai-mascot');
            });
        });
    }

    public function test_a_public_demo_tells_visitors_not_to_type_personal_data(): void
    {
        $admin = $this->admin();

        $this->withProvider(['ATRIUM_DEMO' => 'true'], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openAdmin($browser, $admin)
                    ->waitFor('@ai-mascot')
                    ->click('@ai-mascot')
                    ->waitFor('@ai-chat')
                    ->assertSeeIn('@ai-demo-notice', 'This demo is public: do not type personal data');
                $browser->screenshot('ai-helper/demo-notice');
            });
        });
    }

    public function test_a_site_that_is_not_a_demo_has_no_such_notice(): void
    {
        $admin = $this->admin();

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openAdmin($browser, $admin)
                    ->waitFor('@ai-mascot')
                    ->click('@ai-mascot')
                    ->waitFor('@ai-chat')
                    ->assertMissing('@ai-demo-notice');
            });
        });
    }

    public function test_the_helper_says_so_when_the_demo_s_daily_limit_is_used_up(): void
    {
        $admin = $this->admin();
        Cache::store('file')->flush();

        $this->withProvider(['ATRIUM_DEMO' => 'true', 'ATRIUM_AI_DEMO_DAILY_LIMIT' => '1', 'ATRIUM_AI_DEMO_STORE' => 'file'], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openAdmin($browser, $admin)
                    ->waitFor('@ai-mascot')
                    ->click('@ai-mascot')
                    ->waitFor('@ai-chat')
                    ->type('@ai-input', 'First')
                    ->click('@ai-send')
                    ->waitFor('@ai-answer', 15)
                    ->type('@ai-input', 'Second')
                    ->click('@ai-send')
                    ->waitFor('@ai-error', 15)
                    ->assertSeeIn('@ai-error', 'The demo\'s daily AI limit is used up. Try again tomorrow.');
            });
        });

        Cache::store('file')->flush();
        $this->assertDatabaseCount('ai_usage', 1);
    }

    /**
     * Signs in and opens the dashboard with no conversation: the chat keeps
     * one in the tab's session storage, and Dusk keeps the tab between tests.
     */
    private function openAdmin(Browser $browser, AdminUser $admin): Browser
    {
        $browser->loginAs($admin, 'admin')->visit('/admin');
        $browser->script('sessionStorage.clear();');

        return $browser->refresh();
    }

    /**
     * Runs the test with the stand-in provider up and the app talking to it.
     *
     * @param  array<string, string>  $env
     */
    private function withProvider(array $env, callable $test): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        $server = new Process([PHP_BINARY, '-S', "127.0.0.1:{$port}", base_path('tests/Browser/Fakes/ollama.php')]);
        $server->start();

        try {
            $this->waitForPort($port);
            $this->withEnv(['ATRIUM_AI_TEXT' => 'ollama:fake', 'OLLAMA_URL' => "http://127.0.0.1:{$port}", ...$env], $test);
        } finally {
            $server->stop();
        }
    }

    private function waitForPort(int $port): void
    {
        $deadline = microtime(true) + 10;

        while (microtime(true) < $deadline) {
            $connection = @fsockopen('127.0.0.1', $port);

            if ($connection !== false) {
                fclose($connection);

                return;
            }

            usleep(50_000);
        }

        throw new RuntimeException("The stand-in provider did not start on port {$port}.");
    }
}
