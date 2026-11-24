<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks\Tests\Feature;

use Atrium\Admin\Embeds\Embeds;
use Atrium\Blocks\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Required with Composer but not installed: no blocks in the panel, none in
 * a text, and a template that asks for one gets an empty place.
 */
final class NotInstalledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('atrium.modules', []);
    }

    public function test_the_module_stays_out_of_the_panel_and_the_editor(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get('/admin/blocks')->assertNotFound();
        $this->assertNull(collect($this->app->make(Embeds::class)->types($admin))->firstWhere('type', 'block'));
        $this->getJson('/admin/embeds/block')->assertNotFound();
    }

    public function test_a_template_gets_an_empty_place_without_asking_the_database(): void
    {
        DB::enableQueryLog();

        $run = uniqid();

        $this->assertSame("<p id=\"{$run}\"></p>", Blade::render("<p id=\"{$run}\">@block('phone')</p>"));
        $this->assertSame('', atrium_block('phone'));
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_saving_or_deleting_a_block_does_not_touch_the_cache(): void
    {
        $block = $this->block('phone', '+1 00');
        $block->delete();

        $this->assertNull(Cache::get('atrium-blocks:version'));
    }
}
