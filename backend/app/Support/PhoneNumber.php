<?php

namespace App\Support;

/**
 * Normalizes phone numbers to E.164. Numbers without a country code are treated as Swedish.
 */
final class PhoneNumber
{
    private const E164 = '/^\+[1-9]\d{6,14}$/';

    public static function normalize(string $input): ?string
    {
        $digits = preg_replace('/[\s\-().]/', '', trim($input)) ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = '+'.substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '+46'.substr($digits, 1);
        }

        return preg_match(self::E164, $digits) === 1 ? $digits : null;
    }
}
