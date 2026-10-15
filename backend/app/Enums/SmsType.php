<?php

namespace App\Enums;

enum SmsType: string
{
    case Otp = 'otp';
    case BookingConfirmed = 'booking_confirmed';
    case BookingRescheduled = 'booking_rescheduled';
    case BookingCancelled = 'booking_cancelled';
    case Reminder24h = 'reminder_24h';
    case Reminder2h = 'reminder_2h';
}
