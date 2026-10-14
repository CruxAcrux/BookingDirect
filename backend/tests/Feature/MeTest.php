<?php

use App\Enums\ShopRole;
use App\Models\Shop;
use App\Models\User;

it('requires authentication', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

it('skips deactivated shops when picking the current shop', function () {
    $closed = Shop::factory()->inactive()->create();
    $open = Shop::factory()->create();
    $user = $this->staffMember($closed);
    $open->users()->attach($user, ['role' => ShopRole::Barber]);

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.current_shop_id', $open->id)
        ->assertJsonCount(2, 'data.shops');
});

it('returns no current shop for a platform admin without memberships', function () {
    $admin = User::factory()->platformAdmin()->create();

    $this->actingAs($admin)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.is_platform_admin', true)
        ->assertJsonPath('data.current_shop_id', null)
        ->assertJsonCount(0, 'data.shops');
});
