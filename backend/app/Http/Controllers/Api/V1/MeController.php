<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CurrentUserResource;
use App\Models\User;
use App\Tenancy\StaffShopResolver;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * The signed-in staff member.
     */
    public function __invoke(Request $request, StaffShopResolver $resolver): CurrentUserResource
    {
        /** @var User $user */
        $user = $request->user();

        return new CurrentUserResource($user, $resolver->resolve($user, $request->hasSession() ? $request->session() : null));
    }
}
