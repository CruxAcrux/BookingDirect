import { toValue, type MaybeRefOrGetter } from 'vue'
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'

import { api, type Schemas } from '@/lib/api'
import { ApiError, unwrap } from '@/lib/apiError'

export type PublicShop = Schemas['PublicShopResource']
export type PublicBooking = Schemas['PublicBookingResource']
export type AvailabilityDay = Schemas['AvailabilityDayResource']
export type Slot = Schemas['SlotResource']
export type PublicCustomer = Schemas['PublicCustomerResource']

export const publicKeys = {
  shop: (slug: string) => ['public', slug] as const,
  availability: (slug: string, service: number, barber: number | null, from: string, to: string) =>
    ['public', slug, 'availability', service, barber, from, to] as const,
  next: (slug: string, service: number, barber: number | null) =>
    ['public', slug, 'next', service, barber] as const,
  customer: (slug: string) => ['public', slug, 'me'] as const,
  myBookings: (slug: string) => ['public', slug, 'me', 'bookings'] as const,
  managed: (token: string) => ['public', 'booking', token] as const,
}

const shopPath = (slug: string) => ({ params: { path: { shop: slug } } })

export function usePublicShop(slug: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: () => publicKeys.shop(toValue(slug)),
    queryFn: async () =>
      (await unwrap(api.GET('/public/shops/{shop}', shopPath(toValue(slug))))).data,
    retry: (count, error) => !(error instanceof ApiError && error.status === 404) && count < 2,
  })
}

interface AvailabilityQuery {
  slug: string
  serviceId: number | null
  barberId: number | null
  from: string
  to: string
}

export function useAvailability(query: MaybeRefOrGetter<AvailabilityQuery>) {
  return useQuery({
    queryKey: () => {
      const { slug, serviceId, barberId, from, to } = toValue(query)
      return publicKeys.availability(slug, serviceId ?? 0, barberId, from, to)
    },
    queryFn: async () => {
      const { slug, serviceId, barberId, from, to } = toValue(query)
      return (
        await unwrap(
          api.GET('/public/shops/{shop}/availability', {
            params: {
              path: { shop: slug },
              query: { service_id: serviceId ?? 0, barber_id: barberId, from, to },
            },
          }),
        )
      ).data
    },
    enabled: () => toValue(query).serviceId !== null,
    placeholderData: keepPreviousData,
    staleTime: 20_000,
  })
}

export function useNextAvailable(
  slug: MaybeRefOrGetter<string>,
  serviceId: MaybeRefOrGetter<number | null>,
  barberId: MaybeRefOrGetter<number | null>,
) {
  return useQuery({
    queryKey: () => publicKeys.next(toValue(slug), toValue(serviceId) ?? 0, toValue(barberId)),
    queryFn: async (): Promise<Slot | null> => {
      const body = await unwrap(
        api.GET('/public/shops/{shop}/availability/next', {
          params: {
            path: { shop: toValue(slug) },
            query: { service_id: toValue(serviceId) ?? 0, barber_id: toValue(barberId) },
          },
        }),
      )
      return 'data' in body ? body.data : null
    },
    enabled: () => toValue(serviceId) !== null,
    staleTime: 20_000,
  })
}

export function useCustomer(slug: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: () => publicKeys.customer(toValue(slug)),
    queryFn: async (): Promise<PublicCustomer | null> => {
      const { data, response } = await api.GET('/public/shops/{shop}/me', shopPath(toValue(slug)))
      return response.ok && data ? data.data : null
    },
    staleTime: 60_000,
  })
}

export function useCustomerSignIn(slug: MaybeRefOrGetter<string>) {
  const queryClient = useQueryClient()

  const requestCode = useMutation({
    mutationFn: async (phone: string) =>
      unwrap(api.POST('/public/shops/{shop}/otp', { ...shopPath(toValue(slug)), body: { phone } })),
  })

  const verify = useMutation({
    mutationFn: async (body: { phone: string; code: string }) =>
      (
        await unwrap(
          api.POST('/public/shops/{shop}/otp/verify', { ...shopPath(toValue(slug)), body }),
        )
      ).data,
    onSuccess: (customer) => {
      queryClient.setQueryData(publicKeys.customer(toValue(slug)), customer)
      void queryClient.invalidateQueries({ queryKey: publicKeys.myBookings(toValue(slug)) })
    },
  })

  const signOut = useMutation({
    mutationFn: async () =>
      unwrap(api.POST('/public/shops/{shop}/logout', shopPath(toValue(slug)))),
    onSuccess: () => {
      queryClient.setQueryData(publicKeys.customer(toValue(slug)), null)
      queryClient.removeQueries({ queryKey: publicKeys.myBookings(toValue(slug)) })
    },
  })

  return { requestCode, verify, signOut }
}

export function useCreateBooking(slug: MaybeRefOrGetter<string>) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (body: Schemas['CreateBookingRequest']) =>
      unwrap(api.POST('/public/shops/{shop}/bookings', { ...shopPath(toValue(slug)), body })),
    onSettled: () => queryClient.invalidateQueries({ queryKey: ['public', toValue(slug)] }),
  })
}

export function useMyBookings(slug: MaybeRefOrGetter<string>, enabled: MaybeRefOrGetter<boolean>) {
  return useQuery({
    queryKey: () => publicKeys.myBookings(toValue(slug)),
    queryFn: async () =>
      (await unwrap(api.GET('/public/shops/{shop}/me/bookings', shopPath(toValue(slug))))).data,
    enabled,
  })
}

export function useManagedBooking(token: MaybeRefOrGetter<string>) {
  return useQuery({
    queryKey: () => publicKeys.managed(toValue(token)),
    queryFn: async () =>
      (
        await unwrap(
          api.GET('/public/bookings/{token}', { params: { path: { token: toValue(token) } } }),
        )
      ).data,
    retry: (count, error) => !(error instanceof ApiError && error.status === 404) && count < 2,
  })
}

/**
 * Cancel and move, either through the manage link (token) or the signed-in session.
 */
export function useBookingChanges(
  target: MaybeRefOrGetter<{ token: string } | { slug: string; id: number }>,
) {
  const queryClient = useQueryClient()

  const onSuccess = (booking: PublicBooking) => {
    const value = toValue(target)
    if ('token' in value) queryClient.setQueryData(publicKeys.managed(value.token), booking)
    void queryClient.invalidateQueries({ queryKey: ['public', booking.shop.slug] })
  }

  const cancel = useMutation({
    mutationFn: async () => {
      const value = toValue(target)
      const request =
        'token' in value
          ? api.POST('/public/bookings/{token}/cancel', {
              params: { path: { token: value.token } },
            })
          : api.POST('/public/shops/{shop}/me/bookings/{booking}/cancel', {
              params: { path: { shop: value.slug, booking: value.id } },
            })
      return (await unwrap(request)).data
    },
    onSuccess,
  })

  const reschedule = useMutation({
    mutationFn: async (body: Schemas['RescheduleBookingRequest']) => {
      const value = toValue(target)
      const request =
        'token' in value
          ? api.POST('/public/bookings/{token}/reschedule', {
              params: { path: { token: value.token } },
              body,
            })
          : api.POST('/public/shops/{shop}/me/bookings/{booking}/reschedule', {
              params: { path: { shop: value.slug, booking: value.id } },
              body,
            })
      return (await unwrap(request)).data
    },
    onSuccess,
  })

  return { cancel, reschedule }
}
