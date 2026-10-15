<?php

namespace App\Booking\Actions;

use App\Booking\Availability\AvailabilityService;
use App\Booking\Availability\Slot;
use App\Booking\BookingWriter;
use App\Booking\Exceptions\SlotTakenException;
use App\Booking\IssuedBooking;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Shop;
use App\Notifications\Booking\BookingConfirmed;
use Carbon\CarbonImmutable;

class CreateOnlineBooking
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingWriter $writer,
    ) {}

    /**
     * Books a slot for a verified customer. With no barber chosen, the first barber in
     * team order who is free at that time gets it; if two customers race for the same
     * barber, the next free barber is tried before giving up.
     *
     * @throws SlotTakenException
     */
    public function handle(Shop $shop, Customer $customer, Service $service, ?Barber $barber, CarbonImmutable $startsAt, ?string $note): IssuedBooking
    {
        $date = $startsAt->setTimezone($shop->timezone)->toDateString();
        $candidates = array_values(array_filter(
            $this->availability->slots($shop, $service, $barber, $date, $date),
            fn (Slot $slot): bool => $slot->startsAt->equalTo($startsAt),
        ));

        $barbers = $this->availability->barbersFor($service, $barber)->keyBy('id');

        foreach ($candidates as $slot) {
            $chosen = $barbers->get($slot->barberId);
            if ($chosen === null) {
                continue;
            }

            try {
                return $this->book($customer, $service, $chosen, $slot, $note);
            } catch (SlotTakenException) {
                continue;
            }
        }

        throw new SlotTakenException;
    }

    private function book(Customer $customer, Service $service, Barber $barber, Slot $slot, ?string $note): IssuedBooking
    {
        $token = '';

        $booking = $this->writer->write(function () use ($customer, $service, $barber, $slot, $note, &$token): Booking {
            $booking = new Booking([
                'barber_id' => $barber->id,
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'starts_at' => $slot->startsAt,
                'ends_at' => $slot->endsAt,
                'blocked_until' => $slot->endsAt->addMinutes($service->buffer_minutes),
                'status' => BookingStatus::Confirmed,
                'source' => BookingSource::Online,
                'price_ore' => $this->availability->priceFor($barber, $service),
                'duration_minutes' => $this->availability->durationFor($barber, $service),
                'buffer_minutes' => $service->buffer_minutes,
                'customer_note' => $note,
            ]);
            $token = $booking->issueManageToken();
            $booking->save();

            return $booking;
        });

        $customer->notify(new BookingConfirmed($booking));

        return new IssuedBooking($booking, $token);
    }
}
