import { describe, expect, it } from 'vitest'

import type { CalendarBarber, StaffBooking } from '../api'
import { buildColumn, clipToDate, needsOutcome } from '../lib/columns'

const barber: CalendarBarber = {
  id: 7,
  display_name: 'Samir',
  avatar_url: null,
  is_active: true,
  working_hours: [{ weekday: 1, starts_at: '09:00', ends_at: '17:00' }],
  breaks: [{ weekday: 1, starts_at: '12:00', ends_at: '12:30' }],
  time_off: [
    {
      id: 1,
      barber_id: 7,
      starts_at: '2027-06-14T13:00:00Z',
      ends_at: '2027-06-16T22:00:00Z',
      starts_at_local: '2027-06-14T15:00',
      ends_at_local: '2027-06-17T00:00',
      all_day: false,
      reason: 'other',
      note: null,
    },
  ],
}

function booking(overrides: Partial<StaffBooking>): StaffBooking {
  return {
    id: 1,
    status: 'confirmed',
    source: 'online',
    barber_id: 7,
    starts_at: '2027-06-14T08:00:00Z',
    ends_at: '2027-06-14T08:30:00Z',
    blocked_until: '2027-06-14T08:30:00Z',
    starts_at_local: '2027-06-14T10:00',
    ends_at_local: '2027-06-14T10:30',
    duration_minutes: 30,
    buffer_minutes: 0,
    price_ore: 30000,
    customer_note: null,
    staff_note: null,
    service: { id: 1, name: 'Klippning' },
    customer: { id: 1, name: 'Kim', phone: '+46701234567', email: null },
    cancelled_at: null,
    cancelled_by: null,
    cancellation_message: null,
    ...overrides,
  }
}

describe('calendar columns', () => {
  it('opens only where opening hours and working hours overlap, and blocks breaks and time off', () => {
    const column = buildColumn(
      '2027-06-14',
      barber,
      [{ weekday: 1, starts_at: '10:00', ends_at: '19:00' }],
      [],
    )

    expect(column.open).toEqual([{ start: 600, end: 1020 }])
    expect(column.blocked).toEqual([
      { start: 720, end: 750 },
      { start: 900, end: 1440 },
    ])
  })

  it("takes only the barber's bookings for that date", () => {
    const column = buildColumn(
      '2027-06-14',
      barber,
      [],
      [
        booking({ id: 1 }),
        booking({ id: 2, barber_id: 8 }),
        booking({ id: 3, starts_at_local: '2027-06-15T10:00' }),
      ],
    )

    expect(column.bookings.map((item) => item.id)).toEqual([1])
  })

  it('clips multi-day spans to one date', () => {
    expect(clipToDate('2027-06-14T15:00', '2027-06-17T00:00', '2027-06-15')).toEqual({
      start: 0,
      end: 1440,
    })
    expect(clipToDate('2027-06-14T15:00', '2027-06-17T00:00', '2027-06-17')).toBeNull()
    expect(clipToDate('2027-06-14T15:00', '2027-06-14T16:30', '2027-06-14')).toEqual({
      start: 900,
      end: 990,
    })
  })

  it('flags past confirmed visits that were never marked', () => {
    const now = new Date('2027-06-14T09:00:00Z')

    expect(needsOutcome(booking({}), now)).toBe(true)
    expect(needsOutcome(booking({ status: 'completed' }), now)).toBe(false)
    expect(needsOutcome(booking({ ends_at: '2027-06-14T10:00:00Z' }), now)).toBe(false)
  })
})
