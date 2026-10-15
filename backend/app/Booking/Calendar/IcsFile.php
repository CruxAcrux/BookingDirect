<?php

namespace App\Booking\Calendar;

use App\Models\Booking;
use Carbon\CarbonInterface;

/**
 * An iCalendar (RFC 5545) event for a booking, attached to booking emails.
 *
 * The UID is stable per booking, so importing the file again after a move updates the
 * existing calendar event instead of adding a second one.
 */
final class IcsFile
{
    public static function forBooking(Booking $booking, string $summary, string $description, ?string $location): string
    {
        $host = parse_url(config()->string('app.url'), PHP_URL_HOST) ?: 'bookingdirect';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//BookingDirect//Bookings//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:booking-{$booking->id}@{$host}",
            'DTSTAMP:'.self::utc(now()),
            'DTSTART:'.self::utc($booking->starts_at),
            'DTEND:'.self::utc($booking->ends_at),
            'SUMMARY:'.self::text($summary),
            'DESCRIPTION:'.self::text($description),
            ...($location === null ? [] : ['LOCATION:'.self::text($location)]),
            ...($booking->manageUrl() === null ? [] : ['URL:'.$booking->manageUrl()]),
            'STATUS:CONFIRMED',
            'BEGIN:VALARM',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.self::text($summary),
            'TRIGGER:-PT1H',
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    private static function utc(CarbonInterface $time): string
    {
        return $time->copy()->utc()->format('Ymd\THis\Z');
    }

    private static function text(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $value);
    }

    /** Lines longer than 75 octets continue on the next line after a space. */
    private static function fold(string $line): string
    {
        $folded = '';
        $length = 0;

        foreach (mb_str_split($line) as $char) {
            $bytes = strlen($char);
            if ($length + $bytes > 75) {
                $folded .= "\r\n ";
                $length = 1;
            }
            $folded .= $char;
            $length += $bytes;
        }

        return $folded;
    }
}
