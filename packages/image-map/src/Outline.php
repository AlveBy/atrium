<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\ImageMap;

/**
 * The border of an area with a status: a second sign besides the colour.
 */
enum Outline: string
{
    case Solid = 'solid';
    case Dashed = 'dashed';
    case Dotted = 'dotted';
    case Thick = 'thick';

    public function label(): string
    {
        return __('atrium-image-map::image-map.outlines.'.$this->value);
    }

    /**
     * stroke-dasharray, in multiples of the stroke width.
     */
    public function dashArray(): ?string
    {
        return match ($this) {
            self::Dashed => '4 2',
            self::Dotted => '1 1.6',
            default => null,
        };
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
