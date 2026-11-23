<?php

namespace App\Care;

use Carbon\CarbonImmutable;

class Planning
{
    public const ZONE = 'Europe/Amsterdam';

    public function scheduledAt(object $assignment, string $day): ?CarbonImmutable
    {
        if ($day < $assignment->starts_on || ($assignment->ends_on && $day > $assignment->ends_on)) {
            return null;
        }

        $date = CarbonImmutable::parse($day, self::ZONE);
        $start = CarbonImmutable::parse($assignment->starts_on, self::ZONE);
        $occurs = match ($assignment->repeat) {
            'once' => $date->equalTo($start),
            'daily' => true,
            'alternate' => (int) $start->diffInDays($date) % 2 === 0,
            'weekly' => $date->dayOfWeek === $start->dayOfWeek,
            default => false,
        };

        return $occurs ? CarbonImmutable::parse("$day {$assignment->time}", self::ZONE)->utc() : null;
    }
}
