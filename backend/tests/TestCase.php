<?php

namespace Tests;

use App\Enums\ShopRole;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\OpeningHour;
use App\Models\Service;
use App\Models\Shop;
use App\Models\User;
use App\Models\WorkingHour;
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

    /**
     * A shop open every day 10:00–18:00 with barbers who work all of it and offer one
     * 30-minute service. Times are Europe/Stockholm.
     *
     * @return array{shop: Shop, service: Service, barbers: list<Barber>}
     */
    protected function bookableShop(int $barbers = 1, array $shopAttributes = []): array
    {
        $shop = Shop::factory()->create(['min_notice_minutes' => 0, 'slot_interval_minutes' => 30, ...$shopAttributes]);
        $service = Service::factory()->for($shop)->create(['duration_minutes' => 30, 'buffer_minutes' => 0, 'price_ore' => 40000, 'is_active' => true]);

        foreach (range(1, 7) as $day) {
            OpeningHour::factory()->for($shop)->create(['weekday' => $day, 'opens_at' => '10:00', 'closes_at' => '18:00']);
        }

        $team = [];
        foreach (range(1, $barbers) as $position) {
            $barber = Barber::factory()->for($shop)->create(['sort_order' => $position]);
            $barber->services()->attach($service);
            foreach (range(1, 7) as $day) {
                WorkingHour::factory()->for($barber)->create(['shop_id' => $shop->id, 'weekday' => $day, 'starts_at' => '10:00', 'ends_at' => '18:00']);
            }
            $team[] = $barber;
        }

        return ['shop' => $shop, 'service' => $service, 'barbers' => $team];
    }

    /** Signs a customer in at a shop, the way the SMS code flow would. */
    protected function actingAsCustomer(Customer $customer): static
    {
        return $this->actingAs($customer, 'customer');
    }

    protected function staffMember(?Shop $shop = null, ShopRole $role = ShopRole::Owner): User
    {
        $user = User::factory()->create();
        ($shop ?? Shop::factory()->create())->users()->attach($user, ['role' => $role]);

        return $user;
    }
}
