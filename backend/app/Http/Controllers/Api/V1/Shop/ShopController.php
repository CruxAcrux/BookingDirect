<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\UpdateShopRequest;
use App\Http\Resources\ShopResource;
use App\Tenancy\CurrentShop;
use Illuminate\Support\Facades\Gate;

class ShopController extends Controller
{
    public function __construct(private readonly CurrentShop $currentShop) {}

    /**
     * The current shop's profile and booking rules.
     */
    public function show(): ShopResource
    {
        $shop = $this->currentShop->getOrFail();
        Gate::authorize('view', $shop);

        return new ShopResource($shop);
    }

    /**
     * Update the shop's profile and booking rules.
     *
     * Phone numbers are stored in E.164; Swedish numbers without a country code are accepted.
     */
    public function update(UpdateShopRequest $request): ShopResource
    {
        $shop = $this->currentShop->getOrFail();
        Gate::authorize('update', $shop);

        $shop->update($request->settings());

        return new ShopResource($shop);
    }
}
