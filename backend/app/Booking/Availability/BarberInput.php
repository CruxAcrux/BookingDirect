<?php

namespace App\Booking\Availability;

/** One barber's schedule and commitments, for the service being booked. */
final readonly class BarberInput
{
    /**
     * @param  list<WeeklyRange>  $workingHours
     * @param  list<WeeklyRange>  $breaks
     * @param  list<Period>  $blocked  time off and existing bookings (including their buffers)
     */
    public function __construct(
        public int $barberId,
        public array $workingHours,
        public array $breaks,
        public array $blocked,
        public int $durationMinutes,
        public int $bufferMinutes,
    ) {}
}
