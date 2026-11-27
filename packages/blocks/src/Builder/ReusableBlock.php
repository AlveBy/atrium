<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks\Builder;

use Atrium\Admin\Builder\BlockType;
use Atrium\Admin\Fields\SelectField;
use Atrium\Admin\Modules\Modules;
use Atrium\Blocks\Blocks;
use Atrium\Blocks\Models\Block;

/**
 * The block of the site builder that shows a reusable block of this module:
 * written once, changed once for every page that holds it.
 */
final class ReusableBlock extends BlockType
{
    public function label(): string
    {
        return __('atrium-blocks::blocks.builder.label');
    }

    public function fields(): array
    {
        return [
            SelectField::make('block')
                ->label(__('atrium-blocks::blocks.embed'))
                ->options(Block::query()->orderBy('name')->pluck('name', 'id')->all())
                ->required()
                ->rules('integer'),
        ];
    }

    public function view(): string
    {
        return 'atrium-blocks::builder';
    }

    public function data(array $settings): ?array
    {
        $id = $settings['block'] ?? null;
        $code = is_numeric($id) ? Block::query()->whereKey((int) $id)->value('code') : null;
        // Empty for a block that is switched off, too.
        $html = $code === null ? '' : app(Blocks::class)->html((string) $code);

        return $html === '' ? null : ['html' => $html];
    }

    public function available(): bool
    {
        return app(Modules::class)->available(Block::class);
    }
}
