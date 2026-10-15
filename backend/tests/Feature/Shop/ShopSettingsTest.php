<?php

use App\Enums\ShopRole;
use App\Models\Shop;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->shop = Shop::factory()->create(['slug' => 'fade-studio']);
    $this->owner = $this->staffMember($this->shop);
});

it('shows the shop settings to staff', function () {
    $barber = $this->staffMember($this->shop, ShopRole::Barber);

    $this->actingAs($barber)
        ->getJson('/api/v1/shop')
        ->assertOk()
        ->assertJsonPath('data.slug', 'fade-studio')
        ->assertJsonPath('data.slot_interval_minutes', 15);
});

it('lets the owner update settings and normalizes the phone number', function () {
    $this->actingAs($this->owner)
        ->patchJson('/api/v1/shop', [
            'name' => 'Fade Studio Göteborg',
            'phone' => '031-12 34 56',
            'slot_interval_minutes' => 30,
            'walk_ins_enabled' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.phone', '+4631123456')
        ->assertJsonPath('data.slot_interval_minutes', 30)
        ->assertJsonPath('data.walk_ins_enabled', false);
});

it('does not let barbers change settings', function () {
    $barber = $this->staffMember($this->shop, ShopRole::Barber);

    $this->actingAs($barber)->patchJson('/api/v1/shop', ['name' => 'Mine now'])->assertForbidden();

    expect($this->shop->fresh()->name)->not->toBe('Mine now');
});

it('validates settings', function (array $payload, string $field) {
    Shop::factory()->create(['slug' => 'taken']);

    $this->actingAs($this->owner)
        ->patchJson('/api/v1/shop', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'slot interval' => [['slot_interval_minutes' => 7], 'slot_interval_minutes'],
    'taken slug' => [['slug' => 'taken'], 'slug'],
    'phone' => [['phone' => '12'], 'phone'],
    'timezone' => [['timezone' => 'Nowhere/City'], 'timezone'],
]);

it('keeps the shop\'s own slug valid when saving', function () {
    $this->actingAs($this->owner)
        ->patchJson('/api/v1/shop', ['slug' => 'fade-studio'])
        ->assertOk();
});

it('stores the logo as a resized WebP and replaces the old one', function () {
    Storage::fake('public');

    $first = $this->actingAs($this->owner)
        ->postJson('/api/v1/shop/logo', ['image' => UploadedFile::fake()->image('logo.png', 1200, 900)])
        ->assertOk()
        ->json('data.logo_url');

    $oldPath = $this->shop->fresh()->logo_path;
    expect($oldPath)->toEndWith('.webp');
    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($oldPath));
    expect([$width, $height])->toBe([512, 384])->and($first)->toContain($oldPath);

    $this->actingAs($this->owner)
        ->postJson('/api/v1/shop/logo', ['image' => UploadedFile::fake()->image('logo2.png', 300, 300)])
        ->assertOk();

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($this->shop->fresh()->logo_path);
});

it('removes the cover image', function () {
    Storage::fake('public');
    $this->actingAs($this->owner)->postJson('/api/v1/shop/cover', ['image' => UploadedFile::fake()->image('c.jpg', 2400, 1200)]);
    $path = $this->shop->fresh()->cover_path;

    $this->actingAs($this->owner)
        ->deleteJson('/api/v1/shop/cover')
        ->assertOk()
        ->assertJsonPath('data.cover_url', null);

    Storage::disk('public')->assertMissing($path);
});

it('rejects files that are not images', function () {
    Storage::fake('public');

    $this->actingAs($this->owner)
        ->postJson('/api/v1/shop/logo', ['image' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf')])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});
