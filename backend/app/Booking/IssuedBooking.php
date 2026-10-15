<?php

namespace App\Booking;

use App\Models\Booking;

/** A new booking together with its manage-link token, which exists in plain text only now. */
final readonly class IssuedBooking
{
    public function __construct(
        public Booking $booking,
        public string $manageToken,
    ) {}
}
