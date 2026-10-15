<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\SmsType;
use App\Models\Booking;
use App\Notifications\Booking\BookingReminder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Remind customers a day and two hours before their visit';

    public function handle(): int
    {
        $dayBefore = $this->remind(SmsType::Reminder24h, 'reminded_24h_at', hours: 24, notWithinHours: 2);
        $soon = $this->remind(SmsType::Reminder2h, 'reminded_2h_at', hours: 2, notWithinHours: 0);

        $this->info("Sent {$dayBefore} day-before and {$soon} two-hour reminders.");

        return self::SUCCESS;
    }

    /**
     * Reminds bookings that start within `$hours`. A booking only gets a reminder if it was
     * booked (or last moved) before the reminder was due: someone booking this afternoon's
     * cut has just had the confirmation. When both are due, only the closer one is sent.
     */
    private function remind(SmsType $kind, string $column, int $hours, int $notWithinHours): int
    {
        $sent = 0;

        Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereNull($column)
            ->where('starts_at', '>', now()->addHours($notWithinHours))
            ->where('starts_at', '<=', now()->addHours($hours))
            ->whereRaw('scheduled_at <= starts_at - make_interval(hours => ?)', [$hours])
            ->whereHas('shop', fn ($query) => $query->whereNull('deactivated_at'))
            ->with(['shop', 'customer', 'barber', 'service'])
            ->chunkById(200, function (Collection $bookings) use ($kind, $column, &$sent): void {
                foreach ($bookings as $booking) {
                    // Claiming the booking first means overlapping runs never send twice.
                    $claimed = Booking::query()->whereKey($booking->id)->whereNull($column)->update([$column => now()]);

                    if ($claimed === 1) {
                        $booking->customer->notify(new BookingReminder($booking, $kind));
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
