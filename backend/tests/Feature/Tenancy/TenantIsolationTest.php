<?php

use App\Enums\ShopRole;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Shop;
use App\Tenancy\CurrentShop;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->shopA = Shop::factory()->create();
    $this->shopB = Shop::factory()->create();
});

describe('model scoping', function () {
    it('only returns records of the current shop', function (string $model) {
        $own = $model::factory()->for($this->shopA)->create();
        $foreign = $model::factory()->for($this->shopB)->create();

        app(CurrentShop::class)->set($this->shopA);

        expect($model::pluck('id')->all())->toBe([$own->id])
            ->and($model::find($foreign->id))->toBeNull();
    })->with([Barber::class, Service::class, Customer::class]);

    it('assigns the current shop to new records', function () {
        app(CurrentShop::class)->set($this->shopA);

        $service = Service::factory()->make(['shop_id' => null]);
        $service->save();

        expect($service->shop_id)->toBe($this->shopA->id);
    });

    it('refuses to create a record for another shop', function () {
        app(CurrentShop::class)->set($this->shopA);

        Service::factory()->for($this->shopB)->create();
    })->throws(LogicException::class);
});

describe('staff routes', function () {
    beforeEach(function () {
        Route::middleware(['api', 'auth:sanctum', 'shop'])
            ->get('/api/v1/_test/barbers/{barber}', fn (Barber $barber) => ['id' => $barber->id]);
    });

    it('resolves records of the staff member\'s shop', function () {
        $barber = Barber::factory()->for($this->shopA)->create();

        $this->actingAs($this->staffMember($this->shopA))
            ->getJson("/api/v1/_test/barbers/{$barber->id}")
            ->assertOk()
            ->assertJson(['id' => $barber->id]);
    });

    it('returns 404 for another shop\'s records', function () {
        $foreign = Barber::factory()->for($this->shopB)->create();

        $this->actingAs($this->staffMember($this->shopA, ShopRole::Barber))
            ->getJson("/api/v1/_test/barbers/{$foreign->id}")
            ->assertNotFound();
    });

    it('blocks staff whose only shop is deactivated', function () {
        $closed = Shop::factory()->inactive()->create();
        $barber = Barber::factory()->for($closed)->create();

        $this->actingAs($this->staffMember($closed))
            ->getJson("/api/v1/_test/barbers/{$barber->id}")
            ->assertForbidden()
            ->assertJson(['message' => __('shop.no_active_shop')]);
    });
});
