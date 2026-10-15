<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Booking\Actions\CancelBooking;
use App\Booking\Actions\CreateOnlineBooking;
use App\Booking\Actions\RescheduleBooking;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CreateBookingRequest;
use App\Http\Requests\Public\RescheduleBookingRequest;
use App\Http\Resources\PublicBookingResource;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Bookings for the signed-in customer.
 */
class CustomerBookingController extends Controller
{
    /**
     * Book a time.
     *
     * The customer must be signed in with an SMS code. The response contains `manage_token`,
     * the secret for the customer's manage-booking link; it is only ever returned here.
     *
     * @unauthenticated
     */
    public function store(CreateBookingRequest $request, Shop $shop, CreateOnlineBooking $create): JsonResponse
    {
        $customer = CustomerSessionController::customer($request);

        if ($customer->name === null && ! $request->filled('name')) {
            throw ValidationException::withMessages(['name' => __('booking.name_required')]);
        }

        $customer->fill(array_filter($request->safe()->only(['name', 'email']), fn ($value) => $value !== null))->save();

        $issued = $create->handle(
            $shop,
            $customer,
            Service::findOrFail($request->integer('service_id')),
            $request->filled('barber_id') ? Barber::findOrFail($request->integer('barber_id')) : null,
            $request->startsAt(),
            $request->validated('note'),
        );

        return (new PublicBookingResource($issued->booking->load(['service', 'barber'])))
            ->additional(['manage_token' => $issued->manageToken])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * The customer's bookings at this shop: upcoming first, then the most recent past ones.
     *
     * @unauthenticated
     */
    public function index(Request $request, Shop $shop): AnonymousResourceCollection
    {
        $customer = CustomerSessionController::customer($request);

        $upcoming = $customer->bookings()->where('starts_at', '>=', now())->orderBy('starts_at')->with(['service', 'barber'])->get();
        $past = $customer->bookings()->where('starts_at', '<', now())->latest('starts_at')->limit(20)->with(['service', 'barber'])->get();

        return PublicBookingResource::collection($upcoming->concat($past));
    }

    /**
     * Cancel one of the customer's bookings, within the shop's cancellation window.
     *
     * @unauthenticated
     */
    public function cancel(Request $request, Shop $shop, int $booking, CancelBooking $cancel): PublicBookingResource
    {
        $model = $this->owned($request, $booking);

        return new PublicBookingResource($cancel->byCustomer($shop, $model)->load(['service', 'barber']));
    }

    /**
     * Move one of the customer's bookings to another free time, within the cancellation window.
     *
     * @unauthenticated
     */
    public function reschedule(RescheduleBookingRequest $request, Shop $shop, int $booking, RescheduleBooking $reschedule): PublicBookingResource
    {
        $model = $this->owned($request, $booking);
        $barber = $request->filled('barber_id') ? Barber::findOrFail($request->integer('barber_id')) : null;

        return new PublicBookingResource($reschedule->byCustomer($shop, $model, $request->startsAt(), $barber)->load(['service', 'barber']));
    }

    private function owned(Request $request, int $id): Booking
    {
        return CustomerSessionController::customer($request)->bookings()->with('service')->findOrFail($id);
    }
}
