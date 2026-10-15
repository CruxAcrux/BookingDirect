<?php

namespace App\Enums;

enum BookingStatus: string
{
    /** Holds a slot without a confirmed customer yet (e.g. a waitlist claim). */
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    /** Statuses that occupy the barber's time. Mirrors the exclusion constraint. */
    public function blocksTime(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed], true);
    }

    /**
     * @return array<int, string>
     */
    public static function blockingValues(): array
    {
        return [self::Pending->value, self::Confirmed->value];
    }
}
