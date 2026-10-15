<?php

namespace App\Http\Controllers\Api\V1\Services;

use App\Actions\ReorderModels;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderRequest;
use App\Http\Requests\Services\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ServiceController extends Controller
{
    /**
     * List the shop's services, including inactive ones.
     */
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Service::class);

        return ServiceResource::collection(Service::orderBy('sort_order')->orderBy('id')->get());
    }

    /**
     * Add a service. It is placed last in the list.
     */
    public function store(ServiceRequest $request): ServiceResource
    {
        Gate::authorize('create', Service::class);

        $service = Service::create([
            ...$request->validated(),
            'sort_order' => (int) Service::max('sort_order') + 1,
        ]);

        return new ServiceResource($service->refresh());
    }

    /**
     * Update a service.
     */
    public function update(ServiceRequest $request, Service $service): ServiceResource
    {
        Gate::authorize('update', $service);

        $service->update($request->validated());

        return new ServiceResource($service);
    }

    /**
     * Delete a service.
     *
     * The service is archived, so past bookings and statistics keep their reference.
     */
    public function destroy(Service $service): Response
    {
        Gate::authorize('delete', $service);

        $service->delete();

        return response()->noContent();
    }

    /**
     * Set the display order of all services.
     */
    public function reorder(ReorderRequest $request, ReorderModels $reorder): AnonymousResourceCollection
    {
        Gate::authorize('reorder', Service::class);

        $reorder->handle(Service::query(), $request->ids());

        return $this->index();
    }
}
