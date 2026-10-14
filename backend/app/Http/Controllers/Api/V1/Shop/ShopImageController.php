<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Resources\ShopResource;
use App\Media\ImagePreset;
use App\Media\ImageStore;
use App\Tenancy\CurrentShop;
use Illuminate\Support\Facades\Gate;

class ShopImageController extends Controller
{
    public function __construct(
        private readonly CurrentShop $currentShop,
        private readonly ImageStore $images,
    ) {}

    /**
     * Upload the shop's logo or cover image.
     *
     * JPEG, PNG or WebP up to 10 MB. Stored as WebP: logos up to 512 px, covers up to 2000 px wide.
     *
     * @param  'logo'|'cover'  $type
     */
    public function store(ImageUploadRequest $request, string $type): ShopResource
    {
        $shop = $this->currentShop->getOrFail();
        Gate::authorize('update', $shop);

        $column = "{$type}_path";
        $previous = $shop->{$column};

        $shop->{$column} = $this->images->store($request->upload(), $type === 'logo' ? ImagePreset::Logo : ImagePreset::Cover);
        $shop->save();

        $this->images->delete($previous);

        return new ShopResource($shop);
    }

    /**
     * Remove the shop's logo or cover image.
     *
     * @param  'logo'|'cover'  $type
     */
    public function destroy(string $type): ShopResource
    {
        $shop = $this->currentShop->getOrFail();
        Gate::authorize('update', $shop);

        $column = "{$type}_path";
        $this->images->delete($shop->{$column});
        $shop->{$column} = null;
        $shop->save();

        return new ShopResource($shop);
    }
}
