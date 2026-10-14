<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Email a password reset link.
     *
     * Always answers the same way, so it can't be used to find out which emails have accounts.
     *
     * @unauthenticated
     */
    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker()->sendResetLink($request->safe()->only(['email']));

        return response()->json(['message' => __('passwords.sent')], 202);
    }
}
