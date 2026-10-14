<?php

namespace App\Policies;

use App\Models\Barber;
use App\Models\User;
use App\Policies\Concerns\ResolvesShopRole;

/**
 * Owners manage the whole team. A barber may edit their own profile, portfolio and time off.
 */
class BarberPolicy
{
    use ResolvesShopRole;

    public function viewAny(User $user): bool
    {
        return $this->isMember($user);
    }

    public function view(User $user, Barber $barber): bool
    {
        return $this->isMember($user);
    }

    /** Create, deactivate, delete, reorder, invite, and set services and schedules. */
    public function manage(User $user, ?Barber $barber = null): bool
    {
        return $this->isOwner($user);
    }

    /** Name, bio and photo. */
    public function updateProfile(User $user, Barber $barber): bool
    {
        return $this->isOwner($user) || $this->isSelf($user, $barber);
    }

    public function managePortfolio(User $user, Barber $barber): bool
    {
        return $this->isOwner($user) || $this->isSelf($user, $barber);
    }

    public function manageTimeOff(User $user, Barber $barber): bool
    {
        return $this->isOwner($user) || $this->isSelf($user, $barber);
    }

    private function isSelf(User $user, Barber $barber): bool
    {
        return $barber->user_id !== null && $barber->user_id === $user->id;
    }
}
