/** Adds days to a "YYYY-MM-DD" date without involving the browser's timezone. */
export function addDays(date: string, days: number): string {
  const value = new Date(`${date}T00:00:00Z`)
  value.setUTCDate(value.getUTCDate() + days)
  return value.toISOString().slice(0, 10)
}

/** Today's date ("YYYY-MM-DD") in a timezone, e.g. the shop's. */
export function todayIn(timezone: string, now: Date = new Date()): string {
  return new Intl.DateTimeFormat('en-CA', { timeZone: timezone }).format(now)
}

/** ISO weekday (1 = Monday) and "HH:MM" right now in a timezone. */
export function clockIn(
  timezone: string,
  now: Date = new Date(),
): { weekday: number; time: string } {
  const parts = new Intl.DateTimeFormat('en-GB', {
    timeZone: timezone,
    weekday: 'short',
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
  }).formatToParts(now)
  const part = (type: string) => parts.find((p) => p.type === type)?.value ?? ''
  const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

  return {
    weekday: weekdays.indexOf(part('weekday')) + 1,
    time: `${part('hour')}:${part('minute')}`,
  }
}

/** ISO weekday (1 = Monday) of a "YYYY-MM-DD" date. */
export function weekdayOf(date: string): number {
  const day = new Date(`${date}T00:00:00Z`).getUTCDay()
  return day === 0 ? 7 : day
}
