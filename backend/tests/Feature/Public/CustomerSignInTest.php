<?php

namespace Tests\Feature\Public;

use App\Models\Customer;
use App\Models\CustomerOtp;
use App\Models\Shop;
use App\Models\SmsMessage;

beforeEach(function () {
    $this->shop = Shop::factory()->create(['name' => 'Fade Studio']);
});

function sentCode(): string
{
    preg_match('/\d{6}/', SmsMessage::withoutGlobalScopes()->latest('id')->value('body'), $match);

    return $match[0];
}

it('texts a code and stores only its hash', function () {
    $this->withHeader('Accept-Language', 'en')
        ->postJson("/api/v1/public/shops/{$this->shop->slug}/otp", ['phone' => '070-123 45 67'])
        ->assertAccepted();

    $sms = SmsMessage::withoutGlobalScopes()->sole();
    expect($sms->to)->toBe('+46701234567')
        ->and($sms->body)->toContain('Fade Studio')
        ->and(CustomerOtp::withoutGlobalScopes()->sole()->code_hash)->not->toContain(sentCode());
});

it('signs the customer in with the right code and creates them once', function () {
    $this->fromSpa()->postJson("/api/v1/public/shops/{$this->shop->slug}/otp", ['phone' => '0701234567']);

    $this->fromSpa()
        ->postJson("/api/v1/public/shops/{$this->shop->slug}/otp/verify", ['phone' => '0701234567', 'code' => sentCode()])
        ->assertOk()
        ->assertJsonPath('data.phone', '+46701234567')
        ->assertJsonPath('data.name', null);

    $this->fromSpa()->getJson("/api/v1/public/shops/{$this->shop->slug}/me")->assertOk();
    expect(Customer::withoutGlobalScopes()->where('shop_id', $this->shop->id)->count())->toBe(1);
});

it('rejects a wrong code and locks after five attempts', function () {
    $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp", ['phone' => '0701234567']);
    $right = sentCode();
    $wrong = $right === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $attempt) {
        $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp/verify", ['phone' => '0701234567', 'code' => $wrong])
            ->assertJsonValidationErrors('code');
    }

    $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp/verify", ['phone' => '0701234567', 'code' => $right])
        ->assertUnprocessable();
    $this->assertGuest('customer');
});

it('rejects an expired code', function () {
    $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp", ['phone' => '0701234567']);
    $code = sentCode();

    $this->travel(11)->minutes();

    $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp/verify", ['phone' => '0701234567', 'code' => $code])
        ->assertJsonValidationErrors('code');
});

it('invalidates older codes when a new one is requested', function () {
    $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp", ['phone' => '0701234567']);
    $old = sentCode();
    $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp", ['phone' => '0701234567']);

    if ($old !== sentCode()) {
        $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp/verify", ['phone' => '0701234567', 'code' => $old])
            ->assertJsonValidationErrors('code');
    }
    expect(CustomerOtp::withoutGlobalScopes()->whereNull('consumed_at')->count())->toBe(1);
});

it('limits how many codes a phone number can request', function () {
    foreach (range(1, 3) as $attempt) {
        $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp", ['phone' => '0701234567'])->assertAccepted();
    }

    $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp", ['phone' => '070-123 45 67'])->assertTooManyRequests();
});

it('keeps customers of one shop signed out at another', function () {
    $customer = Customer::factory()->for($this->shop)->create();
    $other = Shop::factory()->create();

    $this->actingAsCustomer($customer)->getJson("/api/v1/public/shops/{$this->shop->slug}/me")->assertOk();
    $this->actingAsCustomer($customer)->getJson("/api/v1/public/shops/{$other->slug}/me")->assertUnauthorized();
});

it('validates the phone number', function () {
    $this->postJson("/api/v1/public/shops/{$this->shop->slug}/otp", ['phone' => '12'])
        ->assertJsonValidationErrors('phone');
});
