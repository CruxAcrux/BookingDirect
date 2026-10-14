<?php

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('emails a reset link that points to the SPA', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertAccepted();

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($user) {
        $url = $notification->toMail($user)->actionUrl;

        return str_starts_with($url, config('app.frontend_url').'/reset-password?token=');
    });
});

it('answers the same way for unknown emails', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])->assertAccepted();

    Notification::assertNothingSent();
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'a-new-long-password',
        'password_confirmation' => 'a-new-long-password',
    ])->assertOk();

    expect(Hash::check('a-new-long-password', $user->fresh()->password))->toBeTrue();
});

it('rejects an invalid token', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'invalid',
        'email' => $user->email,
        'password' => 'a-new-long-password',
        'password_confirmation' => 'a-new-long-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
