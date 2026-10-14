<?php

namespace App\Http\Controllers\Api\V1\Barbers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Barbers\TimeOffRequest;
use App\Http\Resources\TimeOffResource;
use App\Models\Barber;
use App\Models\TimeOff;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class TimeOffController extends Controller
{
    /**
     * A barber's time off, from 30 days ago onwards.
     */
    public function index(Barber $barber): AnonymousResourceCollection
    {
        Gate::authorize('view', $barber);

        return TimeOffResource::collection(
            $barber->timeOffs()->where('ends_at', '>=', now()->subDays(30))->orderBy('starts_at')->get(),
        );
    }

    /**
     * Add time off.
     *
     * Takes effect immediately. Barbers can add their own; owners can add for anyone.
     */
    public function store(TimeOffRequest $request, Barber $barber): TimeOffResource
    {
        Gate::authorize('manageTimeOff', $barber);

        return new TimeOffResource($barber->timeOffs()->create($request->attributesForModel()));
    }

    /**
     * Update time off.
     */
    public function update(TimeOffRequest $request, Barber $barber, TimeOff $timeOff): TimeOffResource
    {
        Gate::authorize('manageTimeOff', $barber);

        $timeOff->update($request->attributesForModel());

        return new TimeOffResource($timeOff);
    }

    /**
     * Remove time off.
     */
    public function destroy(Barber $barber, TimeOff $timeOff): Response
    {
        Gate::authorize('manageTimeOff', $barber);

        $timeOff->delete();

        return response()->noContent();
    }
}
