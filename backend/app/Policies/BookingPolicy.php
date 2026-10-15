<?php

namespace App\Policies;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\User;
use App\Policies\Concerns\ResolvesShopRole;

/**
 * Owners see and manage every booking. Barbers work with their own calendar only.
 */
class BookingPolicy
{
    use ResolvesShopRole;

    public function viewAny(User $user): bool
    {
        return $this->isMember($user);
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->isOwner($user) || $this->isOwnBarber($user, $booking->barber_id);
    }

    /** Create or move a booking into this barber's calendar. */
    public function bookFor(User $user, Barber $barber): bool
    {
        return $this->isOwner($user) || $this->isOwnBarber($user, $barber->id);
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->view($user, $booking);
    }

    private function isOwnBarber(User $user, int $barberId): bool
    {
        return $user->barberProfileId() === $barberId;
    }
}
