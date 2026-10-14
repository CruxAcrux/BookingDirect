<?php

namespace App\Policies;

use App\Models\Shop;
use App\Models\User;
use App\Policies\Concerns\ResolvesShopRole;

class ShopPolicy
{
    use ResolvesShopRole;

    public function view(User $user, Shop $shop): bool
    {
        return $this->isMember($user);
    }

    public function update(User $user, Shop $shop): bool
    {
        return $this->isOwner($user);
    }
}
