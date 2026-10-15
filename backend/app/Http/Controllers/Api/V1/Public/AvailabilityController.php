<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Booking\Availability\AvailabilityService;
use App\Booking\Availability\Slot;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\AvailabilityRequest;
use App\Http\Requests\Public\NextAvailableRequest;
use App\Http\Resources\AvailabilityDayResource;
use App\Http\Resources\SlotResource;
use App\Models\Barber;
use App\Models\Service;
use App\Models\Shop;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvailabilityController extends Controller
{
    private const NEXT_SEARCH_CHUNK_DAYS = 14;

    public function __construct(private readonly AvailabilityService $availability) {}

    /**
     * Free start times per local date, for up to 14 days.
     *
     * Every date in the range is listed, also those without free times. When several barbers
     * are free at the same time, the slot names the one that would be booked: the first in
     * team order.
     *
     * @unauthenticated
     */
    public function index(AvailabilityRequest $request, Shop $shop): AnonymousResourceCollection
    {
        [$service, $barber] = $this->subject($request->integer('service_id'), $request->input('barber_id'));
        $from = $request->string('from')->value();
        $to = $request->string('to')->value();

        $byDate = [];
        foreach ($this->firstPerTime($this->availability->slots($shop, $service, $barber, $from, $to)) as $slot) {
            $byDate[$slot->startsAt->setTimezone($shop->timezone)->toDateString()][] = $slot;
        }

        $days = [];
        for ($date = CarbonImmutable::parse($from); $date->toDateString() <= $to; $date = $date->addDay()) {
            $days[] = ['date' => $date->toDateString(), 'slots' => $byDate[$date->toDateString()] ?? []];
        }

        return AvailabilityDayResource::collection($days);
    }

    /**
     * The earliest free time, or null when nothing is free within the booking horizon.
     *
     * @unauthenticated
     */
    public function next(NextAvailableRequest $request, Shop $shop): SlotResource|JsonResponse
    {
        [$service, $barber] = $this->subject($request->integer('service_id'), $request->input('barber_id'));
        $today = CarbonImmutable::now($shop->timezone)->startOfDay();
        $horizon = $today->addDays($shop->max_days_ahead);

        for ($from = $today; $from <= $horizon; $from = $from->addDays(self::NEXT_SEARCH_CHUNK_DAYS)) {
            $to = min($from->addDays(self::NEXT_SEARCH_CHUNK_DAYS - 1), $horizon);
            $slots = $this->availability->slots($shop, $service, $barber, $from->toDateString(), $to->toDateString());

            if ($slots !== []) {
                return new SlotResource($slots[0]);
            }
        }

        return response()->json(['data' => null]);
    }

    /**
     * @return array{Service, Barber|null}
     */
    private function subject(int $serviceId, mixed $barberId): array
    {
        return [
            Service::findOrFail($serviceId),
            is_numeric($barberId) ? Barber::findOrFail((int) $barberId) : null,
        ];
    }

    /**
     * Slots arrive sorted by time and then team order, so the first per time is the barber
     * who would get the booking.
     *
     * @param  list<Slot>  $slots
     * @return list<Slot>
     */
    private function firstPerTime(array $slots): array
    {
        $seen = [];

        return array_values(array_filter($slots, function (Slot $slot) use (&$seen): bool {
            $key = $slot->startsAt->getTimestamp();
            if (isset($seen[$key])) {
                return false;
            }

            return $seen[$key] = true;
        }));
    }
}
