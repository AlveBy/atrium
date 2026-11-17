<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Enums;

/**
 * Where an application is in the hiring pipeline, in the pipeline's order.
 */
enum ApplicationStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Interview = 'interview';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('atrium-jobs::jobs.statuses.'.$this->value);
    }

    /**
     * @return array<string,string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $case): array => [$case->value, $case->label()], self::cases()), 1, 0);
    }
}
