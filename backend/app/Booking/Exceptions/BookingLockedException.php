<?php

namespace App\Booking\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/** The booking can no longer be changed: it is too close, already cancelled or finished. */
class BookingLockedException extends RuntimeException
{
    public function __construct(private readonly string $reasonKey)
    {
        parent::__construct($reasonKey);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => __($this->reasonKey)], JsonResponse::HTTP_CONFLICT);
    }
}
