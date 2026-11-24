<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Tracker\Enums;

/**
 * Lowest to highest. The label always carries an arrow as well as the word,
 * so the priority is not told by colour.
 */
enum Priority: string
{
    case Lowest = 'lowest';
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Highest = 'highest';

    public function label(): string
    {
        return $this->symbol().' '.__('atrium-tracker::tracker.priorities.'.$this->value);
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Lowest => '⇣',
            self::Low => '↓',
            self::Medium => '→',
            self::High => '↑',
            self::Highest => '⇡',
        };
    }

    /**
     * @return array<string,string> value => label
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
