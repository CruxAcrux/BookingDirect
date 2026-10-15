<?php

namespace Tests;

use App\Enums\ShopRole;
use App\Models\Barber;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;

abstract class TestCase extends BaseTestCase
{
    /**
     * Sends requests the way the SPA does, so Sanctum starts a cookie session.
     */
    protected function fromSpa(): static
    {
        /** @var array<int, string> $statefulDomains */
        $statefulDomains = config('sanctum.stateful');

        return $this->withHeader('Origin', 'http://'.Arr::first($statefulDomains));
    }

    /**
     * A barber with a login in the given shop.
     *
     * @return array{0: User, 1: Barber}
     */
    protected function barberWithLogin(Shop $shop): array
    {
        $user = $this->staffMember($shop, ShopRole::Barber);
        $barber = Barber::factory()->for($shop)->create(['user_id' => $user->id]);

        return [$user, $barber];
    }

    protected function staffMember(?Shop $shop = null, ShopRole $role = ShopRole::Owner): User
    {
        $user = User::factory()->create();
        ($shop ?? Shop::factory()->create())->users()->attach($user, ['role' => $role]);

        return $user;
    }
}
