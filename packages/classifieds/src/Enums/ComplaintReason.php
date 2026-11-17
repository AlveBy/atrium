<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Classifieds\Enums;

enum ComplaintReason: string
{
    case Fraud = 'fraud';
    case Sold = 'sold';
    case WrongCategory = 'wrong_category';
    case Prohibited = 'prohibited';
    case Duplicate = 'duplicate';
    case Other = 'other';

    public function label(): string
    {
        return __('atrium-classifieds::classifieds.complaint.reasons.'.$this->value);
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
