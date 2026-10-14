<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\CurrentUserResource;
use App\Models\User;
use App\Tenancy\StaffShopResolver;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_FAILED_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 60;

    /**
     * Sign in a staff member.
     *
     * Starts a cookie session. Call `GET /sanctum/csrf-cookie` first. After five failed
     * attempts for the same email and IP, further attempts get `429` for a minute.
     *
     * @unauthenticated
     */
    public function __invoke(LoginRequest $request, StaffShopResolver $resolver): CurrentUserResource
    {
        $throttleKey = Str::transliterate(Str::lower($request->string('email')->value()).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_FAILED_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw new ThrottleRequestsException(__('auth.throttle', ['seconds' => $seconds]), headers: ['Retry-After' => $seconds]);
        }

        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();

        return new CurrentUserResource($user, $resolver->resolve($user, $request->session()));
    }
}
