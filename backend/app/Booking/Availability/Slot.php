<?php

namespace App\Booking\Availability;

use Carbon\CarbonImmutable;

final readonly class Slot
{
    public function __construct(
        public int $barberId,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
    ) {}
}
