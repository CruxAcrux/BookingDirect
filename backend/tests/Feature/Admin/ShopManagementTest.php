<?php

use App\Models\Shop;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

dataset('admin endpoints', [
    'list' => ['get', '/api/v1/admin/shops'],
    'create' => ['post', '/api/v1/admin/shops'],
    'deactivate' => ['post', '/api/v1/admin/shops/{shop}/deactivate'],
    'activate' => ['post', '/api/v1/admin/shops/{shop}/activate'],
]);

it('is forbidden for shop staff', function (string $method, string $uri) {
    $shop = Shop::factory()->create();
    $owner = $this->staffMember($shop);

    $this->actingAs($owner)
        ->json($method, str_replace('{shop}', (string) $shop->id, $uri))
        ->assertForbidden();
})->with('admin endpoints');

it('requires authentication', function (string $method, string $uri) {
    $this->json($method, str_replace('{shop}', '1', $uri))->assertUnauthorized();
})->with('admin endpoints');

it('lists shops with their owners', function () {
    $shop = Shop::factory()->create(['name' => 'Fade Studio']);
    $owner = $this->staffMember($shop);
    Shop::factory()->create(['name' => 'Klipp & Co']);

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->getJson('/api/v1/admin/shops?search=fade')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Fade Studio')
        ->assertJsonPath('data.0.owners.0.email', $owner->email);
});

it('creates a shop and invites a new owner', function () {
    Notification::fake();

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->postJson('/api/v1/admin/shops', [
            'name' => 'Fade Studio Göteborg',
            'owner' => ['name' => 'Sara Lind', 'email' => 'Sara@Example.com'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'fade-studio-goteborg')
        ->assertJsonPath('data.owners.0.email', 'sara@example.com');

    $owner = User::where('email', 'sara@example.com')->sole();
    $shop = Shop::where('slug', 'fade-studio-goteborg')->sole();

    expect($owner->isOwnerOf($shop))->toBeTrue();
    Notification::assertSentTo($owner, ResetPasswordNotification::class);
});

it('attaches an existing user as owner without resetting their password', function () {
    Notification::fake();
    $existing = User::factory()->create(['email' => 'sara@example.com']);

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->postJson('/api/v1/admin/shops', [
            'name' => 'Second Shop',
            'owner' => ['name' => 'Ignored', 'email' => 'sara@example.com'],
        ])
        ->assertCreated();

    expect($existing->fresh()->shops)->toHaveCount(1);
    Notification::assertNothingSent();
});

it('makes generated slugs unique', function () {
    Shop::factory()->create(['slug' => 'fade-studio']);

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->postJson('/api/v1/admin/shops', [
            'name' => 'Fade Studio',
            'owner' => ['name' => 'Sara Lind', 'email' => 'sara@example.com'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'fade-studio-2');
});

it('validates the new shop', function (array $payload, array $errors) {
    Shop::factory()->create(['slug' => 'taken']);

    $this->actingAs(User::factory()->platformAdmin()->create())
        ->postJson('/api/v1/admin/shops', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($errors);
})->with([
    'missing fields' => [[], ['name', 'owner']],
    'bad slug' => [['name' => 'X', 'slug' => 'Not A Slug', 'owner' => ['name' => 'A', 'email' => 'a@example.com']], ['slug']],
    'taken slug' => [['name' => 'X', 'slug' => 'taken', 'owner' => ['name' => 'A', 'email' => 'a@example.com']], ['slug']],
    'bad owner email' => [['name' => 'X', 'owner' => ['name' => 'A', 'email' => 'nope']], ['owner.email']],
    'unknown timezone' => [['name' => 'X', 'timezone' => 'Mars/Base', 'owner' => ['name' => 'A', 'email' => 'a@example.com']], ['timezone']],
]);

it('deactivates and reactivates a shop', function () {
    $shop = Shop::factory()->create();
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/shops/{$shop->id}/deactivate")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($shop->fresh()->isActive())->toBeFalse();

    $this->actingAs($admin)
        ->postJson("/api/v1/admin/shops/{$shop->id}/activate")
        ->assertOk()
        ->assertJsonPath('data.is_active', true);
});
