<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use App\Tenancy\CurrentShop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public pages are addressed by shop slug. Deactivated shops look like they don't exist.
 */
class ResolvePublicShop
{
    public function __construct(private readonly CurrentShop $currentShop) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shop = $request->route('shop');

        abort_unless($shop instanceof Shop && $shop->isActive(), Response::HTTP_NOT_FOUND);

        $this->currentShop->set($shop);

        return $next($request);
    }
}
