<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Integrations\Tests;

use Atrium\Integrations\Enums\Status;
use Atrium\Integrations\Exchange;
use Illuminate\Support\Facades\Log;

/** Required by composer but not installed: the connector keeps working, the line goes to the application log. */
final class NotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', []);
    }

    public function test_without_the_module_the_line_goes_to_the_application_log(): void
    {
        Log::spy();

        $entry = app(Exchange::class)->record('1c', 'catalog.import', Status::Failed, error: 'no disk');

        $this->assertFalse($entry->exists);
        $this->assertNull($entry->created_at);
        $this->assertSame('no disk', $entry->error);
        Log::shouldHaveReceived('log')->once()->with('error', 'Atrium integration 1c catalog.import: failed no disk');
    }

    public function test_without_the_module_a_good_line_is_only_information(): void
    {
        Log::spy();

        app(Exchange::class)->record('1c', 'catalog.import', Status::Ok);

        Log::shouldHaveReceived('log')->once()->with('info', 'Atrium integration 1c catalog.import: ok');
    }
}
