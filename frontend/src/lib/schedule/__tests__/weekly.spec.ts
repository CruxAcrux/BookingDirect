import { describe, expect, it } from 'vitest'

import { hasBlockingIssues, weekdayName, weeklyIssues } from '../weekly'

describe('weeklyIssues', () => {
  it('accepts separate ranges on the same day', () => {
    const issues = weeklyIssues([
      { weekday: 1, starts_at: '13:00', ends_at: '18:00' },
      { weekday: 1, starts_at: '09:00', ends_at: '12:00' },
    ])

    expect(issues.get(1)).toEqual({ invalid: false, overlaps: false, outsideReference: false })
    expect(hasBlockingIssues(issues)).toBe(false)
  })

  it('flags overlaps and ranges that end before they start', () => {
    const issues = weeklyIssues([
      { weekday: 2, starts_at: '09:00', ends_at: '13:00' },
      { weekday: 2, starts_at: '12:00', ends_at: '18:00' },
      { weekday: 3, starts_at: '18:00', ends_at: '09:00' },
    ])

    expect(issues.get(2)?.overlaps).toBe(true)
    expect(issues.get(3)?.invalid).toBe(true)
    expect(hasBlockingIssues(issues)).toBe(true)
  })

  it('warns about ranges outside the reference without blocking', () => {
    const opening = [{ weekday: 5, starts_at: '10:00', ends_at: '18:00' }]
    const issues = weeklyIssues(
      [
        { weekday: 5, starts_at: '09:00', ends_at: '17:00' },
        { weekday: 6, starts_at: '10:00', ends_at: '14:00' },
      ],
      opening,
    )

    expect(issues.get(5)?.outsideReference).toBe(true)
    expect(issues.get(6)?.outsideReference).toBe(true)
    expect(hasBlockingIssues(issues)).toBe(false)
  })
})

describe('weekdayName', () => {
  it('names ISO weekdays in the given language', () => {
    expect(weekdayName(1, 'sv')).toBe('måndag')
    expect(weekdayName(7, 'en')).toBe('Sunday')
  })
})
