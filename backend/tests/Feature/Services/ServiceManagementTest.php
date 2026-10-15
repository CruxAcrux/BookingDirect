<?php

use App\Enums\ShopRole;
use App\Models\Service;
use App\Models\Shop;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->owner = $this->staffMember($this->shop);
});

it('lists only the shop\'s own services, in order', function () {
    $second = Service::factory()->for($this->shop)->create(['sort_order' => 2]);
    $first = Service::factory()->for($this->shop)->create(['sort_order' => 1, 'is_active' => false]);
    Service::factory()->create();

    $this->actingAs($this->owner)
        ->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $first->id)
        ->assertJsonPath('data.1.id', $second->id);
});

it('creates a service at the end of the list', function () {
    Service::factory()->for($this->shop)->create(['sort_order' => 4]);

    $this->actingAs($this->owner)
        ->postJson('/api/v1/services', [
            'name' => 'Skinfade',
            'duration_minutes' => 45,
            'price_ore' => 42000,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Skinfade')
        ->assertJsonPath('data.buffer_minutes', 0)
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.sort_order', 5);
});

it('updates and archives a service', function () {
    $service = Service::factory()->for($this->shop)->create();

    $this->actingAs($this->owner)
        ->patchJson("/api/v1/services/{$service->id}", ['price_ore' => 39900, 'is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.price_ore', 39900)
        ->assertJsonPath('data.is_active', false);

    $this->actingAs($this->owner)->deleteJson("/api/v1/services/{$service->id}")->assertNoContent();

    expect($service->fresh()->trashed())->toBeTrue();
});

it('validates services', function (array $payload, string $field) {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/services', [
            'name' => 'Klippning', 'duration_minutes' => 30, 'price_ore' => 30000, ...$payload,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'odd duration' => [['duration_minutes' => 32], 'duration_minutes'],
    'negative price' => [['price_ore' => -1], 'price_ore'],
    'missing name' => [['name' => ''], 'name'],
]);

it('only lets owners change services', function () {
    $barber = $this->staffMember($this->shop, ShopRole::Barber);
    $service = Service::factory()->for($this->shop)->create();

    $this->actingAs($barber)->getJson('/api/v1/services')->assertOk();
    $this->actingAs($barber)->postJson('/api/v1/services', ['name' => 'X', 'duration_minutes' => 30, 'price_ore' => 1])->assertForbidden();
    $this->actingAs($barber)->patchJson("/api/v1/services/{$service->id}", ['name' => 'X'])->assertForbidden();
    $this->actingAs($barber)->deleteJson("/api/v1/services/{$service->id}")->assertForbidden();
});

it('cannot touch another shop\'s service', function () {
    $foreign = Service::factory()->create();

    $this->actingAs($this->owner)->patchJson("/api/v1/services/{$foreign->id}", ['name' => 'Hacked'])->assertNotFound();
    $this->actingAs($this->owner)->deleteJson("/api/v1/services/{$foreign->id}")->assertNotFound();
});

it('reorders services', function () {
    [$a, $b, $c] = Service::factory()->for($this->shop)->count(3)->sequence(
        ['sort_order' => 0], ['sort_order' => 1], ['sort_order' => 2],
    )->create();

    $this->actingAs($this->owner)
        ->putJson('/api/v1/services/order', ['ids' => [$c->id, $a->id, $b->id]])
        ->assertOk()
        ->assertJsonPath('data.0.id', $c->id)
        ->assertJsonPath('data.2.id', $b->id);
});

it('rejects an order that does not match the shop\'s services', function () {
    $own = Service::factory()->for($this->shop)->create();
    $foreign = Service::factory()->create();

    $this->actingAs($this->owner)
        ->putJson('/api/v1/services/order', ['ids' => [$own->id, $foreign->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ids');
});
