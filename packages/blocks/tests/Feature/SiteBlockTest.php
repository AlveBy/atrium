<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks\Tests\Feature;

use Atrium\Admin\Builder\BlockType;
use Atrium\Admin\Builder\BlockTypes;
use Atrium\Admin\Facades\Hook;
use Atrium\Admin\Fields\Field;
use Atrium\Blocks\Builder\ReusableBlock;
use Atrium\Blocks\Models\Block;
use Atrium\Blocks\Tests\TestCase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * A reusable block as a block of a page made with the site builder.
 */
final class SiteBlockTest extends TestCase
{
    private function type(): BlockType
    {
        $type = $this->app->make(BlockTypes::class)->get('block');
        $this->assertInstanceOf(ReusableBlock::class, $type);

        return $type;
    }

    private function accepts(mixed $block): bool
    {
        return Validator::make(['block' => $block], ['block' => $this->type()->fields()[0]->getValidationRules()])->passes();
    }

    public function test_the_module_offers_its_block_type_at_its_priority(): void
    {
        $probe = new class extends BlockType
        {
            public function label(): string
            {
                return 'Probe';
            }

            public function view(): string
            {
                return 'probe';
            }
        };

        Hook::register(BlockTypes::REGISTRY, 'block', $probe, 59);
        $this->type();

        Hook::register(BlockTypes::REGISTRY, 'block', $probe, 60);
        $this->assertSame($probe, $this->app->make(BlockTypes::class)->get('block'));
    }

    public function test_the_editor_picks_a_block_by_its_name(): void
    {
        $zeta = $this->block('zeta', '<p>Z</p>');
        $alpha = $this->block('alpha', '<p>A</p>');

        $this->assertSame('Reusable block', $this->type()->label());
        $this->assertSame([], $this->type()->translatable());

        $fields = array_map(static fn (Field $field): array => $field->toArray(), $this->type()->fields());
        $this->assertCount(1, $fields);
        $this->assertSame(['block', 'select', 'Block', true], [$fields[0]['name'], $fields[0]['type'], $fields[0]['label'], $fields[0]['required']]);
        $this->assertSame([[$alpha->id, 'Alpha'], [$zeta->id, 'Zeta']], array_map(static fn (array $option): array => [$option['value'], $option['label']], $fields[0]['options']));

        $this->assertTrue($this->accepts($alpha->id));
        $this->assertFalse($this->accepts($zeta->id + 100));
        $this->assertFalse($this->accepts(null));
    }

    public function test_a_block_is_named_by_a_number_even_when_there_is_none_to_pick(): void
    {
        $this->assertTrue($this->accepts(3));
        $this->assertFalse($this->accepts('phone'));
    }

    public function test_a_page_shows_the_text_of_the_block_chosen(): void
    {
        $block = $this->block('phone', '<p>+1 555</p>');

        $this->assertSame(['html' => '<p>+1 555</p>'], $this->type()->data(['block' => $block->id]));
        $this->assertSame(['html' => '<p>+1 555</p>'], $this->type()->data(['block' => (string) $block->id]));
        $this->assertSame('<div class="content"><p>+1 555</p></div>', trim(view($this->type()->view(), ['html' => '<p>+1 555</p>'])->render()));
    }

    /**
     * @return array<string,array{mixed}>
     */
    public static function noBlocks(): array
    {
        return [
            'nothing chosen' => [null],
            'a code' => ['phone'],
            'a number with letters' => ['1abc'],
            'a list' => [[1]],
            'a block that is gone' => [999],
        ];
    }

    #[DataProvider('noBlocks')]
    public function test_a_page_without_a_block_chosen_shows_nothing(mixed $block): void
    {
        // The block number 1: "1abc" and [1] would find it if read as numbers.
        // MySQL and PostgreSQL do not start counting anew for each test.
        Block::unguarded(fn (): Block => $this->block('phone', '<p>+1 555</p>', ['id' => 1]));

        $this->assertNull($this->type()->data(['block' => $block]));
        $this->assertNull($this->type()->data([]));
    }

    public function test_a_switched_off_or_empty_block_is_not_drawn(): void
    {
        $off = $this->block('off', '<p>Hidden</p>', ['active' => false]);
        $empty = $this->block('empty', '');

        $this->assertNull($this->type()->data(['block' => $off->id]));
        $this->assertNull($this->type()->data(['block' => $empty->id]));
    }
}
