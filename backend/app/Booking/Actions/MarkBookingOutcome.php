<?php

namespace App\Booking\Actions;

use App\Booking\Exceptions\BookingLockedException;
use App\Enums\BookingStatus;
use App\Models\Booking;

class MarkBookingOutcome
{
    /**
     * Records how a visit went, once it has started. Marking can be corrected later
     * (completed ↔ no-show), but cancelled bookings stay cancelled.
     *
     * @throws BookingLockedException
     */
    public function handle(Booking $booking, BookingStatus $outcome): Booking
    {
        if (! in_array($outcome, [BookingStatus::Completed, BookingStatus::NoShow], true)) {
            throw new BookingLockedException('booking.invalid_outcome');
        }

        if ($booking->status === BookingStatus::Cancelled || $booking->status === BookingStatus::Pending) {
            throw new BookingLockedException('booking.not_active');
        }

        if ($booking->starts_at->isFuture()) {
            throw new BookingLockedException('booking.not_started');
        }

        $booking->status = $outcome;
        $booking->save();

        return $booking;
    }
}
