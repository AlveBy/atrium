<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin;

use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * The "Generate" dialog (alveby/atrium-ai) on a public demo: it says the demo
 * is public, and when the day's limit is used up it says that instead of
 * writing. The provider is a stand-in served on this machine.
 */
final class AiDemoTest extends AdminTestCase
{
    public function test_the_dialog_of_a_demo_says_it_is_public_and_a_site_that_is_not_a_demo_does_not(): void
    {
        $admin = $this->admin();
        Cache::store('file')->flush();

        $this->withProvider(['ATRIUM_DEMO' => 'true'], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openGenerate($browser, $admin)
                    ->waitFor('[data-ai-result]', 15)
                    ->assertSeeIn('[data-ai-demo-notice]', 'This demo is public: do not type personal data');
                $browser->screenshot('ai-demo/dialog-notice');
            });
        });

        $this->withProvider([], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openGenerate($browser, $admin)
                    ->waitFor('[data-ai-result]', 15)
                    ->assertMissing('[data-ai-demo-notice]');
            });
        });

        Cache::store('file')->flush();
    }

    public function test_at_the_daily_limit_the_dialog_says_so_and_no_provider_is_asked(): void
    {
        $admin = $this->admin();
        Cache::store('file')->flush();

        $this->withProvider(['ATRIUM_DEMO' => 'true', 'ATRIUM_AI_DEMO_DAILY_LIMIT' => '1', 'ATRIUM_AI_DEMO_STORE' => 'file'], function () use ($admin): void {
            $this->browse(function (Browser $browser) use ($admin): void {
                $this->openGenerate($browser, $admin)
                    ->waitFor('[data-ai-result]', 15)
                    ->click('[data-ai-accept]')
                    ->waitUntilMissing('[data-ai-result]')
                    ->click('[data-ai-generate="seo_title"]')
                    ->waitFor('[data-ai-error]', 15)
                    ->assertSeeIn('[data-ai-error]', 'The demo\'s daily AI limit is used up. Try again tomorrow.')
                    ->assertSeeIn('[data-ai-demo-notice]', 'This demo is public');
                $browser->screenshot('ai-demo/limit');
            });
        });

        Cache::store('file')->flush();
        $this->assertDatabaseCount('ai_usage', 1);
    }

    /** The SEO tab of a new page, with the Generate link of the title clicked. */
    private function openGenerate(Browser $browser, mixed $admin): Browser
    {
        return $browser->loginAs($admin, 'admin')
            ->visit('/admin/pages/create')
            ->waitFor('#name')
            ->type('#name', 'About us')
            ->click('[data-tab="seo"]')
            ->waitFor('[data-ai-generate="seo_title"]')
            ->click('[data-ai-generate="seo_title"]');
    }

    /**
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
            $deadline = microtime(true) + 10;

            while (@fsockopen('127.0.0.1', $port) === false) {
                if (microtime(true) > $deadline) {
                    throw new RuntimeException("The stand-in provider did not start on port {$port}.");
                }

                usleep(50_000);
            }

            $this->withEnv(['ATRIUM_AI_TEXT' => 'ollama:fake', 'OLLAMA_URL' => "http://127.0.0.1:{$port}", ...$env], $test);
        } finally {
            $server->stop();
        }
    }
}
