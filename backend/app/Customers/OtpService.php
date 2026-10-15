<?php

namespace App\Customers;

use App\Models\CustomerOtp;
use App\Models\Shop;
use App\Sms\SmsSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One-time codes for customers to prove they own a phone number.
 *
 * Codes are six digits, valid for ten minutes, stored as a keyed hash, and locked after
 * five wrong guesses. Requesting a new code invalidates older ones.
 */
class OtpService
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly SmsSender $sms) {}

    /**
     * @return string the code, so demo mode can show it; never expose it otherwise
     */
    public function send(Shop $shop, string $phone): string
    {
        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($phone, $code): void {
            CustomerOtp::where('phone', $phone)->whereNull('consumed_at')->update(['consumed_at' => now()]);

            CustomerOtp::create([
                'phone' => $phone,
                'code_hash' => CustomerOtp::hashCode($phone, $code),
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            ]);
        });

        $this->sms->send($phone, __('booking.sms.otp', ['code' => $code, 'shop' => $shop->name, 'minutes' => self::TTL_MINUTES]), 'otp');

        return $code;
    }

    /**
     * @throws ValidationException when the code is wrong, expired or used up
     */
    public function verify(string $phone, string $code): void
    {
        $otp = CustomerOtp::where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($otp === null || $otp->attempts >= self::MAX_ATTEMPTS) {
            throw ValidationException::withMessages(['code' => __('booking.otp.expired')]);
        }

        if (! hash_equals($otp->code_hash, CustomerOtp::hashCode($phone, $code))) {
            $otp->increment('attempts');

            throw ValidationException::withMessages([
                'code' => $otp->attempts >= self::MAX_ATTEMPTS ? __('booking.otp.expired') : __('booking.otp.wrong'),
            ]);
        }

        $otp->forceFill(['consumed_at' => now()])->save();
    }
}
