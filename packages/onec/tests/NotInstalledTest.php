<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\OneC\Tests;

use Atrium\Integrations\IntegrationsServiceProvider;

/**
 * The package is in vendor but `atrium:module:install` has not run: the
 * address 1C would call is not there.
 */
final class NotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', ['alveby/atrium-catalog', IntegrationsServiceProvider::MODULE]);
    }

    public function test_without_the_install_there_is_no_address(): void
    {
        $this->assertFalse(app('router')->has('atrium.onec.exchange'));
        $this->assertFalse(app('router')->has('atrium.onec.exchange.bitrix'));
        $this->get('/atrium-1c/exchange?type=catalog&mode=checkauth')->assertNotFound();
        $this->get('/bitrix/admin/1c_exchange.php?type=catalog&mode=checkauth')->assertNotFound();
    }
}
