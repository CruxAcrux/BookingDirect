<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResolvesShopRole;

/** The sent-SMS log contains every customer's messages, so only owners may read it. */
class SmsMessagePolicy
{
    use ResolvesShopRole;

    public function viewAny(User $user): bool
    {
        return $this->isOwner($user);
    }
}
