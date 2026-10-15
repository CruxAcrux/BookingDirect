<?php

namespace App\Http\Controllers\Api\V1\Barbers;

use App\Actions\Schedule\ReplaceWeeklyHours;
use App\Http\Controllers\Controller;
use App\Http\Requests\WeeklyHoursRequest;
use App\Http\Resources\WeeklyHourResource;
use App\Models\Barber;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Weekly working hours and breaks. Times are local (HH:MM) in the shop's timezone.
 */
class BarberScheduleController extends Controller
{
    /**
     * A barber's weekly working hours.
     */
    public function workingHours(Barber $barber): AnonymousResourceCollection
    {
        Gate::authorize('view', $barber);

        return WeeklyHourResource::collection($barber->workingHours()->orderBy('weekday')->orderBy('starts_at')->get());
    }

    /**
     * Replace a barber's weekly working hours.
     *
     * Hours outside the shop's opening hours are allowed; only the overlap can be booked.
     */
    public function updateWorkingHours(WeeklyHoursRequest $request, Barber $barber, ReplaceWeeklyHours $replace): AnonymousResourceCollection
    {
        Gate::authorize('manage', $barber);

        return WeeklyHourResource::collection($replace->handle($barber->workingHours(), $request->ranges(), 'starts_at', 'ends_at'));
    }

    /**
     * A barber's weekly breaks.
     */
    public function breaks(Barber $barber): AnonymousResourceCollection
    {
        Gate::authorize('view', $barber);

        return WeeklyHourResource::collection($barber->breaks()->orderBy('weekday')->orderBy('starts_at')->get());
    }

    /**
     * Replace a barber's weekly breaks.
     */
    public function updateBreaks(WeeklyHoursRequest $request, Barber $barber, ReplaceWeeklyHours $replace): AnonymousResourceCollection
    {
        Gate::authorize('manage', $barber);

        return WeeklyHourResource::collection($replace->handle($barber->breaks(), $request->ranges(), 'starts_at', 'ends_at'));
    }
}
