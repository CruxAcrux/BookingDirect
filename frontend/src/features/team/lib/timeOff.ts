export interface TimeOffDraft {
  allDay: boolean
  startDate: string
  endDate: string
  startTime: string
  endTime: string
}

import { addDays } from '@/lib/dates'

/**
 * Turns the form into the API's local date-times. A full-day range runs from midnight
 * on the first day to midnight after the last day, so "12–18 July" is seven whole days.
 */
export function toTimeOffRange(draft: TimeOffDraft): { starts_at: string; ends_at: string } {
  if (draft.allDay) {
    return {
      starts_at: `${draft.startDate}T00:00`,
      ends_at: `${addDays(draft.endDate, 1)}T00:00`,
    }
  }

  return {
    starts_at: `${draft.startDate}T${draft.startTime}`,
    ends_at: `${draft.endDate}T${draft.endTime}`,
  }
}
