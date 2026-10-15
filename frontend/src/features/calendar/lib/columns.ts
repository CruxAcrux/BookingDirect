import { weekdayOf } from '@/lib/dates'
import type { WeeklyRange } from '@/lib/schedule/weekly'

import type { CalendarBarber, StaffBooking } from '../api'
import { minutesOf } from './grid'

export interface MinuteRange {
  start: number
  end: number
}

export interface Column {
  key: string
  date: string
  barber: CalendarBarber
  /** Bookable time: the shop is open and the barber works. */
  open: MinuteRange[]
  /** Breaks and time off inside the day. */
  blocked: MinuteRange[]
  bookings: StaffBooking[]
}

function rangesOn(ranges: WeeklyRange[], weekday: number): MinuteRange[] {
  return ranges
    .filter((range) => range.weekday === weekday)
    .map((range) => ({ start: minutesOf(range.starts_at), end: minutesOf(range.ends_at) }))
}

function intersect(a: MinuteRange[], b: MinuteRange[]): MinuteRange[] {
  return a.flatMap((first) =>
    b
      .map((second) => ({
        start: Math.max(first.start, second.start),
        end: Math.min(first.end, second.end),
      }))
      .filter((range) => range.end > range.start),
  )
}

/** The part of a local "YYYY-MM-DDTHH:MM" span that falls on `date`, in minutes. */
export function clipToDate(startLocal: string, endLocal: string, date: string): MinuteRange | null {
  const dayStart = `${date}T00:00`
  const dayEnd = `${date}T24:00`
  if (endLocal <= dayStart || startLocal >= dayEnd) return null

  return {
    start: startLocal <= dayStart ? 0 : minutesOf(startLocal),
    end: endLocal >= dayEnd || endLocal.slice(0, 10) > date ? 24 * 60 : minutesOf(endLocal),
  }
}

export function buildColumn(
  date: string,
  barber: CalendarBarber,
  openingHours: WeeklyRange[],
  bookings: StaffBooking[],
): Column {
  const weekday = weekdayOf(date)
  const timeOff = barber.time_off
    .map((entry) => clipToDate(entry.starts_at_local, entry.ends_at_local, date))
    .filter((range): range is MinuteRange => range !== null)

  return {
    key: `${String(barber.id)}:${date}`,
    date,
    barber,
    open: intersect(rangesOn(openingHours, weekday), rangesOn(barber.working_hours, weekday)),
    blocked: [...rangesOn(barber.breaks, weekday), ...timeOff],
    bookings: bookings.filter(
      (booking) => booking.barber_id === barber.id && booking.starts_at_local.startsWith(date),
    ),
  }
}

/** A confirmed visit that has ended but was never marked completed or no-show. */
export function needsOutcome(booking: StaffBooking, now: Date = new Date()): boolean {
  return booking.status === 'confirmed' && Date.parse(booking.ends_at) < now.getTime()
}
