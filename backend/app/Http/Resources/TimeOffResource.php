<?php

namespace App\Http\Resources;

use App\Models\TimeOff;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TimeOff
 */
class TimeOffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezone = app(CurrentShop::class)->getOrFail()->timezone;
        $start = $this->starts_at->copy()->setTimezone($timezone);
        $end = $this->ends_at->copy()->setTimezone($timezone);

        return [
            'id' => $this->id,
            'barber_id' => $this->barber_id,
            'starts_at' => $this->starts_at->toIso8601ZuluString(),
            'ends_at' => $this->ends_at->toIso8601ZuluString(),
            /** Local time in the shop's timezone, e.g. "2026-11-03T09:00". */
            'starts_at_local' => $start->format('Y-m-d\TH:i'),
            'ends_at_local' => $end->format('Y-m-d\TH:i'),
            'all_day' => $start->format('H:i') === '00:00' && $end->format('H:i') === '00:00',
            'reason' => $this->reason,
            'note' => $this->note,
        ];
    }
}
