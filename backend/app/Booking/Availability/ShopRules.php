<?php

namespace App\Booking\Availability;

final readonly class ShopRules
{
    /**
     * @param  list<WeeklyRange>  $openingHours
     */
    public function __construct(
        public string $timezone,
        public array $openingHours,
        public int $slotIntervalMinutes,
        public int $minNoticeMinutes,
        public int $maxDaysAhead,
    ) {}
}
