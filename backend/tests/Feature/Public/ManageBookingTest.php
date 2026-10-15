<?php

namespace Tests\Feature\Public;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2027-06-14 07:00:00', 'UTC'));
    ['shop' => $this->shop, 'service' => $this->service, 'barbers' => [$this->barber]] = $this->bookableShop(shopAttributes: ['cancellation_window_hours' => 24]);

    $this->booking = Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-16 08:00', 'UTC'))
        ->create(['shop_id' => $this->shop->id, 'service_id' => $this->service->id]);
    $this->token = $this->booking->issueManageToken();
    $this->booking->save();
});

it('shows a booking by its token', function () {
    $this->getJson("/api/v1/public/bookings/{$this->token}")
        ->assertOk()
        ->assertJsonPath('data.id', $this->booking->id)
        ->assertJsonPath('data.shop.slug', $this->shop->slug)
        ->assertJsonPath('data.change_deadline', '2027-06-15T08:00:00Z');
});

it('returns 404 for unknown tokens and closed shops', function () {
    $this->getJson('/api/v1/public/bookings/not-a-real-token')->assertNotFound();

    $this->shop->update([]);
    $this->shop->forceFill(['deactivated_at' => now()])->save();
    $this->getJson("/api/v1/public/bookings/{$this->token}")->assertNotFound();
});

it('cancels by token', function () {
    $this->postJson("/api/v1/public/bookings/{$this->token}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled')
        ->assertJsonPath('data.can_change', false);

    expect($this->booking->fresh()->cancelled_by->value)->toBe('customer');
});

it('moves the booking to another free time', function () {
    $this->postJson("/api/v1/public/bookings/{$this->token}/reschedule", ['starts_at' => '2027-06-17T09:00:00Z'])
        ->assertOk()
        ->assertJsonPath('data.starts_at', '2027-06-17T09:00:00Z');
});

it('can move a booking into time it overlaps itself', function () {
    $this->postJson("/api/v1/public/bookings/{$this->token}/reschedule", ['starts_at' => '2027-06-16T08:30:00Z'])
        ->assertOk();
});

it('refuses to move a booking onto a taken time', function () {
    Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-17 09:00', 'UTC'))->create(['shop_id' => $this->shop->id]);

    $this->postJson("/api/v1/public/bookings/{$this->token}/reschedule", ['starts_at' => '2027-06-17T09:00:00Z'])
        ->assertConflict();

    expect($this->booking->fresh()->starts_at->toIso8601ZuluString())->toBe('2027-06-16T08:00:00Z');
});

it('refuses changes once the cancellation window has closed', function () {
    $this->travelTo(Carbon::parse('2027-06-15 09:00:00', 'UTC'));

    $this->postJson("/api/v1/public/bookings/{$this->token}/cancel")->assertConflict();
    $this->postJson("/api/v1/public/bookings/{$this->token}/reschedule", ['starts_at' => '2027-06-17T09:00:00Z'])->assertConflict();

    expect($this->booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});
