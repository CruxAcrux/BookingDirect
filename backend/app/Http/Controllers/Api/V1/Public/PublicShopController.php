<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicShopResource;
use App\Models\Shop;

class PublicShopController extends Controller
{
    /**
     * A shop's public profile: details, opening hours, services and barbers.
     *
     * @unauthenticated
     */
    public function show(Shop $shop): PublicShopResource
    {
        $shop->load([
            'openingHours',
            'services' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
            'barbers' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('id'),
            'barbers.services' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            'barbers.portfolioImages',
        ]);

        return new PublicShopResource($shop);
    }
}
