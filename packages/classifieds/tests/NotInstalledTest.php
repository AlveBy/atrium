<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Tests;

use Atrium\Admin\Resources\ResourceRegistry;
use Atrium\Classifieds\Models\Ad;
use Illuminate\Support\Facades\Schema;

/**
 * Required with Composer but not installed with atrium:module:install.
 */
final class NotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', []);
    }

    public function test_a_module_that_is_not_installed_stays_out_of_the_panel_and_the_site(): void
    {
        $this->assertNotContains(Ad::class, app(ResourceRegistry::class)->all());
        $this->assertFalse(app('router')->has('atrium.classifieds.index'));
        $this->assertFalse(Schema::hasTable('classified_ads'));

        $this->get('/classifieds')->assertNotFound();
    }
}
