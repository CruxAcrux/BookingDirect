<?php

namespace App\Http\Controllers\Api\V1\Calendar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\CalendarRequest;
use App\Http\Resources\CalendarBarberResource;
use App\Http\Resources\WeeklyHourResource;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\User;
use App\Tenancy\CurrentShop;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CalendarController extends Controller
{
    public function __construct(private readonly CurrentShop $currentShop) {}

    /**
     * Schedules for the calendar grid.
     *
     * Opening hours plus, for every barber column, working hours, breaks and time off within
     * the range. Owners get every active barber (and inactive ones who still have bookings
     * in the range); barbers get only themselves. Bookings come from `GET /bookings`.
     */
    public function show(CalendarRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Booking::class);

        /** @var User $user */
        $user = $request->user();
        $shop = $this->currentShop->getOrFail();
        $from = CarbonImmutable::parse($request->string('from')->value(), $shop->timezone)->startOfDay()->utc();
        $to = CarbonImmutable::parse($request->string('to')->value(), $shop->timezone)->addDay()->startOfDay()->utc();

        $barbers = Barber::query()
            ->when(
                $user->isOwnerOf($shop),
                fn ($query) => $query->where(fn ($inner) => $inner
                    ->where('is_active', true)
                    ->orWhereHas('bookings', fn ($bookings) => $bookings->where('starts_at', '<', $to)->where('ends_at', '>', $from))),
                fn ($query) => $query->whereKey($user->barberProfileId() ?? 0),
            )
            ->with([
                'workingHours' => fn ($query) => $query->orderBy('weekday')->orderBy('starts_at'),
                'breaks' => fn ($query) => $query->orderBy('weekday')->orderBy('starts_at'),
                'timeOffs' => fn ($query) => $query->where('starts_at', '<', $to)->where('ends_at', '>', $from)->orderBy('starts_at'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => [
            'timezone' => $shop->timezone,
            'opening_hours' => WeeklyHourResource::collection($shop->openingHours()->orderBy('weekday')->orderBy('opens_at')->get()),
            'barbers' => CalendarBarberResource::collection($barbers),
        ]]);
    }
}
