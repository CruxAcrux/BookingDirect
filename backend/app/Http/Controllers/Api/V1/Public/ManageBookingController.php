<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Booking\Actions\CancelBooking;
use App\Booking\Actions\RescheduleBooking;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveManagedBooking;
use App\Http\Requests\Public\RescheduleBookingRequest;
use App\Http\Resources\PublicBookingResource;
use App\Models\Barber;
use App\Models\Booking;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Request;

/**
 * The manage-booking link sent to customers. Whoever holds the token may view, cancel
 * or move that one booking, without signing in.
 */
class ManageBookingController extends Controller
{
    public function __construct(private readonly CurrentShop $currentShop) {}

    /**
     * A booking by its manage token.
     *
     * @unauthenticated
     */
    public function show(Request $request, string $token): PublicBookingResource
    {
        return new PublicBookingResource($this->booking($request));
    }

    /**
     * Cancel a booking by its manage token, within the shop's cancellation window.
     *
     * @unauthenticated
     */
    public function cancel(Request $request, string $token, CancelBooking $cancel): PublicBookingResource
    {
        return new PublicBookingResource($cancel->byCustomer($this->currentShop->getOrFail(), $this->booking($request)));
    }

    /**
     * Move a booking by its manage token to another free time, within the cancellation window.
     *
     * @unauthenticated
     */
    public function reschedule(RescheduleBookingRequest $request, string $token, RescheduleBooking $reschedule): PublicBookingResource
    {
        $barber = $request->filled('barber_id') ? Barber::findOrFail($request->integer('barber_id')) : null;
        $booking = $reschedule->byCustomer($this->currentShop->getOrFail(), $this->booking($request), $request->startsAt(), $barber);

        return new PublicBookingResource($booking->load(['service', 'barber']));
    }

    private function booking(Request $request): Booking
    {
        return ResolveManagedBooking::booking($request)->load(['service', 'barber']);
    }
}
