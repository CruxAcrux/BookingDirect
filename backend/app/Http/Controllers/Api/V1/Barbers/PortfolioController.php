<?php

namespace App\Http\Controllers\Api\V1\Barbers;

use App\Actions\ReorderModels;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\ReorderRequest;
use App\Http\Resources\PortfolioImageResource;
use App\Media\ImageStore;
use App\Models\Barber;
use App\Models\PortfolioImage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PortfolioController extends Controller
{
    public const MAX_IMAGES = 30;

    public function __construct(private readonly ImageStore $images) {}

    /**
     * A barber's portfolio, in display order.
     */
    public function index(Barber $barber): AnonymousResourceCollection
    {
        Gate::authorize('view', $barber);

        return PortfolioImageResource::collection($barber->portfolioImages()->orderBy('id')->get());
    }

    /**
     * Add an image to a barber's portfolio.
     *
     * JPEG, PNG or WebP up to 10 MB. Stored as WebP up to 1600 px, with a 600 px square thumbnail.
     */
    public function store(ImageUploadRequest $request, Barber $barber): PortfolioImageResource
    {
        Gate::authorize('managePortfolio', $barber);

        if ($barber->portfolioImages()->count() >= self::MAX_IMAGES) {
            throw ValidationException::withMessages(['image' => __('rules.portfolio_full', ['max' => self::MAX_IMAGES])]);
        }

        $image = $barber->portfolioImages()->create([
            ...$this->images->storeWithThumbnail($request->upload()),
            'caption' => $request->validated('caption'),
            'sort_order' => (int) $barber->portfolioImages()->max('sort_order') + 1,
        ]);

        return new PortfolioImageResource($image);
    }

    /**
     * Change an image's caption.
     */
    public function update(Request $request, Barber $barber, PortfolioImage $portfolioImage): PortfolioImageResource
    {
        Gate::authorize('managePortfolio', $barber);

        $portfolioImage->update($request->validate(['caption' => ['present', 'nullable', 'string', 'max:160']]));

        return new PortfolioImageResource($portfolioImage);
    }

    /**
     * Remove an image from a barber's portfolio.
     */
    public function destroy(Barber $barber, PortfolioImage $portfolioImage): Response
    {
        Gate::authorize('managePortfolio', $barber);

        $portfolioImage->delete();
        $this->images->delete($portfolioImage->path, $portfolioImage->thumbnail_path);

        return response()->noContent();
    }

    /**
     * Set the display order of a barber's portfolio.
     */
    public function reorder(ReorderRequest $request, Barber $barber, ReorderModels $reorder): AnonymousResourceCollection
    {
        Gate::authorize('managePortfolio', $barber);

        $reorder->handle($barber->portfolioImages()->getQuery(), $request->ids());

        return $this->index($barber);
    }
}
