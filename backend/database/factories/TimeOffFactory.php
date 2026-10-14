<?php

namespace Database\Factories;

use App\Enums\TimeOffReason;
use App\Models\Barber;
use App\Models\TimeOff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeOff>
 */
class TimeOffFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = now()->addDays(fake()->numberBetween(1, 30))->startOfDay();

        return [
            'barber_id' => Barber::factory(),
            'shop_id' => fn (array $attributes) => Barber::withoutGlobalScopes()->whereKey($attributes['barber_id'])->value('shop_id'),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays(fake()->numberBetween(1, 7)),
            'reason' => fake()->randomElement(TimeOffReason::cases()),
            'note' => null,
        ];
    }
}
