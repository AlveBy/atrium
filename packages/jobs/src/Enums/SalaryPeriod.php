<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Jobs\Enums;

/**
 * What the salary is paid for, with its schema.org unitText.
 */
enum SalaryPeriod: string
{
    case Hour = 'hour';
    case Month = 'month';
    case Year = 'year';

    public function label(): string
    {
        return __('atrium-jobs::jobs.salary_periods.'.$this->value);
    }

    public function schema(): string
    {
        return strtoupper($this->value);
    }

    /**
     * @return array<string,string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $case): array => [$case->value, $case->label()], self::cases()), 1, 0);
    }
}
