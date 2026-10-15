<?php

namespace Tests\Feature\Public;

use App\Booking\Availability\AvailabilityService;
use App\Booking\Availability\Slot;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2027-06-14 07:00:00', 'UTC'));
});

it('requires the customer to be signed in', function () {
    ['shop' => $shop, 'service' => $service] = $this->bookableShop();

    $this->postJson("/api/v1/public/shops/{$shop->slug}/bookings", [
        'service_id' => $service->id, 'starts_at' => '2027-06-14T08:00:00Z', 'name' => 'Sam',
    ])->assertUnauthorized();
});

it('books a free time and returns the manage token once', function () {
    ['shop' => $shop, 'service' => $service, 'barbers' => [$barber]] = $this->bookableShop();
    $barber->services()->updateExistingPivot($service->id, ['price_ore' => 45000]);
    $customer = Customer::factory()->for($shop)->create(['name' => null]);

    $response = $this->actingAsCustomer($customer)
        ->postJson("/api/v1/public/shops/{$shop->slug}/bookings", [
            'service_id' => $service->id,
            'barber_id' => $barber->id,
            'starts_at' => '2027-06-16T10:00:00+02:00',
            'name' => 'Sam Berg',
            'note' => 'Short on the sides',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.starts_at', '2027-06-16T08:00:00Z')
        ->assertJsonPath('data.starts_at_local', '2027-06-16T10:00')
        ->assertJsonPath('data.price_ore', 45000)
        ->assertJsonPath('data.can_change', true);

    $booking = Booking::sole();
    expect($booking->manage_token_hash)->toBe(Booking::hashToken($response->json('manage_token')))
        ->and($customer->fresh()->name)->toBe('Sam Berg');
});

it('asks for a name the first time', function () {
    ['shop' => $shop, 'service' => $service] = $this->bookableShop();
    $customer = Customer::factory()->for($shop)->create(['name' => null]);

    $this->actingAsCustomer($customer)
        ->postJson("/api/v1/public/shops/{$shop->slug}/bookings", ['service_id' => $service->id, 'starts_at' => '2027-06-14T08:00:00Z'])
        ->assertJsonValidationErrors('name');
});

it('refuses times that are not on offer', function (string $startsAt) {
    ['shop' => $shop, 'service' => $service] = $this->bookableShop();
    $customer = Customer::factory()->for($shop)->create();

    $this->actingAsCustomer($customer)
        ->postJson("/api/v1/public/shops/{$shop->slug}/bookings", ['service_id' => $service->id, 'starts_at' => $startsAt])
        ->assertConflict();

    expect(Booking::count())->toBe(0);
})->with([
    'before opening' => ['2027-06-14T07:00:00Z'],
    'off the slot grid' => ['2027-06-14T08:10:00Z'],
    'in the past' => ['2027-06-14T06:00:00Z'],
]);

it('gives "first available" to the first free barber in team order', function () {
    ['shop' => $shop, 'service' => $service, 'barbers' => [$first, $second]] = $this->bookableShop(barbers: 2);
    Booking::factory()->for($first)->at(Carbon::parse('2027-06-14 08:00', 'UTC'))->create(['shop_id' => $shop->id]);
    $customer = Customer::factory()->for($shop)->create();

    $this->actingAsCustomer($customer)
        ->postJson("/api/v1/public/shops/{$shop->slug}/bookings", ['service_id' => $service->id, 'starts_at' => '2027-06-14T08:00:00Z'])
        ->assertCreated()
        ->assertJsonPath('data.barber.id', $second->id);
});

it('falls back to the next free barber when one is taken in a race', function () {
    ['shop' => $shop, 'service' => $service, 'barbers' => [$first, $second]] = $this->bookableShop(barbers: 2);
    $customer = Customer::factory()->for($shop)->create();
    $start = CarbonImmutable::parse('2027-06-14 08:00', 'UTC');

    // Someone else books the first barber after availability was checked.
    $this->mock(AvailabilityService::class)->makePartial()
        ->shouldReceive('slots')->andReturnUsing(function () use ($first, $second, $start, $shop) {
            Booking::factory()->for($first)->at(Carbon::instance($start))->create(['shop_id' => $shop->id]);

            return [new Slot($first->id, $start, $start->addMinutes(30)), new Slot($second->id, $start, $start->addMinutes(30))];
        });

    $this->actingAsCustomer($customer)
        ->postJson("/api/v1/public/shops/{$shop->slug}/bookings", ['service_id' => $service->id, 'starts_at' => '2027-06-14T08:00:00Z'])
        ->assertCreated()
        ->assertJsonPath('data.barber.id', $second->id);
});

it('answers 409 when the only barber is taken in a race', function () {
    ['shop' => $shop, 'service' => $service, 'barbers' => [$barber]] = $this->bookableShop();
    $customer = Customer::factory()->for($shop)->create();
    $start = CarbonImmutable::parse('2027-06-14 08:00', 'UTC');

    $this->mock(AvailabilityService::class)->makePartial()
        ->shouldReceive('slots')->andReturnUsing(function () use ($barber, $start, $shop) {
            Booking::factory()->for($barber)->at(Carbon::instance($start))->create(['shop_id' => $shop->id]);

            return [new Slot($barber->id, $start, $start->addMinutes(30))];
        });

    $this->actingAsCustomer($customer)
        ->withHeader('Accept-Language', 'en')
        ->postJson("/api/v1/public/shops/{$shop->slug}/bookings", ['service_id' => $service->id, 'starts_at' => '2027-06-14T08:00:00Z'])
        ->assertConflict()
        ->assertJson(['message' => 'This time was just taken. Please choose another time.']);

    expect(Booking::count())->toBe(1);
});

describe('my bookings', function () {
    beforeEach(function () {
        ['shop' => $this->shop, 'barbers' => [$this->barber]] = $this->bookableShop(shopAttributes: ['cancellation_window_hours' => 24]);
        $this->customer = Customer::factory()->for($this->shop)->create();
    });

    it('lists the customer\'s own bookings only', function () {
        Booking::factory()->for($this->barber)->for($this->customer)->at(Carbon::parse('2027-06-15 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
        Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-15 09:00', 'UTC'))->create(['shop_id' => $this->shop->id]);

        $this->actingAsCustomer($this->customer)
            ->getJson("/api/v1/public/shops/{$this->shop->slug}/me/bookings")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('cancels within the cancellation window, but not after it', function () {
        $early = Booking::factory()->for($this->barber)->for($this->customer)->at(Carbon::parse('2027-06-16 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
        $soon = Booking::factory()->for($this->barber)->for($this->customer)->at(Carbon::parse('2027-06-14 12:00', 'UTC'))->create(['shop_id' => $this->shop->id]);

        $this->actingAsCustomer($this->customer)
            ->postJson("/api/v1/public/shops/{$this->shop->slug}/me/bookings/{$early->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->actingAsCustomer($this->customer)
            ->postJson("/api/v1/public/shops/{$this->shop->slug}/me/bookings/{$soon->id}/cancel")
            ->assertConflict();

        expect($soon->fresh()->status)->toBe(BookingStatus::Confirmed);
    });

    it('cannot touch another customer\'s booking', function () {
        $foreign = Booking::factory()->for($this->barber)->create(['shop_id' => $this->shop->id]);

        $this->actingAsCustomer($this->customer)
            ->postJson("/api/v1/public/shops/{$this->shop->slug}/me/bookings/{$foreign->id}/cancel")
            ->assertNotFound();
    });
});
