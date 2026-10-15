import { toValue, type MaybeRefOrGetter } from 'vue'
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'

import { api, type Schemas } from '@/lib/api'
import { unwrap } from '@/lib/apiError'

export type StaffBooking = Schemas['BookingResource']
export type CalendarBarber = Schemas['CalendarBarberResource']
export type CustomerMatch = Schemas['PublicCustomerResource']

interface Range {
  from: string
  to: string
}

export const calendarKeys = {
  schedule: (range: Range) => ['calendar', 'schedule', range.from, range.to] as const,
  bookings: (range: Range, withCancelled: boolean) =>
    ['calendar', 'bookings', range.from, range.to, withCancelled] as const,
  allBookings: ['calendar', 'bookings'] as const,
  customers: (search: string) => ['customers', 'search', search] as const,
}

export function useCalendarSchedule(range: MaybeRefOrGetter<Range>) {
  return useQuery({
    queryKey: () => calendarKeys.schedule(toValue(range)),
    queryFn: async () =>
      (await unwrap(api.GET('/calendar', { params: { query: toValue(range) } }))).data,
    placeholderData: keepPreviousData,
  })
}

export function useStaffBookings(
  range: MaybeRefOrGetter<Range>,
  withCancelled: MaybeRefOrGetter<boolean> = false,
) {
  return useQuery({
    queryKey: () => calendarKeys.bookings(toValue(range), toValue(withCancelled)),
    queryFn: async () =>
      (
        await unwrap(
          api.GET('/bookings', {
            params: {
              query: {
                ...toValue(range),
                with_cancelled: toValue(withCancelled) ? 'true' : 'false',
              },
            },
          }),
        )
      ).data,
    placeholderData: keepPreviousData,
    refetchInterval: 60_000,
  })
}

export function useCustomerSearch(search: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: () => calendarKeys.customers(toValue(search)),
    queryFn: async () =>
      (await unwrap(api.GET('/customers', { params: { query: { search: toValue(search) } } })))
        .data,
    enabled: () => toValue(search).trim().length >= 2,
    staleTime: 30_000,
  })
}

/** Replaces one booking in every cached list it appears in. */
function useReplaceBooking() {
  const queryClient = useQueryClient()
  return (booking: StaffBooking) => {
    queryClient.setQueriesData<StaffBooking[]>({ queryKey: calendarKeys.allBookings }, (list) =>
      list?.map((item) => (item.id === booking.id ? booking : item)),
    )
  }
}

export function useCreateStaffBooking() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (body: Schemas['StoreBookingRequest']) =>
      (await unwrap(api.POST('/bookings', { body }))).data,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: calendarKeys.allBookings }),
  })
}

export interface MoveInput {
  booking: StaffBooking
  startsAt: string
  barberId: number
  /** Where the booking will appear, so it can be drawn there before the server answers. */
  preview: { startsAtLocal: string; endsAtLocal: string }
}

/**
 * Moves a booking and shows it at the new place straight away. If the server refuses
 * (e.g. the time is taken), the booking jumps back.
 */
export function useMoveBooking() {
  const queryClient = useQueryClient()
  const replace = useReplaceBooking()

  return useMutation({
    mutationFn: async ({ booking, startsAt, barberId }: MoveInput) =>
      (
        await unwrap(
          api.PUT('/bookings/{booking}', {
            params: { path: { booking: booking.id } },
            body: { starts_at: startsAt, barber_id: barberId },
          }),
        )
      ).data,
    onMutate: async ({ booking, barberId, preview }) => {
      await queryClient.cancelQueries({ queryKey: calendarKeys.allBookings })
      const snapshot = queryClient.getQueriesData<StaffBooking[]>({
        queryKey: calendarKeys.allBookings,
      })
      replace({
        ...booking,
        barber_id: barberId,
        starts_at_local: preview.startsAtLocal,
        ends_at_local: preview.endsAtLocal,
      })
      return { snapshot }
    },
    onError: (_error, _input, context) => {
      context?.snapshot.forEach(([key, data]) => queryClient.setQueryData(key, data))
    },
    onSuccess: replace,
    onSettled: () => queryClient.invalidateQueries({ queryKey: calendarKeys.allBookings }),
  })
}

export function useBookingActions() {
  const replace = useReplaceBooking()
  const path = (id: number) => ({ params: { path: { booking: id } } })

  const saveNote = useMutation({
    mutationFn: async ({ id, note }: { id: number; note: string | null }) =>
      (await unwrap(api.PUT('/bookings/{booking}', { ...path(id), body: { staff_note: note } })))
        .data,
    onSuccess: replace,
  })

  const cancel = useMutation({
    mutationFn: async ({ id, message }: { id: number; message: string | null }) =>
      (await unwrap(api.POST('/bookings/{booking}/cancel', { ...path(id), body: { message } })))
        .data,
    onSuccess: replace,
  })

  const outcome = useMutation({
    mutationFn: async ({ id, status }: { id: number; status: 'completed' | 'no_show' }) =>
      (await unwrap(api.POST('/bookings/{booking}/outcome', { ...path(id), body: { status } })))
        .data,
    onSuccess: replace,
  })

  return { saveNote, cancel, outcome }
}
