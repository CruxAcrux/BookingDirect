<?php

namespace App\Actions\Admin;

use App\Enums\ShopRole;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateShopWithOwner
{
    /**
     * Creates the shop and attaches the owner. An owner without an account gets one
     * with a random password and an email link to choose their own.
     */
    public function handle(
        string $name,
        string $ownerName,
        string $ownerEmail,
        ?string $slug = null,
        string $timezone = 'Europe/Stockholm',
        string $locale = 'sv',
    ): Shop {
        [$shop, $owner] = DB::transaction(function () use ($name, $ownerName, $ownerEmail, $slug, $timezone, $locale): array {
            $shop = Shop::create([
                'name' => $name,
                'slug' => $slug ?? $this->uniqueSlug($name),
                'timezone' => $timezone,
                'locale' => $locale,
            ]);

            $owner = User::firstOrCreate(
                ['email' => Str::lower($ownerEmail)],
                ['name' => $ownerName, 'password' => Str::password(32), 'locale' => $locale],
            );

            $shop->users()->attach($owner, ['role' => ShopRole::Owner]);

            return [$shop, $owner];
        });

        if ($owner->wasRecentlyCreated) {
            Password::broker()->sendResetLink(['email' => $owner->email]);
        }

        return $shop->load('users');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'shop';
        $slug = $base;

        for ($suffix = 2; Shop::where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
