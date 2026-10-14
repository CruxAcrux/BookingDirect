<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Tenancy\CurrentShop;
use App\Tenancy\StaffShopResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveStaffShop
{
    public function __construct(
        private readonly StaffShopResolver $resolver,
        private readonly CurrentShop $currentShop,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        $shop = $this->resolver->resolve($user, $request->hasSession() ? $request->session() : null);

        abort_if($shop === null, Response::HTTP_FORBIDDEN, __('shop.no_active_shop'));

        $this->currentShop->set($shop);

        return $next($request);
    }
}
