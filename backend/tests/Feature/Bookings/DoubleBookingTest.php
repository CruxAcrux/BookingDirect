<?php

namespace Tests\Feature\Bookings;

use App\Booking\Actions\CreateStaffBooking;
use App\Booking\Exceptions\SlotTakenException;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Tenancy\CurrentShop;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2027-06-14 07:00:00', 'UTC'));
    ['shop' => $this->shop, 'service' => $this->service, 'barbers' => [$this->barber]] = $this->bookableShop();
});

it('lets the database refuse overlapping bookings even without an application check', function () {
    Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-14 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);

    Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-14 08:15', 'UTC'))->create(['shop_id' => $this->shop->id]);
})->throws(QueryException::class, 'bookings_no_overlap');

it('turns the conflict into a clean error', function () {
    $customer = Customer::factory()->for($this->shop)->create();
    app(CurrentShop::class)->set($this->shop);
    $create = app(CreateStaffBooking::class);
    $start = CarbonImmutable::parse('2027-06-14 08:00', 'UTC');

    $create->handle($customer, $this->service, $this->barber, $start, null);

    expect(fn () => $create->handle($customer, $this->service, $this->barber, $start->addMinutes(15), null))
        ->toThrow(SlotTakenException::class);
    expect(Booking::count())->toBe(1);
});

it('counts the cleanup buffer as busy time', function () {
    Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-14 08:00', 'UTC'), minutes: 30, buffer: 10)->create(['shop_id' => $this->shop->id]);

    Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-14 08:35', 'UTC'))->create(['shop_id' => $this->shop->id]);
})->throws(QueryException::class, 'bookings_no_overlap');

it('allows back-to-back bookings, other barbers and cancelled bookings to share time', function () {
    $start = Carbon::parse('2027-06-14 08:00', 'UTC');
    $own = ['shop_id' => $this->shop->id];
    Booking::factory()->for($this->barber)->at($start)->create($own);

    Booking::factory()->for($this->barber)->at($start->copy()->addMinutes(30))->create($own);
    Booking::factory()->at($start)->create();
    Booking::factory()->for($this->barber)->at($start)->status(BookingStatus::Cancelled)->create($own);

    expect(Booking::withoutGlobalScopes()->count())->toBe(4);
});
