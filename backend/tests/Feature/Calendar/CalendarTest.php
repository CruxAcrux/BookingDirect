<?php

namespace Tests\Feature\Calendar;

use App\Enums\ShopRole;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\BreakPeriod;
use App\Models\Customer;
use App\Models\TimeOff;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2027-06-14 07:00:00', 'UTC'));
    ['shop' => $this->shop, 'barbers' => [$this->first, $this->second]] = $this->bookableShop(barbers: 2);
    $this->owner = $this->staffMember($this->shop);
});

it('returns opening hours and every active barber\'s schedule', function () {
    BreakPeriod::factory()->for($this->first)->create(['shop_id' => $this->shop->id, 'weekday' => 1, 'starts_at' => '12:00', 'ends_at' => '12:30']);
    TimeOff::factory()->for($this->first)->create(['shop_id' => $this->shop->id, 'starts_at' => '2027-06-15 06:00', 'ends_at' => '2027-06-15 10:00']);
    TimeOff::factory()->for($this->first)->create(['shop_id' => $this->shop->id, 'starts_at' => '2027-07-15 06:00', 'ends_at' => '2027-07-15 10:00']);
    Barber::factory()->for($this->shop)->inactive()->create();

    $this->actingAs($this->owner)
        ->getJson('/api/v1/calendar?from=2027-06-14&to=2027-06-20')
        ->assertOk()
        ->assertJsonPath('data.timezone', 'Europe/Stockholm')
        ->assertJsonCount(7, 'data.opening_hours')
        ->assertJsonCount(2, 'data.barbers')
        ->assertJsonPath('data.barbers.0.id', $this->first->id)
        ->assertJsonCount(7, 'data.barbers.0.working_hours')
        ->assertJsonPath('data.barbers.0.breaks.0', ['weekday' => 1, 'starts_at' => '12:00', 'ends_at' => '12:30'])
        ->assertJsonCount(1, 'data.barbers.0.time_off');
});

it('keeps an inactive barber\'s column while they still have bookings', function () {
    $this->second->update(['is_active' => false]);
    Booking::factory()->for($this->second)->at(Carbon::parse('2027-06-15 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->owner)
        ->getJson('/api/v1/calendar?from=2027-06-14&to=2027-06-20')
        ->assertJsonCount(2, 'data.barbers');
});

it('shows barbers only their own column', function () {
    $user = $this->staffMember($this->shop, ShopRole::Barber);
    $this->second->update(['user_id' => $user->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/calendar?from=2027-06-14&to=2027-06-14')
        ->assertOk()
        ->assertJsonCount(1, 'data.barbers')
        ->assertJsonPath('data.barbers.0.id', $this->second->id);
});

it('validates the range', function () {
    $this->actingAs($this->owner)
        ->getJson('/api/v1/calendar?from=2027-06-14&to=2027-09-01')
        ->assertJsonValidationErrors('to');
});

describe('customer search', function () {
    beforeEach(function () {
        Customer::factory()->for($this->shop)->create(['name' => 'Sam Berg', 'phone' => '+46701234567']);
        Customer::factory()->for($this->shop)->create(['name' => 'Kim Lind', 'phone' => '+46709999999']);
        Customer::factory()->create(['name' => 'Sam Other Shop', 'phone' => '+46701234500']);
    });

    it('finds customers by name or by phone typed the Swedish way', function (string $search, array $names) {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/customers?search='.urlencode($search))
            ->assertOk()
            ->assertJsonPath('data.*.name', $names);
    })->with([
        'name, any case' => ['sam', ['Sam Berg']],
        'local number' => ['070-123', ['Sam Berg']],
        'international' => ['+46 709', ['Kim Lind']],
    ]);

    it('needs at least two characters', function () {
        $this->actingAs($this->owner)->getJson('/api/v1/customers?search=s')->assertJsonValidationErrors('search');
    });
});
