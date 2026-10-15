import { computed } from 'vue'
import { useRoute, useRouter, type LocationQueryRaw } from 'vue-router'

export type BookingStep = 'service' | 'barber' | 'time' | 'details'

export const BOOKING_STEPS: readonly BookingStep[] = ['service', 'barber', 'time', 'details']

/** "any" means the first available barber. */
export type BarberChoice = number | 'any'

export interface BookingDraft {
  serviceId: number | null
  barber: BarberChoice | null
  date: string | null
  startsAt: string | null
}

function positiveInt(value: unknown): number | null {
  const number = typeof value === 'string' ? Number(value) : NaN
  return Number.isInteger(number) && number > 0 ? number : null
}

export function parseDraft(query: Record<string, unknown>): BookingDraft {
  const barber = query.barber === 'any' ? 'any' : positiveInt(query.barber)
  const date =
    typeof query.date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(query.date) ? query.date : null
  const startsAt = typeof query.at === 'string' && query.at !== '' ? query.at : null

  return { serviceId: positiveInt(query.service), barber, date, startsAt }
}

/** The first step that still needs an answer. */
export function currentStep(draft: BookingDraft): BookingStep {
  if (draft.serviceId === null) return 'service'
  if (draft.barber === null) return 'barber'
  if (draft.startsAt === null) return 'time'
  return 'details'
}

/**
 * The booking in progress lives in the URL query, so back/forward, reloads and
 * "book again" links (`?service=2&barber=5`) all work.
 */
export function useBookingDraft() {
  const route = useRoute()
  const router = useRouter()

  const draft = computed(() => parseDraft(route.query))
  const step = computed(() => currentStep(draft.value))

  function update(changes: Partial<BookingDraft>, options: { replace?: boolean } = {}) {
    const next = { ...draft.value, ...changes }
    const query: LocationQueryRaw = {}
    if (next.serviceId !== null) query.service = String(next.serviceId)
    if (next.barber !== null) query.barber = String(next.barber)
    if (next.date !== null) query.date = next.date
    if (next.startsAt !== null) query.at = next.startsAt

    const location = { query }
    return options.replace ? router.replace(location) : router.push(location)
  }

  /** Goes back to a step, clearing it and every later answer. */
  function goTo(step: BookingStep) {
    const index = BOOKING_STEPS.indexOf(step)
    return update({
      ...(index <= 0 ? { serviceId: null } : {}),
      ...(index <= 1 ? { barber: null } : {}),
      ...(index <= 2 ? { startsAt: null } : {}),
    })
  }

  return { draft, step, update, goTo }
}
