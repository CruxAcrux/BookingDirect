<?php

namespace App\Rules;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An ISO 8601 date-time with an explicit offset, e.g. 2026-11-03T08:15:00Z or
 * 2026-11-03T09:15:00+01:00, so it is never ambiguous which instant is meant.
 */
class IsoDateTimeRule implements ValidationRule
{
    private const PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:\d{2})$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::PATTERN, $value) !== 1 || ! $this->parses($value)) {
            $fail('rules.iso_datetime')->translate();
        }
    }

    private function parses(string $value): bool
    {
        try {
            new DateTimeImmutable($value);

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
