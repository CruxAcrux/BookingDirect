<?php

namespace App\Providers;

use App\Models\User;
use App\Tenancy\CurrentShop;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentShop::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->uncompromised()
            : Password::min(10));

        Gate::define('administer-platform', fn (User $user): bool => $user->is_platform_admin);

        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            $query = http_build_query(['token' => $token, 'email' => $user->email]);

            return rtrim(config()->string('app.frontend_url'), '/').'/reset-password?'.$query;
        });
    }
}
