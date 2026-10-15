<?php

namespace Tests\Feature\Bookings;

use App\Enums\BookingStatus;
use App\Enums\ShopRole;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2027-06-14 07:00:00', 'UTC'));
    ['shop' => $this->shop, 'service' => $this->service, 'barbers' => [$this->first, $this->second]] = $this->bookableShop(barbers: 2);
    $this->owner = $this->staffMember($this->shop);
});

function newBooking(array $overrides = []): array
{
    return [
        'starts_at' => '2027-06-14T19:00:00+02:00',
        'customer' => ['name' => 'Sam Berg', 'phone' => '070-123 45 67'],
        ...$overrides,
    ];
}

it('lists bookings in a date range', function () {
    Booking::factory()->for($this->first)->at(Carbon::parse('2027-06-14 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
    Booking::factory()->for($this->second)->at(Carbon::parse('2027-06-15 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
    Booking::factory()->for($this->first)->at(Carbon::parse('2027-06-14 09:00', 'UTC'))->status(BookingStatus::Cancelled)->create(['shop_id' => $this->shop->id]);
    Booking::factory()->create();

    $this->actingAs($this->owner)
        ->getJson('/api/v1/bookings?from=2027-06-14&to=2027-06-14')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.starts_at_local', '2027-06-14T10:00');

    $this->actingAs($this->owner)
        ->getJson('/api/v1/bookings?from=2027-06-14&to=2027-06-15&with_cancelled=1')
        ->assertJsonCount(3, 'data');
});

it('shows barbers only their own bookings', function () {
    $user = $this->staffMember($this->shop, ShopRole::Barber);
    $this->first->update(['user_id' => $user->id]);
    Booking::factory()->for($this->first)->at(Carbon::parse('2027-06-15 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
    $colleagues = Booking::factory()->for($this->second)->at(Carbon::parse('2027-06-15 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/bookings?from=2027-06-14&to=2027-06-30&barber_id='.$this->second->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.barber_id', $this->first->id);

    $this->actingAs($user)->getJson("/api/v1/bookings/{$colleagues->id}")->assertForbidden();
});

it('creates a phone booking outside opening hours and finds the customer by phone', function () {
    $existing = Customer::factory()->for($this->shop)->create(['phone' => '+46701234567', 'name' => 'Samuel']);

    $this->actingAs($this->owner)
        ->postJson('/api/v1/bookings', newBooking(['barber_id' => $this->first->id, 'service_id' => $this->service->id, 'staff_note' => 'Called in']))
        ->assertCreated()
        ->assertJsonPath('data.source', 'staff')
        ->assertJsonPath('data.customer.id', $existing->id)
        ->assertJsonPath('data.customer.name', 'Samuel')
        ->assertJsonPath('data.starts_at_local', '2027-06-14T19:00');
});

it('refuses to double-book or book in the past', function () {
    Booking::factory()->for($this->first)->at(Carbon::parse('2027-06-14 17:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
    $base = ['barber_id' => $this->first->id, 'service_id' => $this->service->id];

    $this->actingAs($this->owner)->postJson('/api/v1/bookings', newBooking($base))->assertConflict();
    $this->actingAs($this->owner)->postJson('/api/v1/bookings', newBooking([...$base, 'starts_at' => '2027-06-14T06:00:00Z']))->assertConflict();
});

it('does not let barbers book into a colleague\'s calendar', function () {
    $user = $this->staffMember($this->shop, ShopRole::Barber);
    $this->first->update(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson('/api/v1/bookings', newBooking(['barber_id' => $this->second->id, 'service_id' => $this->service->id]))
        ->assertForbidden();
    $this->actingAs($user)
        ->postJson('/api/v1/bookings', newBooking(['barber_id' => $this->first->id, 'service_id' => $this->service->id]))
        ->assertCreated();
});

it('moves a booking to another barber and time', function () {
    $booking = Booking::factory()->for($this->first)->at(Carbon::parse('2027-06-14 08:00', 'UTC'))->create(['shop_id' => $this->shop->id, 'service_id' => $this->service->id]);

    $this->actingAs($this->owner)
        ->patchJson("/api/v1/bookings/{$booking->id}", ['starts_at' => '2027-06-14T09:00:00Z', 'barber_id' => $this->second->id, 'staff_note' => 'Moved'])
        ->assertOk()
        ->assertJsonPath('data.barber_id', $this->second->id)
        ->assertJsonPath('data.starts_at', '2027-06-14T09:00:00Z')
        ->assertJsonPath('data.staff_note', 'Moved');
});

it('cancels with a message and records outcomes', function () {
    $later = Booking::factory()->for($this->first)->at(Carbon::parse('2027-06-14 12:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
    $earlier = Booking::factory()->for($this->first)->at(Carbon::parse('2027-06-14 06:00', 'UTC'))->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->owner)
        ->postJson("/api/v1/bookings/{$later->id}/cancel", ['message' => 'Barber is ill'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled')
        ->assertJsonPath('data.cancelled_by', 'staff')
        ->assertJsonPath('data.cancellation_message', 'Barber is ill');

    $this->actingAs($this->owner)->postJson("/api/v1/bookings/{$earlier->id}/outcome", ['status' => 'no_show'])
        ->assertOk()->assertJsonPath('data.status', 'no_show');
    $this->actingAs($this->owner)->postJson("/api/v1/bookings/{$earlier->id}/outcome", ['status' => 'completed'])
        ->assertOk()->assertJsonPath('data.status', 'completed');
});

it('refuses outcomes for visits that have not started or were cancelled', function () {
    $future = Booking::factory()->for($this->first)->at(Carbon::parse('2027-06-14 12:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
    $cancelled = Booking::factory()->for($this->first)->at(Carbon::parse('2027-06-14 06:00', 'UTC'))->status(BookingStatus::Cancelled)->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->owner)->postJson("/api/v1/bookings/{$future->id}/outcome", ['status' => 'completed'])->assertConflict();
    $this->actingAs($this->owner)->postJson("/api/v1/bookings/{$cancelled->id}/outcome", ['status' => 'completed'])->assertConflict();
    $this->actingAs($this->owner)->postJson("/api/v1/bookings/{$future->id}/outcome", ['status' => 'cancelled'])->assertUnprocessable();
});

it('returns 404 for another shop\'s booking', function () {
    $foreign = Booking::factory()->create();

    $this->actingAs($this->owner)->getJson("/api/v1/bookings/{$foreign->id}")->assertNotFound();
    $this->actingAs($this->owner)->postJson("/api/v1/bookings/{$foreign->id}/cancel")->assertNotFound();
});

it('refuses a barber from another shop', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/bookings', newBooking(['barber_id' => Barber::factory()->create()->id, 'service_id' => $this->service->id]))
        ->assertJsonValidationErrors('barber_id');
});
