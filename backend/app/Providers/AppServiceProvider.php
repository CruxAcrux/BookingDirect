<?php

namespace App\Providers;

use App\Models\User;
use App\Sms\LogSmsSender;
use App\Sms\SmsSender;
use App\Tenancy\CurrentShop;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentShop::class);
        $this->app->bind(SmsSender::class, LogSmsSender::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->uncompromised()
            : Password::min(10));

        RateLimiter::for('otp', fn (Request $request): array => [
            Limit::perMinutes(10, 3)->by('otp-phone|'.$request->string('phone')->replaceMatches('/\D/', '')),
            Limit::perHour(10)->by('otp-ip|'.$request->ip()),
        ]);
        RateLimiter::for('public-booking', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('availability', fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));

        Gate::define('administer-platform', fn (User $user): bool => $user->is_platform_admin);

        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            $query = http_build_query(['token' => $token, 'email' => $user->email]);

            return rtrim(config()->string('app.frontend_url'), '/').'/reset-password?'.$query;
        });
    }
}
