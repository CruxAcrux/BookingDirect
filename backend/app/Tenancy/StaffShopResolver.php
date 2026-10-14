<?php

namespace App\Tenancy;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Contracts\Session\Session;

/**
 * Picks which shop a staff member is working in.
 *
 * Prefers the shop remembered in the session and otherwise falls back to the
 * user's first active membership. Deactivated shops are never returned.
 */
class StaffShopResolver
{
    private const SESSION_KEY = 'current_shop_id';

    public function resolve(User $user, ?Session $session = null): ?Shop
    {
        $shops = $user->shops()
            ->whereNull('deactivated_at')
            ->orderBy('shop_user.id')
            ->get();

        $preferredId = $session?->get(self::SESSION_KEY);
        $shop = $shops->firstWhere('id', $preferredId) ?? $shops->first();

        if ($shop !== null && $session !== null && $shop->id !== $preferredId) {
            $session->put(self::SESSION_KEY, $shop->id);
        }

        return $shop;
    }
}
