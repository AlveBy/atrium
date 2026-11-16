<?php

declare(strict_types=1);

namespace Atrium\Jobs\Enums;

/**
 * The kind of contract, with its schema.org JobPosting employmentType.
 */
enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Temporary = 'temporary';
    case Internship = 'internship';

    public function label(): string
    {
        return __('atrium-jobs::jobs.employment_types.'.$this->value);
    }

    public function schema(): string
    {
        return match ($this) {
            self::FullTime => 'FULL_TIME',
            self::PartTime => 'PART_TIME',
            self::Contract => 'CONTRACTOR',
            self::Temporary => 'TEMPORARY',
            self::Internship => 'INTERN',
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
