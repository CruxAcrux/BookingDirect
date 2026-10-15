<?php

namespace App\Http\Controllers\Api\V1\Barbers;

use App\Actions\Barbers\InviteBarber;
use App\Actions\Barbers\RemoveBarber;
use App\Actions\ReorderModels;
use App\Http\Controllers\Controller;
use App\Http\Requests\Barbers\BarberRequest;
use App\Http\Requests\Barbers\InviteBarberRequest;
use App\Http\Requests\Barbers\SyncBarberServicesRequest;
use App\Http\Requests\ReorderRequest;
use App\Http\Resources\BarberResource;
use App\Models\Barber;
use App\Tenancy\CurrentShop;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class BarberController extends Controller
{
    public function __construct(private readonly CurrentShop $currentShop) {}

    /**
     * List the shop's barbers, including inactive ones.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Barber::class);

        return BarberResource::collection(
            Barber::with(['user', 'services'])->orderBy('sort_order')->orderBy('id')->get(),
        );
    }

    /**
     * A barber with their login and services.
     */
    public function show(Barber $barber): BarberResource
    {
        Gate::authorize('view', $barber);

        return new BarberResource($barber->load(['user', 'services']));
    }

    /**
     * Add a barber profile. It is placed last in the list.
     *
     * The barber gets no login until they are invited.
     */
    public function store(BarberRequest $request): BarberResource
    {
        Gate::authorize('manage', Barber::class);

        $barber = Barber::create([
            ...$request->validated(),
            'sort_order' => (int) Barber::max('sort_order') + 1,
        ]);

        return new BarberResource($barber->refresh()->load(['user', 'services']));
    }

    /**
     * Update a barber.
     *
     * Barbers may update their own name and bio; everything else is for owners.
     */
    public function update(BarberRequest $request, Barber $barber): BarberResource
    {
        Gate::authorize('updateProfile', $barber);

        $data = $request->validated();
        $changesBeyondProfile = Arr::except($data, BarberRequest::PROFILE_FIELDS) !== [];

        abort_if($changesBeyondProfile && Gate::denies('manage', $barber), Response::HTTP_FORBIDDEN, __('rules.profile_only'));

        $barber->update($data);

        return new BarberResource($barber->load(['user', 'services']));
    }

    /**
     * Remove a barber.
     *
     * The profile is archived so past bookings keep it, and the barber loses access to the shop.
     */
    public function destroy(Barber $barber, RemoveBarber $remove): Response
    {
        Gate::authorize('manage', $barber);

        $remove->handle($this->currentShop->getOrFail(), $barber);

        return response()->noContent();
    }

    /**
     * Set the display order of all barbers.
     */
    public function reorder(ReorderRequest $request, ReorderModels $reorder): AnonymousResourceCollection
    {
        Gate::authorize('manage', Barber::class);

        $reorder->handle(Barber::query(), $request->ids());

        return $this->index();
    }

    /**
     * Give a barber a login.
     *
     * A new user gets an email to choose a password. An existing user, such as an owner
     * who also cuts hair, is linked without changing their password.
     */
    public function invite(InviteBarberRequest $request, Barber $barber, InviteBarber $invite): BarberResource
    {
        Gate::authorize('manage', $barber);

        $barber = $invite->handle(
            $this->currentShop->getOrFail(),
            $barber,
            $request->string('email')->value(),
            $request->filled('name') ? $request->string('name')->value() : null,
        );

        return new BarberResource($barber->load('services'));
    }

    /**
     * Set which services a barber offers, with optional price and duration overrides.
     */
    public function syncServices(SyncBarberServicesRequest $request, Barber $barber): BarberResource
    {
        Gate::authorize('manage', $barber);

        $barber->services()->sync($request->pivotData());

        return new BarberResource($barber->load(['user', 'services']));
    }
}
