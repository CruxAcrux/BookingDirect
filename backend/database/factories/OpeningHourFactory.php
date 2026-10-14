<?php

namespace Database\Factories;

use App\Enums\Weekday;
use App\Models\OpeningHour;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpeningHour>
 */
class OpeningHourFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'weekday' => fake()->randomElement(Weekday::cases()),
            'opens_at' => '09:00',
            'closes_at' => '18:00',
        ];
    }
}
