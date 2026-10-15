<?php

namespace App\Booking\Availability;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\BreakPeriod;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\Shop;
use App\Models\TimeOff;
use App\Models\WorkingHour;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Loads a shop's schedules and bookings and asks the calculator for free slots.
 */
class AvailabilityService
{
    public function __construct(private readonly AvailabilityCalculator $calculator) {}

    /**
     * @param  Barber|null  $barber  null means any barber who offers the service
     * @param  int|null  $ignoreBookingId  a booking being moved, which should not block itself
     * @return list<Slot>
     */
    public function slots(
        Shop $shop,
        Service $service,
        ?Barber $barber,
        string $fromDate,
        string $toDate,
        ?int $ignoreBookingId = null,
    ): array {
        $barbers = $this->barbersFor($service, $barber);

        if ($barbers->isEmpty() || ! $service->is_active) {
            return [];
        }

        // One day of padding on each side covers bookings and time off that cross local midnight.
        $windowStart = CarbonImmutable::parse($fromDate, $shop->timezone)->startOfDay()->subDay()->utc();
        $windowEnd = CarbonImmutable::parse($toDate, $shop->timezone)->startOfDay()->addDays(2)->utc();
        $ids = $barbers->modelKeys();

        $workingHours = WorkingHour::whereIn('barber_id', $ids)->get()->groupBy('barber_id');
        $breaks = BreakPeriod::whereIn('barber_id', $ids)->get()->groupBy('barber_id');
        $timeOff = TimeOff::whereIn('barber_id', $ids)
            ->where('starts_at', '<', $windowEnd)
            ->where('ends_at', '>', $windowStart)
            ->get()
            ->groupBy('barber_id');
        $bookings = Booking::whereIn('barber_id', $ids)
            ->blockingTime()
            ->when($ignoreBookingId, fn ($query) => $query->whereKeyNot($ignoreBookingId))
            ->where('starts_at', '<', $windowEnd)
            ->where('blocked_until', '>', $windowStart)
            ->get()
            ->groupBy('barber_id');

        $inputs = $barbers->map(fn (Barber $barber): BarberInput => new BarberInput(
            barberId: $barber->id,
            workingHours: $this->weekly($workingHours->get($barber->id)),
            breaks: $this->weekly($breaks->get($barber->id)),
            blocked: array_values([
                ...$timeOff->get($barber->id, collect())->map(fn (TimeOff $off) => new Period($off->starts_at->toImmutable(), $off->ends_at->toImmutable())),
                ...$bookings->get($barber->id, collect())->map(fn (Booking $booking) => new Period($booking->starts_at->toImmutable(), $booking->blocked_until->toImmutable())),
            ]),
            durationMinutes: $this->durationFor($barber, $service),
            bufferMinutes: $service->buffer_minutes,
        ))->all();

        return $this->calculator->slots(
            $this->rules($shop),
            array_values($inputs),
            $fromDate,
            $toDate,
            CarbonImmutable::now(),
        );
    }

    /**
     * The barbers who could be booked for a service, in team order.
     *
     * @return Collection<int, Barber>
     */
    public function barbersFor(Service $service, ?Barber $barber = null): Collection
    {
        return Barber::query()
            ->where('is_active', true)
            ->whereHas('services', fn ($query) => $query->whereKey($service->id))
            ->when($barber, fn ($query, Barber $barber) => $query->whereKey($barber->id))
            ->with(['services' => fn ($query) => $query->whereKey($service->id)])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /** The barber's own duration for the service, or the service's default. */
    public function durationFor(Barber $barber, Service $service): int
    {
        $override = $barber->services->firstWhere('id', $service->id)?->getRelationValue('pivot')?->getAttribute('duration_minutes');

        return is_numeric($override) ? (int) $override : $service->duration_minutes;
    }

    /** The barber's own price for the service, or the service's default. */
    public function priceFor(Barber $barber, Service $service): int
    {
        $override = $barber->services->firstWhere('id', $service->id)?->getRelationValue('pivot')?->getAttribute('price_ore');

        return is_numeric($override) ? (int) $override : $service->price_ore;
    }

    public function rules(Shop $shop): ShopRules
    {
        return new ShopRules(
            timezone: $shop->timezone,
            openingHours: $this->weekly(OpeningHour::where('shop_id', $shop->id)->get()),
            slotIntervalMinutes: $shop->slot_interval_minutes,
            minNoticeMinutes: $shop->min_notice_minutes,
            maxDaysAhead: $shop->max_days_ahead,
        );
    }

    /**
     * @param  iterable<OpeningHour|WorkingHour|BreakPeriod>|null  $hours
     * @return list<WeeklyRange>
     */
    private function weekly(?iterable $hours): array
    {
        $ranges = [];

        foreach ($hours ?? [] as $hour) {
            [$start, $end] = $hour instanceof OpeningHour
                ? [$hour->opens_at, $hour->closes_at]
                : [$hour->starts_at, $hour->ends_at];

            $ranges[] = new WeeklyRange($hour->weekday->value, $start, $end);
        }

        return $ranges;
    }
}
