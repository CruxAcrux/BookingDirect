<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'name' => fake()->name(),
            'phone' => '+467'.fake()->unique()->numerify('########'),
            'email' => fake()->optional()->safeEmail(),
            'locale' => 'sv',
            'notes' => null,
            'marketing_consent_at' => fake()->optional()->dateTimeBetween('-1 year'),
        ];
    }
}
