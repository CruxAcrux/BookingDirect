<?php

namespace App\Booking;

use App\Booking\Exceptions\SlotTakenException;
use App\Models\Booking;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Saves bookings and turns the database's double-booking protection into a clean error.
 */
class BookingWriter
{
    /** PostgreSQL's SQLSTATE for an exclusion constraint violation. */
    private const EXCLUSION_VIOLATION = '23P01';

    /**
     * @param  Closure(): Booking  $write
     *
     * @throws SlotTakenException when the barber already has a booking at that time
     */
    public function write(Closure $write): Booking
    {
        try {
            return DB::transaction($write);
        } catch (QueryException $exception) {
            if ($exception->getCode() === self::EXCLUSION_VIOLATION) {
                throw new SlotTakenException(previous: $exception);
            }

            throw $exception;
        }
    }
}
