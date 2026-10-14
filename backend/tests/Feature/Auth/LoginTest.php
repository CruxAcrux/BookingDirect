<?php

use App\Enums\ShopRole;
use App\Models\Shop;
use App\Models\User;

it('signs in a staff member and returns their shops', function () {
    $shop = Shop::factory()->create();
    $user = $this->staffMember($shop, ShopRole::Barber);

    $this->fromSpa()
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.current_shop_id', $shop->id)
        ->assertJsonPath('data.shops.0.role', 'barber');

    $this->assertAuthenticatedAs($user, 'web');
});

it('rejects a wrong password without revealing whether the email exists', function (string $email) {
    User::factory()->create(['email' => 'owner@example.com']);

    $this->fromSpa()
        ->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'wrong-password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => __('auth.failed')]);

    $this->assertGuest('web');
})->with(['owner@example.com', 'nobody@example.com']);

it('locks the email out after five failed attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->fromSpa()->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->fromSpa()
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After');

    $this->assertGuest('web');
});

it('forgets earlier failed attempts after a successful sign-in', function () {
    $user = User::factory()->create();

    foreach (range(1, 4) as $attempt) {
        $this->fromSpa()->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong']);
    }
    $this->fromSpa()->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
    $this->fromSpa()->postJson('/api/v1/auth/logout');

    $this->fromSpa()->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
    $this->fromSpa()->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
});

it('signs out and ends the session', function () {
    $user = User::factory()->create();

    $this->fromSpa()->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password']);

    $this->fromSpa()->postJson('/api/v1/auth/logout')->assertNoContent();

    $this->assertGuest('web');
});

it('requires authentication to sign out', function () {
    $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
});
