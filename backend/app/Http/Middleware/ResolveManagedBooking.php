<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use App\Models\Shop;
use App\Tenancy\CurrentShop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Finds the booking behind a manage-link token and scopes the request to its shop,
 * before validation runs.
 */
class ResolveManagedBooking
{
    public function __construct(private readonly CurrentShop $currentShop) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        $booking = is_string($token)
            ? Booking::withoutGlobalScopes()->where('manage_token_hash', Booking::hashToken($token))->with('shop')->first()
            : null;

        $shop = $booking?->shop;

        abort_unless($booking !== null && $shop instanceof Shop && $shop->isActive(), Response::HTTP_NOT_FOUND);

        $this->currentShop->set($shop);
        $request->attributes->set(Booking::class, $booking);

        return $next($request);
    }

    public static function booking(Request $request): Booking
    {
        /** @var Booking */
        return $request->attributes->get(Booking::class);
    }
}
