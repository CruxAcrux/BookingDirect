<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\ShopRole;
use App\Enums\Weekday;
use App\Models\Barber;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Shop;
use App\Models\User;
use App\Tenancy\CurrentShop;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Local sign-in accounts and a small shop setup. All accounts use the password "password".
     */
    public function run(CurrentShop $currentShop): void
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
            'phone' => '+4631123456',
        ]);

        $owner = User::factory()->create(['name' => 'Sara Lind', 'email' => 'owner@bookingdirect.test']);
        $barberUser = User::factory()->create(['name' => 'Samir Haddad', 'email' => 'barber@bookingdirect.test']);

        $shop->users()->attach($owner, ['role' => ShopRole::Owner]);
        $shop->users()->attach($barberUser, ['role' => ShopRole::Barber]);

        $currentShop->runAs($shop, function () use ($shop, $barberUser): void {
            foreach ([Weekday::Monday, Weekday::Tuesday, Weekday::Wednesday, Weekday::Thursday, Weekday::Friday] as $day) {
                $shop->openingHours()->create(['weekday' => $day, 'opens_at' => '10:00', 'closes_at' => '19:00']);
            }
            $shop->openingHours()->create(['weekday' => Weekday::Saturday, 'opens_at' => '10:00', 'closes_at' => '16:00']);

            $services = collect([
                ['Klippning', 30, 32000, 28],
                ['Skinfade', 45, 42000, 21],
                ['Klippning + skägg', 60, 52000, 28],
                ['Skäggtrim', 20, 22000, 21],
                ['Barnklippning', 30, 25000, null],
            ])->map(fn (array $service, int $index): Service => Service::create([
                'name' => $service[0],
                'duration_minutes' => $service[1],
                'buffer_minutes' => 5,
                'price_ore' => $service[2],
                'rebook_after_days' => $service[3],
                'sort_order' => $index,
            ]));

            $barbers = collect([
                ['Samir', 'Skinfades och skarpa linjer.', $barberUser->id],
                ['Ali', 'Klassiska klippningar och rakning med kniv.', null],
            ])->map(fn (array $barber, int $index): Barber => Barber::create([
                'display_name' => $barber[0],
                'bio' => $barber[1],
                'user_id' => $barber[2],
                'sort_order' => $index,
            ]));

            foreach ($barbers as $barber) {
                $barber->services()->sync($services->pluck('id'));
            }

            $this->seedBookings($shop, $barbers->values()->all(), $services->values()->all());

            foreach ($barbers as $barber) {

                foreach ([Weekday::Tuesday, Weekday::Wednesday, Weekday::Thursday, Weekday::Friday, Weekday::Saturday] as $day) {
                    $barber->workingHours()->create(['weekday' => $day, 'starts_at' => '10:00', 'ends_at' => $day === Weekday::Saturday ? '16:00' : '19:00']);
                    $barber->breaks()->create(['weekday' => $day, 'starts_at' => '13:00', 'ends_at' => '13:30']);
                }
            }
        });
    }

    /**
     * A handful of bookings around today, so the calendar has something to show.
     *
     * @param  array<int, Barber>  $barbers
     * @param  array<int, Service>  $services
     */
    private function seedBookings(Shop $shop, array $barbers, array $services): void
    {
        $customers = Customer::factory()->for($shop)->count(6)->create()->values()->all();
        $plan = [
            // [day offset, local time, barber, service, status]
            [-1, '10:00', 0, 1, BookingStatus::Completed],
            [-1, '14:00', 1, 0, BookingStatus::Confirmed],
            [0, '10:00', 0, 0, BookingStatus::Confirmed],
            [0, '11:00', 1, 2, BookingStatus::Confirmed],
            [0, '15:30', 0, 3, BookingStatus::Confirmed],
            [1, '10:30', 1, 1, BookingStatus::Confirmed],
            [1, '13:30', 0, 0, BookingStatus::Cancelled],
            [2, '16:00', 0, 2, BookingStatus::Confirmed],
        ];

        foreach ($plan as $index => [$offset, $time, $barberIndex, $serviceIndex, $status]) {
            $service = $services[$serviceIndex];
            $startsAt = CarbonImmutable::now($shop->timezone)->startOfDay()->addDays($offset)->setTimeFromTimeString($time)->utc();

            Booking::factory()
                ->for($barbers[$barberIndex])
                ->for($customers[$index % count($customers)] ?? Customer::factory()->for($shop))
                ->for($service)
                ->at(Carbon::instance($startsAt), $service->duration_minutes, $service->buffer_minutes)
                ->status($status)
                ->create(['price_ore' => $service->price_ore]);
        }
    }
}
