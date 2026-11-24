<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks\Tests\Feature;

use Atrium\Admin\Fields\Field;
use Atrium\Admin\Support\Sites;
use Atrium\Blocks\Blocks;
use Atrium\Blocks\BlocksServiceProvider;
use Atrium\Blocks\Models\Block;
use Atrium\Blocks\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

/**
 * What the module is made of: its form, its list, how it saves, what it
 * publishes and what it listens to.
 */
final class ModuleTest extends TestCase
{
    public function test_the_form_of_a_block_has_these_fields(): void
    {
        $this->assertSame([
            ['active', 'checkbox', 'Active', false, null, true],
            ['name', 'text', 'Name', true, null, null],
            ['code', 'slug', 'Code', false, 'Short, in Latin letters. A template shows the block with @block(\'code\'), a text with the + button of the editor.', null],
            ['content', 'content', 'Content', false, null, null],
        ], array_map(function (Field $field): array {
            $array = $field->toArray();

            return [$array['name'], $array['type'], $array['label'], $array['required'], $array['hint'], $array['default']];
        }, Block::getAdministrationFields()));
    }

    public function test_the_list_of_blocks_has_these_columns(): void
    {
        $this->assertSame([
            ['id', 'ID', true, false],
            ['name', 'Name', true, true],
            ['code', 'Code', true, true],
            ['active', 'Active', false, false],
            ['updated_at', 'Changed', true, false],
        ], array_map(fn (object $column): array => [$column->toArray()['name'], $column->toArray()['label'], $column->toArray()['sortable'], $column->toArray()['searchable']], Block::getAdministrationListColumns()));
    }

    public function test_a_block_is_on_or_off(): void
    {
        $block = $this->block('phone', '+1 00', ['active' => 1]);
        $off = $this->block('fax', '+2 00', ['active' => 0]);

        $this->assertTrue($block->fresh()->active);
        $this->assertFalse($off->fresh()->active);
    }

    public function test_the_texts_of_the_module_can_be_published(): void
    {
        $this->assertSame(
            [dirname(__DIR__, 2).'/src/../lang' => $this->app->langPath('vendor/atrium-blocks')],
            ServiceProvider::pathsToPublish(BlocksServiceProvider::class, 'atrium-blocks-lang'),
        );
    }

    public function test_a_block_is_kept_under_a_key_of_its_own_module_site_language_and_text(): void
    {
        $this->block('phone', '+1 00');
        $this->app->make(Blocks::class)->html('phone');

        $site = $this->app->make(Sites::class)->current()->id;
        $version = Cache::get('atrium-blocks:version');

        $this->assertSame(['html' => '+1 00'], Cache::get("atrium-blocks:{$site}:en:".md5('phone').":{$version}"));
    }
}
