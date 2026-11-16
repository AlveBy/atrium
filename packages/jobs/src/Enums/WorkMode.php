<?php

declare(strict_types=1);

namespace Atrium\Jobs\Enums;

/**
 * Where the work is done: at the office, partly from home, or from anywhere.
 */
enum WorkMode: string
{
    case Office = 'office';
    case Hybrid = 'hybrid';
    case Remote = 'remote';

    public function label(): string
    {
        return __('atrium-jobs::jobs.work_modes.'.$this->value);
    }

    /**
     * @return array<string,string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $case): array => [$case->value, $case->label()], self::cases()), 1, 0);
    }
}
