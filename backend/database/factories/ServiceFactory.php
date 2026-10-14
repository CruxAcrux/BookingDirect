<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => fake()->randomElement(['Klippning', 'Skinfade', 'Skäggtrim', 'Klippning + skägg', 'Barnklippning', 'Rakning']),
            'description' => fake()->sentence(),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'buffer_minutes' => fake()->randomElement([0, 5, 10]),
            'price_ore' => fake()->numberBetween(20, 70) * 1000,
            'rebook_after_days' => fake()->optional()->randomElement([21, 28, 35]),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
