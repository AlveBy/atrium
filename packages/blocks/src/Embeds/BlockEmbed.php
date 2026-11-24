<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Blocks\Embeds;

use Atrium\Admin\Embeds\ModelEmbed;
use Atrium\Blocks\Blocks;
use Atrium\Blocks\Models\Block;
use Illuminate\Database\Eloquent\Model;

/**
 * "Insert → Block" in the editor: one of the site's blocks, in the place of
 * the placeholder. Written by id, so a block may be given another code
 * without breaking the texts that hold it.
 */
final class BlockEmbed extends ModelEmbed
{
    public function label(): string
    {
        return __('atrium-blocks::blocks.embed');
    }

    protected function model(): string
    {
        return Block::class;
    }

    protected function renderRecord(Model $record): string
    {
        return app(Blocks::class)->html((string) $record->getAttribute('code'));
    }
}
