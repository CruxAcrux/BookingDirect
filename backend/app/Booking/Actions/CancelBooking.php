<?php

namespace App\Booking\Actions;

use App\Booking\Exceptions\BookingLockedException;
use App\Enums\BookingStatus;
use App\Enums\CancelledBy;
use App\Models\Booking;
use App\Models\Shop;

class CancelBooking
{
    public function __construct(private readonly RescheduleBooking $rules) {}

    /**
     * @throws BookingLockedException when the cancellation window has closed
     */
    public function byCustomer(Shop $shop, Booking $booking): Booking
    {
        $this->rules->ensureCustomerCanChange($shop, $booking);

        return $this->cancel($booking, CancelledBy::Customer, null);
    }

    /**
     * Staff can cancel at any time, optionally with a message for the customer.
     *
     * @throws BookingLockedException when the booking is not active
     */
    public function byStaff(Booking $booking, ?string $message): Booking
    {
        if (! $booking->status->blocksTime()) {
            throw new BookingLockedException('booking.not_active');
        }

        return $this->cancel($booking, CancelledBy::Staff, $message);
    }

    private function cancel(Booking $booking, CancelledBy $by, ?string $message): Booking
    {
        $booking->forceFill([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $by,
            'cancellation_message' => $message,
        ])->save();

        return $booking;
    }
}
