<?php

namespace Tests\Feature\Notifications;

use App\Enums\BookingStatus;
use App\Enums\SmsType;
use App\Models\Booking;
use App\Models\SmsMessage;
use App\Notifications\Booking\BookingReminder;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2027-06-14 07:00:00', 'UTC'));
    ['shop' => $this->shop, 'barbers' => [$this->barber]] = $this->bookableShop();
});

function bookingIn(string $startsIn, array $attributes = []): Booking
{
    return Booking::factory()->for(test()->barber)->at(now()->add($startsIn))
        ->create(['shop_id' => test()->shop->id, ...$attributes]);
}

function reminders(SmsType $kind): array
{
    return SmsMessage::withoutGlobalScopes()->where('type', $kind)->pluck('related_id')->all();
}

it('reminds a day before and two hours before, once each', function () {
    $tomorrow = bookingIn('23 hours 50 minutes');
    $soon = bookingIn('1 hour 55 minutes');
    $later = bookingIn('2 days');

    $this->artisan('bookings:send-reminders')->assertSuccessful();
    $this->artisan('bookings:send-reminders')->assertSuccessful();

    expect(reminders(SmsType::Reminder24h))->toBe([$tomorrow->id])
        ->and(reminders(SmsType::Reminder2h))->toBe([$soon->id])
        ->and($tomorrow->fresh()->reminded_24h_at)->not->toBeNull()
        ->and($later->fresh()->reminded_24h_at)->toBeNull();
});

it('only sends the closer reminder when both are due', function () {
    $booking = bookingIn('90 minutes');

    $this->artisan('bookings:send-reminders');

    expect(reminders(SmsType::Reminder2h))->toBe([$booking->id])
        ->and(reminders(SmsType::Reminder24h))->toBe([]);
});

it('skips reminders that were due before the time was booked', function () {
    bookingIn('20 hours', ['scheduled_at' => now()->subHour()]);
    $bookedJustNow = bookingIn('90 minutes', ['scheduled_at' => now()->subMinutes(10)]);

    $this->artisan('bookings:send-reminders');

    expect(reminders(SmsType::Reminder24h))->toBe([])
        ->and(reminders(SmsType::Reminder2h))->toBe([]);

    $bookedJustNow->forceFill(['scheduled_at' => now()->subHours(3)])->save();
    $this->artisan('bookings:send-reminders');
    expect(reminders(SmsType::Reminder2h))->toBe([$bookedJustNow->id]);
});

it('leaves cancelled bookings and closed shops alone', function () {
    bookingIn('20 hours', ['status' => BookingStatus::Cancelled]);
    Booking::factory()->at(now()->addHours(20))->create()->shop->forceFill(['deactivated_at' => now()])->save();

    $this->artisan('bookings:send-reminders');

    expect(SmsMessage::withoutGlobalScopes()->count())->toBe(0);
});

it('emails the day-before reminder but only texts the two-hour one', function () {
    Notification::fake();
    $tomorrow = bookingIn('20 hours');
    $soon = bookingIn('1 hour');
    $tomorrow->customer->update(['email' => 'sam@example.com']);
    $soon->customer->update(['email' => 'kim@example.com']);

    $this->artisan('bookings:send-reminders');

    Notification::assertSentTo($tomorrow->customer, BookingReminder::class, fn ($n, array $channels) => $channels === [SmsChannel::class, 'mail']);
    Notification::assertSentTo($soon->customer, BookingReminder::class, fn ($n, array $channels) => $channels === [SmsChannel::class]);
});
