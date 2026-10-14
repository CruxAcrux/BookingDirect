<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\CreateShopWithOwner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShopRequest;
use App\Http\Resources\AdminShopResource;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShopController extends Controller
{
    /**
     * List all shops.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->string('search')->trim()->value();

        $shops = Shop::query()
            ->with('users')
            ->when($search !== '', fn ($query) => $query->whereLike('name', "%{$search}%"))
            ->orderBy('name')
            ->paginate(25);

        return AdminShopResource::collection($shops);
    }

    /**
     * Create a shop with an owner.
     *
     * If the owner has no account yet, one is created and they get an email to choose a password.
     */
    public function store(StoreShopRequest $request, CreateShopWithOwner $createShop): AdminShopResource
    {
        $shop = $createShop->handle(
            name: $request->string('name')->value(),
            ownerName: $request->string('owner.name')->value(),
            ownerEmail: $request->string('owner.email')->value(),
            slug: $request->filled('slug') ? $request->string('slug')->value() : null,
            timezone: $request->string('timezone', 'Europe/Stockholm')->value(),
            locale: $request->string('locale', 'sv')->value(),
        );

        return new AdminShopResource($shop);
    }

    /**
     * Deactivate a shop.
     *
     * Its booking page and dashboard stop working; no data is deleted.
     */
    public function deactivate(Shop $shop): AdminShopResource
    {
        $shop->deactivated_at ??= now();
        $shop->save();

        return new AdminShopResource($shop->load('users'));
    }

    /**
     * Reactivate a shop.
     */
    public function activate(Shop $shop): AdminShopResource
    {
        $shop->deactivated_at = null;
        $shop->save();

        return new AdminShopResource($shop->load('users'));
    }
}
