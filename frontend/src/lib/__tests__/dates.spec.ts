import { describe, expect, it } from 'vitest'

import { addDays, clockIn, todayIn, weekdayOf } from '../dates'

describe('dates', () => {
  it('adds days across month and year ends', () => {
    expect(addDays('2027-01-31', 1)).toBe('2027-02-01')
    expect(addDays('2027-12-31', 1)).toBe('2028-01-01')
  })

  it("reads today and the time in the shop's timezone, not the browser's", () => {
    // 23:30 UTC on a Sunday is already Monday 01:30 in Stockholm (summer time).
    const now = new Date('2027-06-13T23:30:00Z')

    expect(todayIn('Europe/Stockholm', now)).toBe('2027-06-14')
    expect(clockIn('Europe/Stockholm', now)).toEqual({ weekday: 1, time: '01:30' })
  })

  it('gives ISO weekdays', () => {
    expect(weekdayOf('2027-06-14')).toBe(1)
    expect(weekdayOf('2027-06-20')).toBe(7)
  })
})
