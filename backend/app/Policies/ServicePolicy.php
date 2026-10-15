<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Policies\Concerns\ResolvesShopRole;

class ServicePolicy
{
    use ResolvesShopRole;

    public function viewAny(User $user): bool
    {
        return $this->isMember($user);
    }

    public function create(User $user): bool
    {
        return $this->isOwner($user);
    }

    public function update(User $user, Service $service): bool
    {
        return $this->isOwner($user);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->isOwner($user);
    }

    public function reorder(User $user): bool
    {
        return $this->isOwner($user);
    }
}
