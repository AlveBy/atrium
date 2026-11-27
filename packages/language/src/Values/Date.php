<?php

/*
 * This file is part of Atrium (https://github.com/AlveBy/atrium).
 * Copyright (c) 2026 AlveBy. Licensed under the MIT License, see LICENSE.
 */

declare(strict_types=1);

namespace Atrium\Language\Values;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Stringable;

/** A calendar day, from year 1 to year 9999, with no time and no time zone. */
final readonly class Date implements Stringable
{
    private function __construct(private DateTimeImmutable $day) {}

    /**
     * Like Excel, a month or a day outside its range rolls over: month 13 is
     * January of the next year. Null when the result is outside the years.
     */
    public static function of(int $year, int $month, int $day): ?self
    {
        return self::bounded(
            (new DateTimeImmutable('2000-01-01', new DateTimeZone('UTC')))->setDate($year, $month, $day),
        );
    }

    /** A real day written as `2026-11-28`, or null for any other text. */
    public static function parse(string $text): ?self
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\z/', $text, $parts) !== 1) {
            return null;
        }

        [, $year, $month, $day] = array_map(intval(...), $parts);

        return checkdate($month, $day, $year) ? self::of($year, $month, $day) : null;
    }

    /** The calendar day of a moment, in the moment's own time zone. */
    public static function from(DateTimeInterface $moment): ?self
    {
        return self::parse($moment->format('Y-m-d'));
    }

    public function year(): int
    {
        return (int) $this->day->format('Y');
    }

    public function month(): int
    {
        return (int) $this->day->format('n');
    }

    public function day(): int
    {
        return (int) $this->day->format('j');
    }

    /** 1 for Monday to 7 for Sunday. */
    public function weekday(): int
    {
        return (int) $this->day->format('N');
    }

    public function addDays(int $days): ?self
    {
        return self::bounded($this->day->modify(sprintf('%+d days', $days)));
    }

    /** The same day some months away, or the last day of a shorter month. */
    public function addMonths(int $months): ?self
    {
        $first = $this->firstOfMonth($months);

        return self::bounded($first->setDate(
            (int) $first->format('Y'),
            (int) $first->format('n'),
            min($this->day(), (int) $first->format('t')),
        ));
    }

    /** The last day of the month some months away. */
    public function endOfMonth(int $months): ?self
    {
        return self::bounded($this->firstOfMonth($months)->modify('last day of this month'));
    }

    /** How many days later the other date is; negative when it is earlier. */
    public function daysUntil(Date $other): int
    {
        return (int) $this->day->diff($other->day)->format('%r%a');
    }

    public function __toString(): string
    {
        return $this->day->format('Y-m-d');
    }

    private function firstOfMonth(int $months): DateTimeImmutable
    {
        return $this->day->modify('first day of this month')->modify(sprintf('%+d months', $months));
    }

    private static function bounded(DateTimeImmutable $day): ?self
    {
        $year = $day->format('Y');

        return $year >= 1 && $year <= 9999 ? new self($day) : null;
    }
}
