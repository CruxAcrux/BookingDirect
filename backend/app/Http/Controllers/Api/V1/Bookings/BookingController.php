<?php

namespace App\Http\Controllers\Api\V1\Bookings;

use App\Booking\Actions\CancelBooking;
use App\Booking\Actions\CreateStaffBooking;
use App\Booking\Actions\FindOrCreateCustomer;
use App\Booking\Actions\MarkBookingOutcome;
use App\Booking\Actions\RescheduleBooking;
use App\Booking\Exceptions\BookingLockedException;
use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookings\BookingIndexRequest;
use App\Http\Requests\Bookings\StoreBookingRequest;
use App\Http\Requests\Bookings\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Tenancy\CurrentShop;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(private readonly CurrentShop $currentShop) {}

    /**
     * Bookings in a date range, for the calendar.
     *
     * Owners see every barber; barbers only see their own bookings. Cancelled bookings are
     * left out unless `with_cancelled` is set.
     */
    public function index(BookingIndexRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Booking::class);

        /** @var User $user */
        $user = $request->user();
        $timezone = $this->currentShop->getOrFail()->timezone;
        $from = CarbonImmutable::parse($request->string('from')->value(), $timezone)->startOfDay()->utc();
        $to = CarbonImmutable::parse($request->string('to')->value(), $timezone)->addDay()->startOfDay()->utc();
        $onlyBarber = $user->isOwnerOf($this->currentShop->getOrFail()) ? $request->input('barber_id') : $user->barberProfileId() ?? 0;

        $bookings = Booking::query()
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->when($onlyBarber !== null, fn ($query) => $query->where('barber_id', $onlyBarber))
            ->unless($request->boolean('with_cancelled'), fn ($query) => $query->where('status', '!=', BookingStatus::Cancelled))
            ->with(['service', 'customer'])
            ->orderBy('starts_at')
            ->get();

        return BookingResource::collection($bookings);
    }

    /**
     * A single booking.
     */
    public function show(Booking $booking): BookingResource
    {
        Gate::authorize('view', $booking);

        return new BookingResource($booking->load(['service', 'customer']));
    }

    /**
     * Create a booking for a customer, e.g. one who phoned.
     *
     * Opening hours and booking notice do not apply to staff; double-booking is refused
     * with `409`. The customer is matched by phone number or created.
     */
    public function store(StoreBookingRequest $request, FindOrCreateCustomer $customers, CreateStaffBooking $create): BookingResource
    {
        $barber = Barber::findOrFail($request->integer('barber_id'));
        Gate::authorize('bookFor', [Booking::class, $barber]);

        if ($request->startsAt()->isPast()) {
            throw new BookingLockedException('booking.in_past');
        }

        $customer = $customers->handle(
            $request->customerPhone(),
            $request->string('customer.name')->value(),
            $request->filled('customer.email') ? $request->string('customer.email')->value() : null,
            $this->currentShop->getOrFail()->locale,
        );

        $issued = $create->handle(
            $customer,
            Service::findOrFail($request->integer('service_id')),
            $barber,
            $request->startsAt(),
            $request->validated('staff_note'),
        );

        return new BookingResource($issued->booking->load(['service', 'customer']));
    }

    /**
     * Move a booking to another time or barber, or change the staff note.
     */
    public function update(UpdateBookingRequest $request, Booking $booking, RescheduleBooking $reschedule): BookingResource
    {
        Gate::authorize('update', $booking);

        if ($request->has('staff_note')) {
            $booking->update(['staff_note' => $request->validated('staff_note')]);
        }

        $startsAt = $request->startsAt();
        if ($startsAt !== null || $request->filled('barber_id')) {
            $barber = $request->filled('barber_id') ? Barber::findOrFail($request->integer('barber_id')) : $booking->barber;
            Gate::authorize('bookFor', [Booking::class, $barber]);

            $booking = $reschedule->byStaff($booking, $startsAt ?? $booking->starts_at->toImmutable(), $barber);
        }

        return new BookingResource($booking->load(['service', 'customer']));
    }

    /**
     * Cancel a booking, optionally with a message for the customer.
     */
    public function cancel(Request $request, Booking $booking, CancelBooking $cancel): BookingResource
    {
        Gate::authorize('update', $booking);
        $request->validate(['message' => ['nullable', 'string', 'max:500']]);

        $message = $request->filled('message') ? $request->string('message')->value() : null;

        return new BookingResource($cancel->byStaff($booking, $message)->load(['service', 'customer']));
    }

    /**
     * Mark how a visit went: completed or no-show.
     */
    public function outcome(Request $request, Booking $booking, MarkBookingOutcome $mark): BookingResource
    {
        Gate::authorize('update', $booking);
        $request->validate(['status' => ['required', Rule::in([BookingStatus::Completed->value, BookingStatus::NoShow->value])]]);

        $status = BookingStatus::from($request->string('status')->value());

        return new BookingResource($mark->handle($booking, $status)->load(['service', 'customer']));
    }
}
