<?php

namespace Database\Seeders;

use App\Enums\ShopRole;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Local sign-in accounts. All use the password "password".
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The development seeder must not run in production.');
        }

        User::factory()->platformAdmin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@bookingdirect.test',
        ]);

        $shop = Shop::factory()->create([
            'name' => 'Fade Studio Göteborg',
            'slug' => 'fade-studio',
            'address' => 'Andra Långgatan 12, 413 03 Göteborg',
        ]);

        $owner = User::factory()->create(['name' => 'Sara Lind', 'email' => 'owner@bookingdirect.test']);
        $barber = User::factory()->create(['name' => 'Samir Haddad', 'email' => 'barber@bookingdirect.test']);

        $shop->users()->attach($owner, ['role' => ShopRole::Owner]);
        $shop->users()->attach($barber, ['role' => ShopRole::Barber]);
    }
}
