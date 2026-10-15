<?php

namespace App\Booking\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** The requested time is no longer free, usually because someone else just booked it. */
class SlotTakenException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => __('booking.slot_taken')], JsonResponse::HTTP_CONFLICT);
    }
}
