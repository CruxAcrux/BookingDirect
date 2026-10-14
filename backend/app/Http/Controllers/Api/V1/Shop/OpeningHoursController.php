<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Actions\Schedule\ReplaceWeeklyHours;
use App\Http\Controllers\Controller;
use App\Http\Requests\WeeklyHoursRequest;
use App\Http\Resources\WeeklyHourResource;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class OpeningHoursController extends Controller
{
    public function __construct(private readonly CurrentShop $currentShop) {}

    /**
     * The shop's weekly opening hours.
     */
    public function index(): AnonymousResourceCollection
    {
        $shop = $this->currentShop->getOrFail();
        Gate::authorize('view', $shop);

        return WeeklyHourResource::collection(
            $shop->openingHours()->orderBy('weekday')->orderBy('opens_at')->get(),
        );
    }

    /**
     * Replace the weekly opening hours.
     *
     * Send the whole week; days without ranges are closed. A day may have several ranges.
     */
    public function update(WeeklyHoursRequest $request, ReplaceWeeklyHours $replace): AnonymousResourceCollection
    {
        $shop = $this->currentShop->getOrFail();
        Gate::authorize('update', $shop);

        return WeeklyHourResource::collection(
            $replace->handle($shop->openingHours(), $request->ranges(), 'opens_at', 'closes_at'),
        );
    }
}
