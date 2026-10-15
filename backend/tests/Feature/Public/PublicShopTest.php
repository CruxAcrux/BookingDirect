<?php

use App\Models\Barber;
use App\Models\Service;
use App\Models\Shop;

it('shows the shop with its active services and barbers', function () {
    ['shop' => $shop, 'service' => $service, 'barbers' => [$barber]] = $this->bookableShop();
    Service::factory()->for($shop)->create(['is_active' => false]);
    Barber::factory()->for($shop)->inactive()->create();
    $barber->services()->updateExistingPivot($service->id, ['price_ore' => 45000]);

    $this->getJson("/api/v1/public/shops/{$shop->slug}")
        ->assertOk()
        ->assertJsonPath('data.name', $shop->name)
        ->assertJsonCount(7, 'data.opening_hours')
        ->assertJsonCount(1, 'data.services')
        ->assertJsonCount(1, 'data.barbers')
        ->assertJsonPath('data.barbers.0.services.0', ['service_id' => $service->id, 'price_ore' => 45000, 'duration_minutes' => 30]);
});

it('hides deactivated and unknown shops', function () {
    $closed = Shop::factory()->inactive()->create();

    $this->getJson("/api/v1/public/shops/{$closed->slug}")->assertNotFound();
    $this->getJson('/api/v1/public/shops/does-not-exist')->assertNotFound();
});
