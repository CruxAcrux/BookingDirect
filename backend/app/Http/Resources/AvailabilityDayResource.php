<?php

namespace App\Http\Resources;

use App\Booking\Availability\Slot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{date: string, slots: list<Slot>} $resource
 */
class AvailabilityDayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /** Local date, e.g. 2026-11-03. */
            'date' => $this->resource['date'],
            'slots' => SlotResource::collection($this->resource['slots']),
        ];
    }
}
