<?php

namespace App\Actions\Barbers;

use App\Enums\ShopRole;
use App\Models\Barber;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteBarber
{
    /**
     * Gives a barber profile a login. New users get an email to choose a password;
     * an existing user (for example an owner who also cuts hair) is linked as they are.
     */
    public function handle(Shop $shop, Barber $barber, string $email, ?string $name = null): Barber
    {
        if ($barber->user_id !== null) {
            throw ValidationException::withMessages(['email' => __('rules.already_has_login')]);
        }

        $user = DB::transaction(function () use ($shop, $barber, $email, $name): User {
            $user = User::firstOrCreate(
                ['email' => Str::lower($email)],
                ['name' => $name ?? $barber->display_name, 'password' => Str::password(32), 'locale' => $shop->locale],
            );

            if (Barber::where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['email' => __('rules.already_linked')]);
            }

            if ($user->roleIn($shop) === null) {
                $shop->users()->attach($user, ['role' => ShopRole::Barber]);
            }

            $barber->user()->associate($user)->save();

            return $user;
        });

        if ($user->wasRecentlyCreated) {
            Password::broker()->sendResetLink(['email' => $user->email]);
        }

        return $barber->load('user');
    }
}
