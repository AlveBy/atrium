<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap;

/**
 * The form of an area, drawn on the layer's picture in its own pixels.
 */
enum Shape: string
{
    case Polygon = 'polygon';
    case Rect = 'rect';
    case Circle = 'circle';
    case Point = 'point';

    public function label(): string
    {
        return __('atrium-image-map::image-map.shapes.'.$this->value);
    }

    /**
     * @return array<string,string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
