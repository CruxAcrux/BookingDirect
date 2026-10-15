import { describe, expect, it } from 'vitest'

import {
  clockOf,
  localToUtc,
  minutesAt,
  minutesOf,
  PX_PER_MINUTE,
  snap,
  topOf,
  visibleWindow,
} from '../lib/grid'

describe('calendar grid', () => {
  it('converts between clock times and minutes', () => {
    expect(minutesOf('09:45')).toBe(585)
    expect(minutesOf('2027-06-14T18:30')).toBe(1110)
    expect(clockOf(585)).toBe('09:45')
  })

  it('snaps to five minutes by default', () => {
    expect(snap(602)).toBe(600)
    expect(snap(603)).toBe(605)
    expect(snap(608, 15)).toBe(615)
  })

  it('shows whole hours around everything scheduled, or 08–20 when empty', () => {
    expect(
      visibleWindow([
        { start: 610, end: 1110 },
        { start: 540, end: 900 },
      ]),
    ).toEqual({ start: 540, end: 1140 })
    expect(visibleWindow([])).toEqual({ start: 480, end: 1200 })
  })

  it('maps pointer positions to snapped minutes inside the window', () => {
    const window = { start: 600, end: 720 }

    expect(topOf(630, window)).toBe(30 * PX_PER_MINUTE)
    expect(minutesAt(31 * PX_PER_MINUTE, window)).toBe(630)
    expect(minutesAt(-50, window)).toBe(600)
    expect(minutesAt(10_000, window)).toBe(720)
  })

  it('turns shop-local times into UTC, whatever timezone the browser is in', () => {
    expect(localToUtc('2027-06-14', 10 * 60, 'Europe/Stockholm')).toBe('2027-06-14T08:00:00Z')
    expect(localToUtc('2027-12-14', 10 * 60, 'Europe/Stockholm')).toBe('2027-12-14T09:00:00Z')
    // Both sides of the October change (last Sunday, 2027-10-31).
    expect(localToUtc('2027-10-30', 10 * 60, 'Europe/Stockholm')).toBe('2027-10-30T08:00:00Z')
    expect(localToUtc('2027-10-31', 10 * 60, 'Europe/Stockholm')).toBe('2027-10-31T09:00:00Z')
  })
})
