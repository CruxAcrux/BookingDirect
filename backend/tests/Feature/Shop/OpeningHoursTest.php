<?php

use App\Enums\ShopRole;
use App\Models\OpeningHour;
use App\Models\Shop;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->owner = $this->staffMember($this->shop);
});

it('replaces the whole week, with several ranges per day', function () {
    OpeningHour::factory()->for($this->shop)->create(['weekday' => 7]);

    $this->actingAs($this->owner)
        ->putJson('/api/v1/shop/opening-hours', ['hours' => [
            ['weekday' => 2, 'starts_at' => '13:00', 'ends_at' => '18:00'],
            ['weekday' => 2, 'starts_at' => '09:00', 'ends_at' => '12:00'],
            ['weekday' => 1, 'starts_at' => '10:00', 'ends_at' => '19:00'],
        ]])
        ->assertOk()
        ->assertExactJson(['data' => [
            ['weekday' => 1, 'starts_at' => '10:00', 'ends_at' => '19:00'],
            ['weekday' => 2, 'starts_at' => '09:00', 'ends_at' => '12:00'],
            ['weekday' => 2, 'starts_at' => '13:00', 'ends_at' => '18:00'],
        ]]);

    expect($this->shop->openingHours()->where('weekday', 7)->exists())->toBeFalse();
});

it('rejects overlapping ranges and names the day', function () {
    $this->actingAs($this->owner)
        ->withHeader('Accept-Language', 'en')
        ->putJson('/api/v1/shop/opening-hours', ['hours' => [
            ['weekday' => 3, 'starts_at' => '09:00', 'ends_at' => '13:00'],
            ['weekday' => 3, 'starts_at' => '12:00', 'ends_at' => '18:00'],
        ]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['hours' => 'The times on Wednesday overlap.']);
});

it('rejects a range that ends before it starts', function () {
    $this->actingAs($this->owner)
        ->putJson('/api/v1/shop/opening-hours', ['hours' => [
            ['weekday' => 1, 'starts_at' => '18:00', 'ends_at' => '09:00'],
        ]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('hours.0.ends_at');
});

it('lets barbers read but not change opening hours', function () {
    $barber = $this->staffMember($this->shop, ShopRole::Barber);

    $this->actingAs($barber)->getJson('/api/v1/shop/opening-hours')->assertOk();
    $this->actingAs($barber)->putJson('/api/v1/shop/opening-hours', ['hours' => []])->assertForbidden();
});

it('leaves other shops\' hours alone', function () {
    $other = OpeningHour::factory()->create();

    $this->actingAs($this->owner)->putJson('/api/v1/shop/opening-hours', ['hours' => []])->assertOk();

    expect(OpeningHour::withoutGlobalScopes()->find($other->id))->not->toBeNull();
});
