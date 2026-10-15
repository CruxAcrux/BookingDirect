<?php

namespace App\Http\Resources;

use App\Models\Booking;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A booking as staff see it.
 *
 * @mixin Booking
 */
class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezone = app(CurrentShop::class)->getOrFail()->timezone;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'source' => $this->source,
            'barber_id' => $this->barber_id,
            'starts_at' => $this->starts_at->toIso8601ZuluString(),
            'ends_at' => $this->ends_at->toIso8601ZuluString(),
            /** End of the cleanup buffer: the barber is busy until then. */
            'blocked_until' => $this->blocked_until->toIso8601ZuluString(),
            'starts_at_local' => $this->starts_at->copy()->setTimezone($timezone)->format('Y-m-d\TH:i'),
            'ends_at_local' => $this->ends_at->copy()->setTimezone($timezone)->format('Y-m-d\TH:i'),
            'duration_minutes' => $this->duration_minutes,
            'buffer_minutes' => $this->buffer_minutes,
            'price_ore' => $this->price_ore,
            'customer_note' => $this->customer_note,
            'staff_note' => $this->staff_note,
            'service' => ['id' => $this->service->id, 'name' => $this->service->name],
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'email' => $this->customer->email,
            ],
            /** @var string|null */
            'cancelled_at' => $this->cancelled_at?->toIso8601ZuluString(),
            'cancelled_by' => $this->cancelled_by,
            'cancellation_message' => $this->cancellation_message,
        ];
    }
}
