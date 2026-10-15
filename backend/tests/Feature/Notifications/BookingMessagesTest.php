<?php

namespace Tests\Feature\Notifications;

use App\Enums\BookingStatus;
use App\Enums\SmsType;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\SmsMessage;
use App\Notifications\Booking\BookingConfirmed;
use App\Notifications\Booking\BookingRescheduled;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2027-06-14 07:00:00', 'UTC'));
    ['shop' => $this->shop, 'service' => $this->service, 'barbers' => [$this->barber]] = $this->bookableShop(shopAttributes: [
        'name' => 'Fade Studio', 'address' => 'Linnégatan 12, Göteborg',
    ]);
    $this->barber->update(['display_name' => 'Ali']);
    $this->service->update(['name' => 'Skin fade']);
});

function bookOnline(Customer $customer, string $startsAt = '2027-06-16T10:00:00+02:00')
{
    return test()->actingAsCustomer($customer)->postJson('/api/v1/public/shops/'.test()->shop->slug.'/bookings', [
        'service_id' => test()->service->id, 'barber_id' => test()->barber->id, 'starts_at' => $startsAt, 'name' => 'Sam',
    ]);
}

function sentSms(): SmsMessage
{
    return SmsMessage::withoutGlobalScopes()->latest('id')->firstOrFail();
}

it('texts a confirmation with the manage link in the customer\'s language', function (string $locale, string $expected) {
    $customer = Customer::factory()->for($this->shop)->create(['locale' => $locale, 'email' => null]);

    $token = bookOnline($customer)->assertCreated()->json('manage_token');

    $sms = sentSms();
    expect($sms->type)->toBe(SmsType::BookingConfirmed)
        ->and($sms->to)->toBe($customer->phone)
        ->and($sms->shop_id)->toBe($this->shop->id)
        ->and($sms->related_id)->toBe(Booking::sole()->id)
        ->and($sms->body)->toContain($expected)
        ->and($sms->body)->toContain("/b/{$token}");
})->with([
    'Swedish' => ['sv', 'Bokat hos Fade Studio: Skin fade med Ali, onsdag 16 juni kl. 10:00.'],
    'English' => ['en', 'Booked at Fade Studio: Skin fade with Ali, Wednesday 16 June at 10:00.'],
]);

it('emails a confirmation with a calendar file when the customer has an email address', function () {
    Notification::fake();
    $customer = Customer::factory()->for($this->shop)->create(['email' => 'sam@example.com', 'locale' => 'en']);

    bookOnline($customer)->assertCreated();

    Notification::assertSentTo($customer, BookingConfirmed::class, function (BookingConfirmed $notification, array $channels) use ($customer) {
        $mail = $notification->toMail($customer);
        $ics = $mail->rawAttachments[0]['data'];

        return $channels === [SmsChannel::class, 'mail']
            && $mail->subject === 'Booking confirmed: Fade Studio, Wednesday 16 June at 10:00'
            && $mail->from === [config('mail.from.address'), 'Fade Studio']
            && str_contains($ics, "UID:booking-{$notification->booking->id}@")
            && str_contains($ics, 'DTSTART:20270616T080000Z')
            && str_contains($ics, 'LOCATION:Linnégatan 12\, Göteborg');
    });
});

it('only texts customers without an email address', function () {
    Notification::fake();
    $customer = Customer::factory()->for($this->shop)->create(['email' => null]);

    bookOnline($customer)->assertCreated();

    Notification::assertSentTo($customer, BookingConfirmed::class, fn ($notification, array $channels) => $channels === [SmsChannel::class]);
});

it('sends nothing when the booking fails', function () {
    Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-16 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
    Notification::fake();

    bookOnline(Customer::factory()->for($this->shop)->create())->assertConflict();

    Notification::assertNothingSent();
});

it('tells the customer about a new time and starts its reminders over', function () {
    $booking = Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-16 08:00', 'UTC'))->create([
        'shop_id' => $this->shop->id, 'service_id' => $this->service->id, 'reminded_24h_at' => now(),
    ]);
    $owner = $this->staffMember($this->shop);

    $this->actingAs($owner)->patchJson("/api/v1/bookings/{$booking->id}", ['starts_at' => '2027-06-17T09:00:00Z', 'barber_id' => $this->barber->id])
        ->assertOk();

    expect(sentSms()->type)->toBe(SmsType::BookingRescheduled)
        ->and(sentSms()->body)->toContain('flyttad till torsdag 17 juni kl. 11:00')
        ->and($booking->fresh())
        ->reminded_24h_at->toBeNull()
        ->scheduled_at->toEqual(now());
});

it('passes on the shop\'s message when staff cancel', function () {
    $booking = Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-16 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);

    $this->actingAs($this->staffMember($this->shop))
        ->postJson("/api/v1/bookings/{$booking->id}/cancel", ['message' => 'Ali is ill today.'])
        ->assertOk();

    expect(sentSms())
        ->type->toBe(SmsType::BookingCancelled)
        ->body->toContain('Fade Studio har tyvärr avbokat din tid onsdag 16 juni kl. 10:00')
        ->body->toContain("/s/{$this->shop->slug}")
        ->body->toEndWith('Ali is ill today.');
});

it('drops queued messages about a time that has since changed', function () {
    $booking = Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-16 08:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
    $confirmed = new BookingConfirmed($booking);
    $moved = new BookingRescheduled($booking);

    $booking->update(['status' => BookingStatus::Cancelled]);
    expect($confirmed->shouldSend($booking->customer, 'mail'))->toBeFalse();

    $booking->update(['status' => BookingStatus::Confirmed, 'starts_at' => Carbon::parse('2027-06-16 07:45', 'UTC')]);
    expect($moved->shouldSend($booking->customer, 'mail'))->toBeFalse();
});

it('sends nothing when a visit is marked completed', function () {
    $booking = Booking::factory()->for($this->barber)->at(Carbon::parse('2027-06-14 06:00', 'UTC'))->create(['shop_id' => $this->shop->id]);
    Notification::fake();

    $this->actingAs($this->staffMember($this->shop))
        ->postJson("/api/v1/bookings/{$booking->id}/outcome", ['status' => 'completed'])
        ->assertOk();

    Notification::assertNothingSent();
});
