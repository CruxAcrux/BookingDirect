<?php

namespace App\Booking\Availability;

use Carbon\CarbonImmutable;

/** A half-open span of absolute time: [start, end). */
final readonly class Period
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {}

    public function overlaps(CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $start < $this->end && $end > $this->start;
    }

    public function contains(CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $start >= $this->start && $end <= $this->end;
    }
}
