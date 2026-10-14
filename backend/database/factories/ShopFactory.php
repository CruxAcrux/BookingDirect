<?php

namespace Database\Factories;

use App\Enums\ShopRole;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->lastName().' Barbershop';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'phone' => '+4631'.fake()->numerify('######'),
            'email' => fake()->safeEmail(),
            'timezone' => 'Europe/Stockholm',
            'locale' => 'sv',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'deactivated_at' => now(),
        ]);
    }

    public function withMember(User $user, ShopRole $role = ShopRole::Owner): static
    {
        return $this->hasAttached($user, ['role' => $role], 'users');
    }
}
