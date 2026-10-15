<?php

namespace App\Http\Requests\Calendar;

use App\Http\Requests\Bookings\BookingIndexRequest;

/** Same date range rules as the booking list, so both can be loaded for the same view. */
class CalendarRequest extends BookingIndexRequest {}
