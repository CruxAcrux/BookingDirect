<?php

namespace App\Http\Controllers\Api\V1\Barbers;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Resources\BarberResource;
use App\Media\ImagePreset;
use App\Media\ImageStore;
use App\Models\Barber;
use Illuminate\Support\Facades\Gate;

class BarberAvatarController extends Controller
{
    public function __construct(private readonly ImageStore $images) {}

    /**
     * Upload a barber's photo.
     *
     * JPEG, PNG or WebP up to 10 MB, cropped to a 400 × 400 square and stored as WebP.
     */
    public function store(ImageUploadRequest $request, Barber $barber): BarberResource
    {
        Gate::authorize('updateProfile', $barber);

        $previous = $barber->avatar_path;
        $barber->avatar_path = $this->images->store($request->upload(), ImagePreset::Avatar);
        $barber->save();
        $this->images->delete($previous);

        return new BarberResource($barber->load(['user', 'services']));
    }

    /**
     * Remove a barber's photo.
     */
    public function destroy(Barber $barber): BarberResource
    {
        Gate::authorize('updateProfile', $barber);

        $this->images->delete($barber->avatar_path);
        $barber->avatar_path = null;
        $barber->save();

        return new BarberResource($barber->load(['user', 'services']));
    }
}
