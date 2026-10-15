<?php

namespace App\Http\Resources;

use App\Booking\Availability\Slot;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A free start time. `starts_at` is the exact instant to book; `date` and `time` are the
 * same moment in the shop's local time, for display.
 *
 * @property Slot $resource
 */
class SlotResource extends JsonResource
{
    /**
     * @return array{starts_at: string, date: string, time: string, barber_id: int}
     */
    public function toArray(Request $request): array
    {
        $local = $this->resource->startsAt->setTimezone(app(CurrentShop::class)->getOrFail()->timezone);

        return [
            'starts_at' => $this->resource->startsAt->toIso8601ZuluString(),
            'date' => $local->toDateString(),
            'time' => $local->format('H:i'),
            /** The barber who would be booked: the first free one in team order. */
            'barber_id' => $this->resource->barberId,
        ];
    }
}
