<?php

namespace Database\Factories;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        // Based on Carbon's clock (not PHP's), so it follows travelTo() in tests.
        $startsAt = Carbon::now()->addDays(fake()->numberBetween(1, 14))->setTime(fake()->numberBetween(8, 16), 0);

        return [
            'barber_id' => Barber::factory(),
            'shop_id' => fn (array $attributes) => Barber::withoutGlobalScopes()->whereKey($attributes['barber_id'])->value('shop_id'),
            'customer_id' => fn (array $attributes) => Customer::factory()->create(['shop_id' => $attributes['shop_id']])->id,
            'service_id' => fn (array $attributes) => Service::factory()->create(['shop_id' => $attributes['shop_id']])->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'blocked_until' => $startsAt->copy()->addMinutes(30),
            'status' => BookingStatus::Confirmed,
            'source' => BookingSource::Online,
            'price_ore' => 35000,
            'duration_minutes' => 30,
            'buffer_minutes' => 0,
            'scheduled_at' => Carbon::now()->subWeek(),
            'manage_token_hash' => fn (array $attributes) => Booking::hashToken($attributes['manage_token']),
            'manage_token' => Str::random(40),
        ];
    }

    public function at(Carbon $startsAt, int $minutes = 30, int $buffer = 0): static
    {
        return $this->state(fn () => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($minutes),
            'blocked_until' => $startsAt->copy()->addMinutes($minutes + $buffer),
            'duration_minutes' => $minutes,
            'buffer_minutes' => $buffer,
        ]);
    }

    public function status(BookingStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
