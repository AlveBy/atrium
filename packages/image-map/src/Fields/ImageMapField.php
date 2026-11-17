<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap\Fields;

use Atrium\Admin\Fields\BelongsToField;
use Atrium\ImageMap\Models\ImageMap;

/**
 * A map picked in any module's form, kept as the map's id:
 *
 *     ImageMapField::make('image_map_id')
 *
 * and drawn on the page with `<x-atrium-image-map::map :map="$record->image_map_id" />`.
 */
final class ImageMapField extends BelongsToField
{
    public static function make(string $name): static
    {
        return parent::make($name)
            ->model(ImageMap::class)
            ->label(__('atrium-image-map::image-map.fields.map'))
            ->query(fn ($query) => $query->orderBy('sort')->orderBy('id'));
    }
}
