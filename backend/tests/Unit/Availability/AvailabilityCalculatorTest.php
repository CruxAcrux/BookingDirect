<?php

namespace Tests\Unit\Availability;

use App\Booking\Availability\AvailabilityCalculator;
use App\Booking\Availability\BarberInput;
use App\Booking\Availability\Period;
use App\Booking\Availability\ShopRules;
use App\Booking\Availability\Slot;
use App\Booking\Availability\WeeklyRange;
use Carbon\CarbonImmutable;

const TZ = 'Europe/Stockholm';

/** 2027-06-14 is a Monday. */
const MONDAY = '2027-06-14';

/**
 * @param  list<WeeklyRange>|null  $opening
 */
function rules(?array $opening = null, int $interval = 15, int $notice = 0, int $maxDays = 60): ShopRules
{
    return new ShopRules(TZ, $opening ?? everyDay('10:00', '12:00'), $interval, $notice, $maxDays);
}

/**
 * @return list<WeeklyRange>
 */
function everyDay(string $start, string $end): array
{
    return array_map(fn (int $day) => new WeeklyRange($day, $start, $end), range(1, 7));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function barber(array $overrides = []): BarberInput
{
    return new BarberInput(...[
        'barberId' => 1,
        'workingHours' => everyDay('00:00', '23:59'),
        'breaks' => [],
        'blocked' => [],
        'durationMinutes' => 30,
        'bufferMinutes' => 0,
        ...$overrides,
    ]);
}

function local(string $dateTime): CarbonImmutable
{
    return CarbonImmutable::parse($dateTime, TZ)->utc();
}

function period(string $start, string $end): Period
{
    return new Period(local($start), local($end));
}

/**
 * Local "HH:MM" start times, for readable assertions.
 *
 * @param  list<Slot>  $slots
 * @return list<string>
 */
function times(array $slots): array
{
    return array_map(fn (Slot $slot) => $slot->startsAt->setTimezone(TZ)->format('H:i'), $slots);
}

/**
 * @param  list<BarberInput>|null  $barbers
 * @return list<Slot>
 */
function slotsOn(string $date, ?ShopRules $rules = null, ?array $barbers = null, ?string $now = null): array
{
    return (new AvailabilityCalculator)->slots(
        $rules ?? rules(),
        $barbers ?? [barber()],
        $date,
        $date,
        local($now ?? '2027-06-01 08:00'),
    );
}

it('offers grid start times up to the last one that still fits before closing', function () {
    expect(times(slotsOn(MONDAY)))->toBe(['10:00', '10:15', '10:30', '10:45', '11:00', '11:15', '11:30']);
});

it('lets the cleanup buffer run past closing time', function () {
    $slots = slotsOn(MONDAY, rules(everyDay('10:00', '11:00')), [barber(['bufferMinutes' => 10])]);

    expect(times($slots))->toBe(['10:00', '10:15', '10:30']);
});

it('keeps the buffer clear of the next booking', function () {
    $booking = period(MONDAY.' 11:00', MONDAY.' 11:30');
    $slots = slotsOn(MONDAY, rules(), [barber(['bufferMinutes' => 10, 'blocked' => [$booking]])]);

    expect(times($slots))->toContain('10:15')
        ->not->toContain('10:30')
        ->not->toContain('11:00');
});

it('treats an existing booking\'s buffer as busy', function () {
    // A 10:00–10:30 booking with a 10-minute buffer keeps the barber busy until 10:40.
    $booking = period(MONDAY.' 10:00', MONDAY.' 10:40');
    $slots = slotsOn(MONDAY, rules(), [barber(['blocked' => [$booking]])]);

    expect(times($slots)[0])->toBe('10:45');
});

it('does not let a service run across a break', function () {
    $slots = slotsOn(MONDAY, rules(everyDay('11:00', '14:00')), [barber([
        'durationMinutes' => 45,
        'breaks' => [new WeeklyRange(1, '12:00', '12:30')],
    ])]);

    expect(times($slots))->toContain('11:15')
        ->not->toContain('11:30')
        ->not->toContain('12:00')
        ->toContain('12:30');
});

it('only offers time where opening hours and working hours overlap', function () {
    $slots = slotsOn(MONDAY, rules(everyDay('10:00', '19:00')), [barber([
        'workingHours' => [new WeeklyRange(1, '17:00', '21:00')],
    ])]);

    expect(times($slots))->toBe(['17:00', '17:15', '17:30', '17:45', '18:00', '18:15', '18:30']);
});

it('offers nothing on days the shop or the barber is closed', function () {
    expect(slotsOn(MONDAY, rules([new WeeklyRange(2, '10:00', '12:00')])))->toBe([])
        ->and(slotsOn(MONDAY, rules(), [barber(['workingHours' => [new WeeklyRange(2, '10:00', '12:00')]])]))->toBe([]);
});

it('skips time off', function () {
    $partial = slotsOn(MONDAY, rules(), [barber(['blocked' => [period(MONDAY.' 10:00', MONDAY.' 11:00')]])]);
    $wholeDay = slotsOn(MONDAY, rules(), [barber(['blocked' => [period(MONDAY.' 00:00', '2027-06-15 00:00')]])]);

    expect(times($partial))->toBe(['11:00', '11:15', '11:30'])
        ->and($wholeDay)->toBe([]);
});

it('respects the minimum notice', function () {
    $slots = slotsOn(MONDAY, rules(notice: 60), now: MONDAY.' 09:50');

    expect(times($slots)[0])->toBe('11:00');
});

it('offers nothing in the past or beyond the booking horizon', function () {
    $calculator = new AvailabilityCalculator;
    $now = local(MONDAY.' 08:00');

    $slots = $calculator->slots(rules(maxDays: 2), [barber()], '2027-06-10', '2027-06-20', $now);
    $dates = array_values(array_unique(array_map(fn (Slot $slot) => $slot->startsAt->setTimezone(TZ)->toDateString(), $slots)));

    expect($dates)->toBe(['2027-06-14', '2027-06-15', '2027-06-16']);
});

it('uses each barber\'s own service duration', function () {
    $slots = slotsOn(MONDAY, rules(everyDay('10:00', '11:00')), [barber(['durationMinutes' => 60])]);

    expect(times($slots))->toBe(['10:00']);
});

it('merges barbers by time, breaking ties in the given order', function () {
    $slots = slotsOn(MONDAY, rules(everyDay('10:00', '10:45')), [
        barber(['barberId' => 7]),
        barber(['barberId' => 3, 'blocked' => [period(MONDAY.' 10:00', MONDAY.' 10:15')]]),
    ]);

    expect(array_map(fn (Slot $slot) => [$slot->startsAt->setTimezone(TZ)->format('H:i'), $slot->barberId], $slots))->toBe([
        ['10:00', 7],
        ['10:15', 7],
        ['10:15', 3],
    ]);
});

describe('daylight saving time', function () {
    it('keeps local opening hours when clocks go forward in March', function () {
        // 2027-03-28 is the last Sunday of March: Stockholm moves from UTC+1 to UTC+2.
        $before = slotsOn('2027-03-27', now: '2027-03-01 08:00')[0];
        $after = slotsOn('2027-03-28', now: '2027-03-01 08:00')[0];

        expect($before->startsAt->format('H:i'))->toBe('09:00')
            ->and($after->startsAt->format('H:i'))->toBe('08:00')
            ->and($after->startsAt->setTimezone(TZ)->format('H:i'))->toBe('10:00');
    });

    it('keeps local opening hours when clocks go back in October', function () {
        // 2027-10-31 is the last Sunday of October: Stockholm moves from UTC+2 to UTC+1.
        $before = slotsOn('2027-10-30', now: '2027-10-01 08:00')[0];
        $after = slotsOn('2027-10-31', now: '2027-10-01 08:00')[0];

        expect($before->startsAt->format('H:i'))->toBe('08:00')
            ->and($after->startsAt->format('H:i'))->toBe('09:00');
    });

    it('never offers the hour that does not exist and measures services in real minutes', function () {
        $slots = slotsOn('2027-03-28', rules(everyDay('01:00', '04:00'), interval: 60), [barber(['durationMinutes' => 60])], '2027-03-01 08:00');

        // 02:00 does not exist that night, and 01:00–04:00 local is only two real hours.
        expect(times($slots))->toBe(['01:00', '03:00']);
    });
});
