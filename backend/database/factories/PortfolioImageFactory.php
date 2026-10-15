<?php

namespace Database\Factories;

use App\Models\Barber;
use App\Models\PortfolioImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PortfolioImage>
 */
class PortfolioImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'barber_id' => Barber::factory(),
            'shop_id' => fn (array $attributes) => Barber::withoutGlobalScopes()->whereKey($attributes['barber_id'])->value('shop_id'),
            'path' => 'portfolio/'.fake()->uuid().'.webp',
            'thumbnail_path' => 'portfolio/thumbnails/'.fake()->uuid().'.webp',
            'caption' => fake()->optional()->sentence(4),
            'sort_order' => 0,
        ];
    }
}
