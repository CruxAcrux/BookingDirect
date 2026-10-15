<?php

namespace Tests\Feature\Sms;

use App\Enums\ShopRole;
use App\Enums\SmsType;
use App\Models\Booking;
use App\Models\Shop;
use App\Sms\SmsSender;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->owner = $this->staffMember($this->shop);
});

function logSms(Shop $shop, string $to, SmsType $type, ?Booking $booking = null): void
{
    app(SmsSender::class)->send($shop, $to, "Message to {$to}", $type, $booking);
}

it('lists the shop\'s messages newest first, linked to their booking', function () {
    $booking = Booking::factory()->create(['shop_id' => $this->shop->id]);
    $this->travelTo(Carbon::parse('2027-06-14 07:00', 'UTC'));
    logSms($this->shop, '+46701111111', SmsType::Otp);
    $this->travel(1)->minute();
    logSms($this->shop, '+46702222222', SmsType::BookingConfirmed, $booking);
    logSms(Shop::factory()->create(), '+46703333333', SmsType::Otp);

    $this->actingAs($this->owner)->getJson('/api/v1/sms-messages')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.to', '+46702222222')
        ->assertJsonPath('data.0.type', 'booking_confirmed')
        ->assertJsonPath('data.0.booking_id', $booking->id)
        ->assertJsonPath('data.0.sent_at', '2027-06-14T07:01:00.000000Z')
        ->assertJsonPath('data.1.booking_id', null);
});

it('filters by type and by phone number typed the Swedish way', function () {
    logSms($this->shop, '+46701111111', SmsType::Otp);
    logSms($this->shop, '+46701111111', SmsType::Reminder24h);
    logSms($this->shop, '+46702222222', SmsType::Reminder24h);

    $this->actingAs($this->owner)->getJson('/api/v1/sms-messages?type=reminder_24h&phone=070-111')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'reminder_24h')
        ->assertJsonPath('data.0.to', '+46701111111');

    $this->actingAs($this->owner)->getJson('/api/v1/sms-messages?type=marketing')->assertJsonValidationErrors('type');
});

it('is for owners only', function () {
    $barber = $this->staffMember($this->shop, ShopRole::Barber);

    $this->actingAs($barber)->getJson('/api/v1/sms-messages')->assertForbidden();
});
