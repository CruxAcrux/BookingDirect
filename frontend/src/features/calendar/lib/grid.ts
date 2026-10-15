/** Vertical scale of the calendar grid. */
export const PX_PER_MINUTE = 1.6

/** Drag and click positions snap to this many minutes. */
export const SNAP_MINUTES = 5

/** "HH:MM" (or the time part of "YYYY-MM-DDTHH:MM") as minutes after midnight. */
export function minutesOf(time: string): number {
  const clock = time.length > 5 ? time.slice(11, 16) : time
  const [hours = 0, minutes = 0] = clock.split(':').map(Number)
  return hours * 60 + minutes
}

export function clockOf(minutes: number): string {
  const hours = Math.floor(minutes / 60)
  return `${String(hours).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`
}

export function snap(minutes: number, step = SNAP_MINUTES): number {
  return Math.round(minutes / step) * step
}

export interface Window {
  /** First visible minute of the day. */
  start: number
  /** Last visible minute of the day. */
  end: number
}

/**
 * The hours to show: everything anyone is scheduled or booked, widened to whole hours.
 * Falls back to 08–20 when there is nothing to show.
 */
export function visibleWindow(ranges: { start: number; end: number }[]): Window {
  if (ranges.length === 0) return { start: 8 * 60, end: 20 * 60 }

  const start = Math.min(...ranges.map((range) => range.start))
  const end = Math.max(...ranges.map((range) => range.end))

  return {
    start: Math.max(0, Math.floor(start / 60) * 60),
    end: Math.min(24 * 60, Math.ceil(end / 60) * 60),
  }
}

export function topOf(minutes: number, window: Window): number {
  return (minutes - window.start) * PX_PER_MINUTE
}

/** A pointer's offset inside a column, as snapped minutes within the visible window. */
export function minutesAt(offsetY: number, window: Window, step = SNAP_MINUTES): number {
  const minutes = snap(window.start + offsetY / PX_PER_MINUTE, step)
  return Math.min(Math.max(minutes, window.start), window.end)
}

/**
 * The UTC instant of a local wall-clock time in a timezone, as ISO 8601.
 * The browser may be in another timezone than the shop, so this never uses local Date APIs.
 */
export function localToUtc(date: string, minutes: number, timezone: string): string {
  const [year = 0, month = 1, day = 1] = date.split('-').map(Number)
  const wanted = Date.UTC(year, month - 1, day, Math.floor(minutes / 60), minutes % 60)

  // Guess, measure how far the guess is off in the target timezone, correct; twice for DST edges.
  let instant = wanted
  for (let i = 0; i < 2; i++) {
    instant += wanted - wallClockAsUtc(instant, timezone)
  }

  return new Date(instant).toISOString().replace('.000Z', 'Z')
}

function wallClockAsUtc(instant: number, timezone: string): number {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone: timezone,
    hourCycle: 'h23',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).formatToParts(new Date(instant))
  const part = (type: string) => Number(parts.find((p) => p.type === type)?.value)

  return Date.UTC(part('year'), part('month') - 1, part('day'), part('hour'), part('minute'))
}
