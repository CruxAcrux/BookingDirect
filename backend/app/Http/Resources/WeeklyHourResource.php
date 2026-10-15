<?php

namespace App\Http\Resources;

use App\Models\BreakPeriod;
use App\Models\OpeningHour;
use App\Models\WorkingHour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One time range in a weekly schedule. Times are local (HH:MM) in the shop's timezone.
 *
 * @property OpeningHour|WorkingHour|BreakPeriod $resource
 */
class WeeklyHourResource extends JsonResource
{
    /**
     * @return array{weekday: int, starts_at: string, ends_at: string}
     */
    public function toArray(Request $request): array
    {
        $model = $this->resource;
        [$start, $end] = $model instanceof OpeningHour
            ? [$model->opens_at, $model->closes_at]
            : [$model->starts_at, $model->ends_at];

        return [
            /** @var int */
            'weekday' => $model->weekday->value,
            'starts_at' => substr($start, 0, 5),
            'ends_at' => substr($end, 0, 5),
        ];
    }
}
