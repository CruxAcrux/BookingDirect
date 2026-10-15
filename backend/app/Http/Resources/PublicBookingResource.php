<?php

namespace App\Http\Resources;

use App\Models\Booking;
use App\Models\Shop;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A booking as its customer sees it.
 *
 * @mixin Booking
 */
class PublicBookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Shop $shop */
        $shop = app(CurrentShop::class)->getOrFail();
        $deadline = $this->starts_at->copy()->subHours($shop->cancellation_window_hours);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'starts_at' => $this->starts_at->toIso8601ZuluString(),
            'ends_at' => $this->ends_at->toIso8601ZuluString(),
            /** Local time in the shop's timezone, e.g. "2026-11-03T09:15". */
            'starts_at_local' => $this->starts_at->copy()->setTimezone($shop->timezone)->format('Y-m-d\TH:i'),
            'ends_at_local' => $this->ends_at->copy()->setTimezone($shop->timezone)->format('Y-m-d\TH:i'),
            'duration_minutes' => $this->duration_minutes,
            'price_ore' => $this->price_ore,
            'note' => $this->customer_note,
            'service' => ['id' => $this->service->id, 'name' => $this->service->name],
            'barber' => ['id' => $this->barber->id, 'display_name' => $this->barber->display_name],
            'shop' => [
                'name' => $shop->name,
                'slug' => $shop->slug,
                /** @var string|null */
                'address' => $shop->address,
                /** @var string|null */
                'phone' => $shop->phone,
                'timezone' => $shop->timezone,
            ],
            /**
             * Whether the customer can still cancel or move the booking online.
             *
             * @var bool
             */
            'can_change' => $this->status->blocksTime() && now()->lessThanOrEqualTo($deadline),
            /** The last moment the customer can cancel or move online. */
            'change_deadline' => $deadline->toIso8601ZuluString(),
        ];
    }
}
