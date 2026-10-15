<?php

namespace App\Policies\Concerns;

use App\Enums\ShopRole;
use App\Models\User;
use App\Tenancy\CurrentShop;

trait ResolvesShopRole
{
    protected function isOwner(User $user): bool
    {
        return $this->role($user) === ShopRole::Owner;
    }

    protected function isMember(User $user): bool
    {
        return $this->role($user) !== null;
    }

    private function role(User $user): ?ShopRole
    {
        $shop = app(CurrentShop::class)->get();

        return $shop === null ? null : $user->roleIn($shop);
    }
}
