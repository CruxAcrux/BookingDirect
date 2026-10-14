<?php

namespace Database\Factories;

use App\Enums\Weekday;
use App\Models\Barber;
use App\Models\BreakPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BreakPeriod>
 */
class BreakPeriodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'barber_id' => Barber::factory(),
            'shop_id' => fn (array $attributes) => Barber::withoutGlobalScopes()->whereKey($attributes['barber_id'])->value('shop_id'),
            'weekday' => fake()->randomElement(Weekday::cases()),
            'starts_at' => '12:00',
            'ends_at' => '12:30',
        ];
    }
}
