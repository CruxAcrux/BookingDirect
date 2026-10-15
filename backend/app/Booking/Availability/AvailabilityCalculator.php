<?php

namespace App\Booking\Availability;

use Carbon\CarbonImmutable;

/**
 * Works out bookable start times. It does no I/O: everything it needs is passed in,
 * which keeps the rules easy to test.
 *
 * Rules:
 * - Start times sit on a clock grid in the shop's timezone (10:00, 10:15, … for a 15-minute interval).
 * - The service itself must fit inside the shop's opening hours and the barber's working hours,
 *   outside breaks, time off and other bookings.
 * - The cleanup buffer after the service may run past closing time, but not into a break,
 *   time off or another booking.
 * - Nothing earlier than "now + minimum notice", nothing after "today + max days ahead".
 * - Weekly hours are wall-clock times, converted per date, so daylight saving changes are
 *   handled. Local times that do not exist (the skipped spring hour) are never offered.
 */
final class AvailabilityCalculator
{
    /**
     * @param  list<BarberInput>  $barbers  in the order ties should be broken
     * @param  string  $fromDate  first local date, "Y-m-d"
     * @param  string  $toDate  last local date, "Y-m-d"
     * @return list<Slot> sorted by start time, then by the order of `$barbers`
     */
    public function slots(ShopRules $rules, array $barbers, string $fromDate, string $toDate, CarbonImmutable $now): array
    {
        $timezone = $rules->timezone;
        $today = $now->setTimezone($timezone)->startOfDay();
        $first = max(CarbonImmutable::parse($fromDate, $timezone)->startOfDay(), $today);
        $last = min(CarbonImmutable::parse($toDate, $timezone)->startOfDay(), $today->addDays($rules->maxDaysAhead));
        $earliestStart = $now->addMinutes($rules->minNoticeMinutes);

        $slots = [];

        for ($date = $first; $date <= $last; $date = $date->addDay()) {
            $opening = $this->periodsOn($rules->openingHours, $date);
            if ($opening === []) {
                continue;
            }

            $candidates = $this->startTimes($date, $rules->slotIntervalMinutes);

            foreach ($barbers as $order => $barber) {
                $breaks = $this->periodsOn($barber->breaks, $date);
                $free = $this->intersect($opening, $this->periodsOn($barber->workingHours, $date));
                $free = $this->subtract($free, [...$breaks, ...$barber->blocked]);
                $obstacles = [...$breaks, ...$barber->blocked];

                foreach ($candidates as $start) {
                    if ($start < $earliestStart) {
                        continue;
                    }

                    $end = $start->addMinutes($barber->durationMinutes);

                    if (! $this->fitsIn($free, $start, $end)) {
                        continue;
                    }

                    if ($barber->bufferMinutes > 0 && $this->hitsAny($obstacles, $end, $end->addMinutes($barber->bufferMinutes))) {
                        continue;
                    }

                    $slots[] = [$order, new Slot($barber->barberId, $start, $end)];
                }
            }
        }

        usort($slots, fn (array $a, array $b): int => [$a[1]->startsAt->getTimestamp(), $a[0]] <=> [$b[1]->startsAt->getTimestamp(), $b[0]]);

        return array_map(fn (array $entry): Slot => $entry[1], $slots);
    }

    /**
     * The weekly ranges that apply on a local date, as absolute periods.
     *
     * @param  list<WeeklyRange>  $ranges
     * @return list<Period>
     */
    private function periodsOn(array $ranges, CarbonImmutable $date): array
    {
        $periods = [];

        foreach ($ranges as $range) {
            if ($range->weekday !== $date->dayOfWeekIso) {
                continue;
            }

            $start = $this->atLocalTime($date, $range->start);
            $end = $this->atLocalTime($date, $range->end);

            if ($end > $start) {
                $periods[] = new Period($start, $end);
            }
        }

        return $periods;
    }

    private function atLocalTime(CarbonImmutable $date, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map(intval(...), explode(':', $time));

        return $date->setTime($hour, $minute)->utc();
    }

    /**
     * Grid start times for a local date. A wall-clock time inside the skipped spring hour
     * resolves to a later real time; such duplicates are dropped.
     *
     * @return list<CarbonImmutable>
     */
    private function startTimes(CarbonImmutable $date, int $interval): array
    {
        $times = [];

        for ($minutes = 0; $minutes < 24 * 60; $minutes += $interval) {
            $hour = intdiv($minutes, 60);
            $minute = $minutes % 60;
            $local = $date->setTime($hour, $minute);

            if ($local->hour !== $hour || $local->minute !== $minute) {
                continue;
            }

            $times[$local->getTimestamp()] = $local->utc();
        }

        return array_values($times);
    }

    /**
     * @param  list<Period>  $a
     * @param  list<Period>  $b
     * @return list<Period>
     */
    private function intersect(array $a, array $b): array
    {
        $result = [];

        foreach ($a as $first) {
            foreach ($b as $second) {
                $start = max($first->start, $second->start);
                $end = min($first->end, $second->end);

                if ($end > $start) {
                    $result[] = new Period($start, $end);
                }
            }
        }

        return $result;
    }

    /**
     * @param  list<Period>  $periods
     * @param  list<Period>  $holes
     * @return list<Period>
     */
    private function subtract(array $periods, array $holes): array
    {
        foreach ($holes as $hole) {
            $next = [];

            foreach ($periods as $period) {
                if (! $period->overlaps($hole->start, $hole->end)) {
                    $next[] = $period;

                    continue;
                }

                if ($hole->start > $period->start) {
                    $next[] = new Period($period->start, $hole->start);
                }

                if ($hole->end < $period->end) {
                    $next[] = new Period($hole->end, $period->end);
                }
            }

            $periods = $next;
        }

        return $periods;
    }

    /**
     * @param  list<Period>  $periods
     */
    private function fitsIn(array $periods, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        foreach ($periods as $period) {
            if ($period->contains($start, $end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<Period>  $periods
     */
    private function hitsAny(array $periods, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        foreach ($periods as $period) {
            if ($period->overlaps($start, $end)) {
                return true;
            }
        }

        return false;
    }
}
