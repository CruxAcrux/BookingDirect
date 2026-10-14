export interface WeeklyRange {
  weekday: number
  starts_at: string
  ends_at: string
}

export interface DayIssues {
  invalid: boolean
  overlaps: boolean
  outsideReference: boolean
}

export const WEEKDAYS = [1, 2, 3, 4, 5, 6, 7] as const

/** Times are "HH:MM", so plain string comparison orders them correctly. */
function covers(reference: WeeklyRange[], range: WeeklyRange): boolean {
  return reference.some(
    (outer) =>
      outer.weekday === range.weekday &&
      outer.starts_at <= range.starts_at &&
      outer.ends_at >= range.ends_at,
  )
}

/**
 * Problems per weekday. `invalid` and `overlaps` block saving; `outsideReference`
 * (e.g. working hours outside opening hours) is only a warning.
 */
export function weeklyIssues(
  ranges: WeeklyRange[],
  reference?: WeeklyRange[],
): Map<number, DayIssues> {
  const issues = new Map<number, DayIssues>()

  for (const weekday of WEEKDAYS) {
    const day = ranges
      .filter((range) => range.weekday === weekday)
      .sort((a, b) => a.starts_at.localeCompare(b.starts_at))

    const invalid = day.some(
      (range) => !range.starts_at || !range.ends_at || range.ends_at <= range.starts_at,
    )
    const overlaps = day.some((range, index) => {
      const previous = day[index - 1]
      return previous !== undefined && range.starts_at < previous.ends_at
    })
    const outsideReference =
      reference !== undefined && day.some((range) => !invalid && !covers(reference, range))

    issues.set(weekday, { invalid, overlaps, outsideReference })
  }

  return issues
}

export function hasBlockingIssues(issues: Map<number, DayIssues>): boolean {
  return [...issues.values()].some((day) => day.invalid || day.overlaps)
}

/** Localized weekday name for an ISO weekday (1 = Monday). */
export function weekdayName(
  weekday: number,
  locale: string,
  style: 'long' | 'short' = 'long',
): string {
  // 2024-01-01 was a Monday.
  const date = new Date(Date.UTC(2024, 0, weekday))
  return new Intl.DateTimeFormat(locale, { weekday: style, timeZone: 'UTC' }).format(date)
}
