<?php

namespace App\Http\Resources;

use App\Media\ImageStore;
use App\Models\Barber;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A barber column in the calendar: who it is and when they are not available.
 *
 * @mixin Barber
 */
class CalendarBarberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'display_name' => $this->display_name,
            /** @var string|null */
            'avatar_url' => app(ImageStore::class)->url($this->avatar_path),
            'is_active' => $this->is_active,
            'working_hours' => WeeklyHourResource::collection($this->workingHours),
            'breaks' => WeeklyHourResource::collection($this->breaks),
            /** Time off overlapping the requested range. */
            'time_off' => TimeOffResource::collection($this->timeOffs),
        ];
    }
}
