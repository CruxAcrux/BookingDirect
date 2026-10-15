<?php

namespace App\Booking\Actions;

use App\Booking\Availability\AvailabilityService;
use App\Booking\Availability\Slot;
use App\Booking\BookingWriter;
use App\Booking\Exceptions\BookingLockedException;
use App\Booking\Exceptions\SlotTakenException;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Shop;
use App\Notifications\Booking\BookingRescheduled;
use Carbon\CarbonImmutable;

class RescheduleBooking
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingWriter $writer,
    ) {}

    /**
     * A customer moving their own booking: allowed until the cancellation window closes,
     * and only to a time the normal booking rules would offer.
     *
     * @throws BookingLockedException|SlotTakenException
     */
    public function byCustomer(Shop $shop, Booking $booking, CarbonImmutable $startsAt, ?Barber $barber): Booking
    {
        $this->ensureCustomerCanChange($shop, $booking);

        $service = $booking->service;
        $date = $startsAt->setTimezone($shop->timezone)->toDateString();
        $slots = array_values(array_filter(
            $this->availability->slots($shop, $service, $barber, $date, $date, ignoreBookingId: $booking->id),
            fn (Slot $slot): bool => $slot->startsAt->equalTo($startsAt),
        ));

        $barbers = $this->availability->barbersFor($service, $barber)->keyBy('id');

        foreach ($slots as $slot) {
            $chosen = $barbers->get($slot->barberId);
            if ($chosen === null) {
                continue;
            }

            try {
                return $this->move($booking, $chosen, $startsAt, $this->availability->durationFor($chosen, $service), $this->availability->priceFor($chosen, $service));
            } catch (SlotTakenException) {
                continue;
            }
        }

        throw new SlotTakenException;
    }

    /**
     * Staff moving a booking, e.g. by dragging it in the calendar. Opening hours and notice
     * do not apply; overlapping another booking still does.
     *
     * @throws BookingLockedException|SlotTakenException
     */
    public function byStaff(Booking $booking, CarbonImmutable $startsAt, Barber $barber): Booking
    {
        if (! $booking->status->blocksTime()) {
            throw new BookingLockedException('booking.not_active');
        }

        if ($startsAt->isPast()) {
            throw new BookingLockedException('booking.in_past');
        }

        $barber->loadMissing(['services' => fn ($query) => $query->whereKey($booking->service_id)]);
        $keepsBarber = $barber->id === $booking->barber_id;

        return $this->move(
            $booking,
            $barber,
            $startsAt,
            $keepsBarber ? $booking->duration_minutes : $this->availability->durationFor($barber, $booking->service),
            $keepsBarber ? $booking->price_ore : $this->availability->priceFor($barber, $booking->service),
        );
    }

    public function ensureCustomerCanChange(Shop $shop, Booking $booking): void
    {
        if (! $booking->status->blocksTime()) {
            throw new BookingLockedException('booking.not_active');
        }

        if (now()->greaterThan($booking->starts_at->copy()->subHours($shop->cancellation_window_hours))) {
            throw new BookingLockedException('booking.too_late');
        }
    }

    private function move(Booking $booking, Barber $barber, CarbonImmutable $startsAt, int $duration, int $price): Booking
    {
        $booking = $this->writer->write(function () use ($booking, $barber, $startsAt, $duration, $price): Booking {
            $endsAt = $startsAt->addMinutes($duration);

            $booking->fill([
                'barber_id' => $barber->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'blocked_until' => $endsAt->addMinutes($booking->buffer_minutes),
                'duration_minutes' => $duration,
                'price_ore' => $price,
            ]);
            $booking->markRescheduled();
            $booking->save();

            return $booking;
        });

        $booking->customer->notify(new BookingRescheduled($booking));

        return $booking;
    }
}
