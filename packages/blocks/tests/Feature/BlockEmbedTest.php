<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks\Tests\Feature;

use Atrium\Admin\Embeds\Embeds;
use Atrium\Blocks\Embeds\BlockEmbed;
use Atrium\Blocks\Tests\TestCase;
use Atrium\Forms\Models\Form;

/**
 * "Insert → Block" in the editor: the block is picked by name and the site
 * draws it in the place of the placeholder.
 */
final class BlockEmbedTest extends TestCase
{
    private function embeds(): Embeds
    {
        return $this->app->make(Embeds::class);
    }

    public function test_the_module_registers_the_block_type_next_to_the_form(): void
    {
        $this->assertInstanceOf(BlockEmbed::class, $this->embeds()->get('block'));
        $this->assertSame(
            [
                ['type' => 'form', 'label' => 'Form', 'url' => '/admin/embeds/form'],
                ['type' => 'block', 'label' => 'Block', 'url' => '/admin/embeds/block'],
            ],
            $this->embeds()->types($this->admin()),
        );
    }

    public function test_the_placeholder_becomes_the_block_and_the_text_stays(): void
    {
        $phone = $this->block('phone', '<b>+1 00</b>');

        $this->assertSame('<p>Call</p><b>+1 00</b><p>now</p>', $this->embeds()->render('<p>Call</p>'.$this->placeholder($phone->id).'<p>now</p>'));
    }

    public function test_the_block_follows_its_new_code_and_text(): void
    {
        $phone = $this->block('phone', '+1 00');
        $html = $this->placeholder($phone->id);
        $this->assertSame('+1 00', $this->embeds()->render($html));

        $phone->update(['code' => 'hotline', 'content' => ['en' => '+2 00']]);

        $this->assertSame('+2 00', $this->embeds()->render($html));
    }

    public function test_a_block_that_is_off_or_gone_leaves_nothing(): void
    {
        $off = $this->block('banner', 'Sale', ['active' => false]);

        $this->assertSame('<p>a</p>', $this->embeds()->render('<p>a</p>'.$this->placeholder($off->id)));
        $this->assertSame('<p>a</p>', $this->embeds()->render('<p>a</p>'.$this->placeholder(9999)));
        $this->assertSame('<p>a</p>', $this->embeds()->render('<p>a</p>'.$this->placeholder('banner')));
    }

    public function test_a_block_may_hold_a_form(): void
    {
        $form = Form::query()->create(['name' => 'Contact us', 'code' => 'contact']);
        $block = $this->block('write', 'Write to us: <div data-atrium-embed="form" data-id="'.$form->id.'"></div>');

        $html = $this->embeds()->render($this->placeholder($block->id));

        $this->assertStringStartsWith('Write to us: ', $html);
        $this->assertStringContainsString('<form id="form-contact"', $html);
    }

    public function test_the_picker_offers_the_blocks_by_name_to_one_who_may_see_them(): void
    {
        $phone = $this->block('phone', '+1 00', ['name' => 'Phone number']);
        $this->block('address', 'Main street', ['name' => 'Address']);
        $this->actingAs($this->editor(['blocks.view']), 'admin');

        $this->getJson('/admin/embeds/block?q=phone')->assertOk()->assertExactJson(['data' => [['id' => (string) $phone->id, 'label' => 'Phone number']]]);
        $this->getJson('/admin/embeds/block')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson("/admin/embeds/block?ids={$phone->id}")->assertOk()->assertExactJson(['data' => [['id' => (string) $phone->id, 'label' => 'Phone number']]]);
    }

    public function test_the_picker_is_closed_to_one_who_may_not_see_the_blocks(): void
    {
        $this->block('phone', '+1 00');
        $this->actingAs($this->editor(['forms.view']), 'admin');

        $this->getJson('/admin/embeds/block')->assertForbidden();
    }
}
