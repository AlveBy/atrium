<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Enums;

/**
 * How much experience the job asks for.
 */
enum Experience: string
{
    case None = 'none';
    case OneToThree = '1-3';
    case ThreeToSix = '3-6';
    case MoreThanSix = '6+';

    public function label(): string
    {
        return __('atrium-jobs::jobs.experience.'.$this->value);
    }

    /**
     * The lower bound in months, for schema.org monthsOfExperience.
     */
    public function months(): int
    {
        return match ($this) {
            self::None => 0,
            self::OneToThree => 12,
            self::ThreeToSix => 36,
            self::MoreThanSix => 72,
        };
    }

    /**
     * @return array<string,string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $case): array => [$case->value, $case->label()], self::cases()), 1, 0);
    }
}
