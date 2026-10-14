<?php

use App\Models\Barber;
use App\Models\Shop;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->owner = $this->staffMember($this->shop);
    $this->barber = Barber::factory()->for($this->shop)->create();
});

it('replaces working hours and breaks', function (string $endpoint) {
    $this->actingAs($this->owner)
        ->putJson("/api/v1/barbers/{$this->barber->id}/{$endpoint}", ['hours' => [
            ['weekday' => 5, 'starts_at' => '10:00', 'ends_at' => '16:00'],
        ]])
        ->assertOk()
        ->assertExactJson(['data' => [['weekday' => 5, 'starts_at' => '10:00', 'ends_at' => '16:00']]]);

    $this->actingAs($this->owner)
        ->getJson("/api/v1/barbers/{$this->barber->id}/{$endpoint}")
        ->assertJsonCount(1, 'data');
})->with(['working-hours', 'breaks']);

it('lets a barber see but not change their schedule', function () {
    [$user, $barber] = $this->barberWithLogin($this->shop);

    $this->actingAs($user)->getJson("/api/v1/barbers/{$barber->id}/working-hours")->assertOk();
    $this->actingAs($user)
        ->putJson("/api/v1/barbers/{$barber->id}/working-hours", ['hours' => []])
        ->assertForbidden();
});
