<?php

namespace App\Booking\Availability;

/** A recurring local wall-clock range, e.g. Monday 10:00–19:00. */
final readonly class WeeklyRange
{
    public function __construct(
        /** ISO weekday, 1 = Monday. */
        public int $weekday,
        /** "HH:MM" or "HH:MM:SS", local time. */
        public string $start,
        public string $end,
    ) {}
}
