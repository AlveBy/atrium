<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks\Tests\Feature;

use Atrium\Admin\Models\Site;
use Atrium\Blocks\Blocks;
use Atrium\Blocks\Models\Block;
use Atrium\Blocks\Tests\TestCase;
use Inertia\Testing\AssertableInertia;

/**
 * Editors keep the blocks in the admin, and what they save is what the site
 * shows at once.
 */
final class AdminTest extends TestCase
{
    public function test_the_installed_module_brings_its_list(): void
    {
        $this->block('phone', '+1 00');

        $this->actingAs($this->admin(), 'admin')
            ->get('/admin/blocks')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('title', 'Blocks'));
    }

    public function test_a_block_is_created_and_the_site_shows_it_at_once(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/blocks', ['name' => 'Phone', 'code' => 'phone', 'content' => ['en' => '+1 00'], 'active' => true])
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect(route('admin.blocks.index'));

        $block = Block::query()->sole();
        $this->assertNotNull($block->site_id);
        $this->assertSame('+1 00', $this->app->make(Blocks::class)->html('phone'));

        $this->put("/admin/blocks/{$block->id}", ['name' => 'Phone', 'code' => 'phone', 'content' => ['en' => '+2 00'], 'active' => true])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame('+2 00', $this->app->make(Blocks::class)->html('phone'));

        $this->put("/admin/blocks/{$block->id}", ['name' => 'Phone', 'code' => 'phone', 'content' => ['en' => '+2 00'], 'active' => false])
            ->assertSessionDoesntHaveErrors();
        $this->assertSame('', $this->app->make(Blocks::class)->html('phone'));
    }

    public function test_the_name_and_the_code_are_needed_and_the_code_is_taken_once_per_site(): void
    {
        $this->block('phone', '+1 00');
        $this->actingAs($this->admin(), 'admin');

        $this->post('/admin/blocks', ['code' => 'other', 'active' => true])->assertSessionHasErrors('name');
        $this->post('/admin/blocks', ['name' => 'Phone again', 'code' => 'phone', 'active' => true])->assertSessionHasErrors('code');

        // Another site may have a "phone" of its own.
        $other = Site::query()->create(['code' => 'shop', 'name' => 'Shop', 'domains' => ['shop.example.org'], 'languages' => ['en']]);
        $this->block('phone', '+9 99', ['site_id' => $other->id]);
        $this->assertSame(2, Block::query()->withoutGlobalScopes()->where('code', 'phone')->count());
    }

    public function test_a_block_is_deleted_from_the_list(): void
    {
        $block = $this->block('phone', '+1 00');
        $this->assertSame('+1 00', $this->app->make(Blocks::class)->html('phone'));

        $this->actingAs($this->admin(), 'admin')->delete("/admin/blocks/{$block->id}")->assertRedirect();

        $this->assertSame('', $this->app->make(Blocks::class)->html('phone'));
    }
}
