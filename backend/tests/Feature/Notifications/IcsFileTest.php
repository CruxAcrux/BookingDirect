<?php

namespace Tests\Feature\Notifications;

use App\Booking\Calendar\IcsFile;
use App\Models\Booking;
use Illuminate\Support\Carbon;

it('writes an RFC 5545 event with escaped text and folded long lines', function () {
    $booking = Booking::factory()->at(Carbon::parse('2027-10-31 09:00', 'UTC'), minutes: 45)->create();

    $ics = IcsFile::forBooking($booking, 'Skin fade; beard', str_repeat('Lång text, ', 12), 'Linnégatan 12, Göteborg');
    $lines = explode("\r\n", rtrim($ics, "\r\n"));

    expect($lines[0])->toBe('BEGIN:VCALENDAR')
        ->and(end($lines))->toBe('END:VCALENDAR')
        ->and($ics)->toContain("DTSTART:20271031T090000Z\r\nDTEND:20271031T094500Z")
        ->and($ics)->toContain('SUMMARY:Skin fade\; beard')
        ->and($ics)->toContain('URL:'.$booking->manageUrl())
        ->and(collect($lines)->every(fn (string $line) => strlen($line) <= 75))->toBeTrue()
        ->and(str_replace("\r\n ", '', $ics))->toContain('DESCRIPTION:'.str_repeat('Lång text\, ', 12));
});
