<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Tests\Browser\Admin\Concerns;

use Atrium\Admin\Models\AdminUser;
use Laravel\Dusk\Browser;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * The AI provider of the browser tests: a stand-in served on this machine
 * (tests/Browser/Fakes/ollama.php), so nothing leaves it.
 */
trait TalksToAStandInProvider
{
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
