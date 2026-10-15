<?php

namespace App\Actions\Barbers;

use App\Enums\ShopRole;
use App\Media\ImageStore;
use App\Models\Barber;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;

class RemoveBarber
{
    public function __construct(private readonly ImageStore $images) {}

    /**
     * Archives the profile so past bookings keep their barber, and revokes the
     * person's access unless they are also an owner.
     */
    public function handle(Shop $shop, Barber $barber): void
    {
        DB::transaction(function () use ($shop, $barber): void {
            $user = $barber->user;

            if ($user !== null && $user->roleIn($shop) === ShopRole::Barber) {
                $shop->users()->detach($user);
            }

            $barber->services()->detach();
            $barber->user()->dissociate();
            $barber->is_active = false;
            $barber->save();
            $barber->delete();
        });

        $this->images->delete($barber->avatar_path);
    }
}
