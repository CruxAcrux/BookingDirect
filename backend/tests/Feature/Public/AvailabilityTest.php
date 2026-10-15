<?php

namespace Tests\Feature\Public;

use App\Models\Barber;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Support\Carbon;

beforeEach(function () {
    // Monday 2027-06-14, 09:00 in Stockholm.
    $this->travelTo(Carbon::parse('2027-06-14 07:00:00', 'UTC'));
});

function availability(string $slug, array $query): string
{
    return "/api/v1/public/shops/{$slug}/availability?".http_build_query($query);
}

it('lists free times per local date', function () {
    ['shop' => $shop, 'service' => $service] = $this->bookableShop();

    $this->getJson(availability($shop->slug, ['service_id' => $service->id, 'from' => '2027-06-14', 'to' => '2027-06-15']))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.date', '2027-06-14')
        ->assertJsonCount(16, 'data.0.slots')
        ->assertJsonPath('data.0.slots.0', [
            'starts_at' => '2027-06-14T08:00:00Z',
            'date' => '2027-06-14',
            'time' => '10:00',
            'barber_id' => $shop->barbers()->first()->id,
        ]);
});

it('leaves out times that are already booked', function () {
    ['shop' => $shop, 'service' => $service, 'barbers' => [$barber]] = $this->bookableShop();
    Booking::factory()->for($barber)->at(Carbon::parse('2027-06-14 08:00', 'UTC'))->create(['shop_id' => $shop->id]);

    $this->getJson(availability($shop->slug, ['service_id' => $service->id, 'from' => '2027-06-14', 'to' => '2027-06-14']))
        ->assertJsonPath('data.0.slots.0.time', '10:30');
});

it('names the first free barber in team order for "first available"', function () {
    ['shop' => $shop, 'service' => $service, 'barbers' => [$first, $second]] = $this->bookableShop(barbers: 2);
    Booking::factory()->for($first)->at(Carbon::parse('2027-06-14 08:00', 'UTC'))->create(['shop_id' => $shop->id]);

    $slots = $this->getJson(availability($shop->slug, ['service_id' => $service->id, 'from' => '2027-06-14', 'to' => '2027-06-14']))
        ->json('data.0.slots');

    expect($slots[0])->toMatchArray(['time' => '10:00', 'barber_id' => $second->id])
        ->and($slots[1])->toMatchArray(['time' => '10:30', 'barber_id' => $first->id]);
});

it('only offers barbers who do the chosen service', function () {
    ['shop' => $shop, 'service' => $service] = $this->bookableShop();
    $other = Service::factory()->for($shop)->create(['is_active' => true]);

    $this->getJson(availability($shop->slug, ['service_id' => $other->id, 'from' => '2027-06-14', 'to' => '2027-06-14']))
        ->assertOk()
        ->assertJsonPath('data.0.slots', []);
});

it('validates the query', function (array $query, string $field) {
    ['shop' => $shop, 'service' => $service] = $this->bookableShop();
    $foreignService = Service::factory()->create();
    $foreignBarber = Barber::factory()->create();

    $replacements = ['own' => $service->id, 'foreign-service' => $foreignService->id, 'foreign-barber' => $foreignBarber->id];
    $query = array_map(fn ($value) => $replacements[$value] ?? $value, $query);

    $this->getJson(availability($shop->slug, $query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'range too long' => [['service_id' => 'own', 'from' => '2027-06-14', 'to' => '2027-06-28'], 'to'],
    'end before start' => [['service_id' => 'own', 'from' => '2027-06-14', 'to' => '2027-06-13'], 'to'],
    'another shop\'s service' => [['service_id' => 'foreign-service', 'from' => '2027-06-14', 'to' => '2027-06-14'], 'service_id'],
    'another shop\'s barber' => [['service_id' => 'own', 'barber_id' => 'foreign-barber', 'from' => '2027-06-14', 'to' => '2027-06-14'], 'barber_id'],
]);

it('finds the next free time', function () {
    ['shop' => $shop, 'service' => $service, 'barbers' => [$barber]] = $this->bookableShop();
    $barber->workingHours()->where('weekday', '<', 3)->delete();

    $this->getJson("/api/v1/public/shops/{$shop->slug}/availability/next?service_id={$service->id}&barber_id={$barber->id}")
        ->assertOk()
        ->assertJsonPath('data.date', '2027-06-16')
        ->assertJsonPath('data.time', '10:00');
});

it('reports no next time when nothing is free', function () {
    ['shop' => $shop, 'service' => $service, 'barbers' => [$barber]] = $this->bookableShop();
    $barber->workingHours()->delete();

    $this->getJson("/api/v1/public/shops/{$shop->slug}/availability/next?service_id={$service->id}")
        ->assertOk()
        ->assertJsonPath('data', null);
});
