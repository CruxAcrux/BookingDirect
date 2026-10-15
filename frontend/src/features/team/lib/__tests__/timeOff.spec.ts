import { describe, expect, it } from 'vitest'

import { toTimeOffRange } from '../timeOff'

describe('toTimeOffRange', () => {
  it('turns whole days into midnight-to-midnight', () => {
    expect(
      toTimeOffRange({
        allDay: true,
        startDate: '2027-07-12',
        endDate: '2027-07-18',
        startTime: '',
        endTime: '',
      }),
    ).toEqual({ starts_at: '2027-07-12T00:00', ends_at: '2027-07-19T00:00' })
  })

  it('keeps times for part of a day', () => {
    expect(
      toTimeOffRange({
        allDay: false,
        startDate: '2027-11-03',
        endDate: '2027-11-03',
        startTime: '09:00',
        endTime: '12:30',
      }),
    ).toEqual({ starts_at: '2027-11-03T09:00', ends_at: '2027-11-03T12:30' })
  })
})
