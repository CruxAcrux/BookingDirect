<?php

use App\Enums\ShopRole;
use App\Models\Barber;
use App\Models\Service;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
    $this->owner = $this->staffMember($this->shop);
});

it('lists the shop\'s barbers with their logins and services', function () {
    $service = Service::factory()->for($this->shop)->create();
    [, $barber] = $this->barberWithLogin($this->shop);
    $barber->services()->attach($service, ['price_ore' => 50000]);
    Barber::factory()->create();

    $this->actingAs($this->owner)
        ->getJson('/api/v1/barbers')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.email', $barber->user->email)
        ->assertJsonPath('data.0.services.0', ['service_id' => $service->id, 'price_ore' => 50000, 'duration_minutes' => null]);
});

it('creates a barber without a login', function () {
    $this->actingAs($this->owner)
        ->postJson('/api/v1/barbers', ['display_name' => 'Ali', 'bio' => 'Fades and beards.'])
        ->assertCreated()
        ->assertJsonPath('data.display_name', 'Ali')
        ->assertJsonPath('data.user', null)
        ->assertJsonPath('data.is_active', true);
});

it('lets a barber edit their own name and bio, but nothing else', function () {
    [$user, $barber] = $this->barberWithLogin($this->shop);

    $this->actingAs($user)
        ->patchJson("/api/v1/barbers/{$barber->id}", ['bio' => 'Classic cuts.'])
        ->assertOk()
        ->assertJsonPath('data.bio', 'Classic cuts.')
        ->assertJsonPath('data.is_me', true);

    $this->actingAs($user)
        ->patchJson("/api/v1/barbers/{$barber->id}", ['is_active' => false])
        ->assertForbidden();

    expect($barber->fresh()->is_active)->toBeTrue();
});

it('does not let a barber edit a colleague', function () {
    [$user] = $this->barberWithLogin($this->shop);
    $colleague = Barber::factory()->for($this->shop)->create();

    $this->actingAs($user)
        ->patchJson("/api/v1/barbers/{$colleague->id}", ['bio' => 'Hi'])
        ->assertForbidden();
});

it('only lets owners create, remove and reorder barbers', function () {
    [$user, $barber] = $this->barberWithLogin($this->shop);

    $this->actingAs($user)->postJson('/api/v1/barbers', ['display_name' => 'X'])->assertForbidden();
    $this->actingAs($user)->deleteJson("/api/v1/barbers/{$barber->id}")->assertForbidden();
    $this->actingAs($user)->putJson('/api/v1/barbers/order', ['ids' => [$barber->id]])->assertForbidden();
});

it('returns 404 for another shop\'s barber', function () {
    $foreign = Barber::factory()->create();

    $this->actingAs($this->owner)->getJson("/api/v1/barbers/{$foreign->id}")->assertNotFound();
    $this->actingAs($this->owner)->patchJson("/api/v1/barbers/{$foreign->id}", ['bio' => 'x'])->assertNotFound();
});

describe('invitations', function () {
    it('creates a login and emails a link to choose a password', function () {
        Notification::fake();
        $barber = Barber::factory()->for($this->shop)->create(['display_name' => 'Samir']);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/barbers/{$barber->id}/invite", ['email' => 'Samir@Example.com'])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'samir@example.com')
            ->assertJsonPath('data.user.name', 'Samir');

        $user = User::where('email', 'samir@example.com')->sole();
        expect($user->roleIn($this->shop))->toBe(ShopRole::Barber);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    });

    it('links an owner who also cuts hair without changing their role', function () {
        Notification::fake();
        $barber = Barber::factory()->for($this->shop)->create();

        $this->actingAs($this->owner)
            ->postJson("/api/v1/barbers/{$barber->id}/invite", ['email' => $this->owner->email])
            ->assertOk();

        expect($this->owner->fresh()->roleIn($this->shop))->toBe(ShopRole::Owner)
            ->and($barber->fresh()->user_id)->toBe($this->owner->id);
        Notification::assertNothingSent();
    });

    it('refuses to link one person to two barbers', function () {
        [$user] = $this->barberWithLogin($this->shop);
        $other = Barber::factory()->for($this->shop)->create();

        $this->actingAs($this->owner)
            ->postJson("/api/v1/barbers/{$other->id}/invite", ['email' => $user->email])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('refuses to invite a barber who already has a login', function () {
        [, $barber] = $this->barberWithLogin($this->shop);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/barbers/{$barber->id}/invite", ['email' => 'new@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });
});

it('removes a barber and their access, but keeps the profile for history', function () {
    [$user, $barber] = $this->barberWithLogin($this->shop);

    $this->actingAs($this->owner)->deleteJson("/api/v1/barbers/{$barber->id}")->assertNoContent();

    expect($barber->fresh()->trashed())->toBeTrue()
        ->and($user->fresh()->roleIn($this->shop))->toBeNull();

    $this->actingAs($user->fresh())->getJson('/api/v1/barbers')->assertForbidden();
});

it('sets the services a barber offers, with overrides', function () {
    $barber = Barber::factory()->for($this->shop)->create();
    [$cut, $beard] = Service::factory()->for($this->shop)->count(2)->create();

    $this->actingAs($this->owner)
        ->putJson("/api/v1/barbers/{$barber->id}/services", ['services' => [
            ['id' => $cut->id],
            ['id' => $beard->id, 'price_ore' => 25000, 'duration_minutes' => 20],
        ]])
        ->assertOk()
        ->assertJsonCount(2, 'data.services');

    expect($barber->services()->find($beard->id)->pivot->price_ore)->toBe(25000);
});

it('refuses services from another shop', function () {
    $barber = Barber::factory()->for($this->shop)->create();
    $foreign = Service::factory()->create();

    $this->actingAs($this->owner)
        ->putJson("/api/v1/barbers/{$barber->id}/services", ['services' => [['id' => $foreign->id]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('services.0.id');
});

it('crops the photo to a square', function () {
    Storage::fake('public');
    [$user, $barber] = $this->barberWithLogin($this->shop);

    $this->actingAs($user)
        ->postJson("/api/v1/barbers/{$barber->id}/avatar", ['image' => UploadedFile::fake()->image('me.jpg', 1000, 1500)])
        ->assertOk();

    $path = $barber->fresh()->avatar_path;
    expect(getimagesizefromstring(Storage::disk('public')->get($path))[0])->toBe(400)
        ->and(getimagesizefromstring(Storage::disk('public')->get($path))[1])->toBe(400);
});

it('reorders barbers', function () {
    [$a, $b] = Barber::factory()->for($this->shop)->count(2)->create();

    $this->actingAs($this->owner)
        ->putJson('/api/v1/barbers/order', ['ids' => [$b->id, $a->id]])
        ->assertOk()
        ->assertJsonPath('data.0.id', $b->id);
});
