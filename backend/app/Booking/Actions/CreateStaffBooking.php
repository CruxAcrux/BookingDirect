<?php

namespace App\Booking\Actions;

use App\Booking\Availability\AvailabilityService;
use App\Booking\BookingWriter;
use App\Booking\Exceptions\SlotTakenException;
use App\Booking\IssuedBooking;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use Carbon\CarbonImmutable;

class CreateStaffBooking
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingWriter $writer,
    ) {}

    /**
     * A booking made by staff, e.g. over the phone. Staff may book outside opening hours
     * or without notice; only double-booking (enforced by the database) is refused.
     *
     * @throws SlotTakenException
     */
    public function handle(Customer $customer, Service $service, Barber $barber, CarbonImmutable $startsAt, ?string $staffNote): IssuedBooking
    {
        $barber->loadMissing(['services' => fn ($query) => $query->whereKey($service->id)]);
        $duration = $this->availability->durationFor($barber, $service);
        $endsAt = $startsAt->addMinutes($duration);
        $token = '';

        $booking = $this->writer->write(function () use ($customer, $service, $barber, $startsAt, $endsAt, $duration, $staffNote, &$token): Booking {
            $booking = new Booking([
                'barber_id' => $barber->id,
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'blocked_until' => $endsAt->addMinutes($service->buffer_minutes),
                'status' => BookingStatus::Confirmed,
                'source' => BookingSource::Staff,
                'price_ore' => $this->availability->priceFor($barber, $service),
                'duration_minutes' => $duration,
                'buffer_minutes' => $service->buffer_minutes,
                'staff_note' => $staffNote,
            ]);
            $token = $booking->issueManageToken();
            $booking->save();

            return $booking;
        });

        return new IssuedBooking($booking, $token);
    }
}
