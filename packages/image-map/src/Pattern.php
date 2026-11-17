<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap;

/**
 * How the inside of an area with a status is filled. Statuses differ by
 * pattern as well as by colour, so they can be told apart without seeing
 * the hue: in greyscale, in print, with colour blindness.
 */
enum Pattern: string
{
    case Solid = 'solid';
    case Hatch = 'hatch';
    case Cross = 'cross';
    case Dots = 'dots';
    case Vertical = 'vertical';
    case Horizontal = 'horizontal';
    case None = 'none';

    public function label(): string
    {
        return __('atrium-image-map::image-map.patterns.'.$this->value);
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
