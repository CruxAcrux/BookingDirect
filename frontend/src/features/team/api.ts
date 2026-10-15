import { toValue, type MaybeRefOrGetter } from 'vue'
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'

import { api, type Schemas } from '@/lib/api'
import { asFormData, unwrap } from '@/lib/apiError'
import type { WeeklyRange } from '@/lib/schedule/weekly'

export type Barber = Schemas['BarberResource']
export type BarberInput = Schemas['BarberRequest']
export type TimeOff = Schemas['TimeOffResource']
export type TimeOffInput = Schemas['TimeOffRequest']
export type PortfolioImage = Schemas['PortfolioImageResource']
export type ScheduleKind = 'working-hours' | 'breaks'

export const teamKeys = {
  all: ['barbers'] as const,
  barber: (id: number) => ['barbers', id] as const,
  schedule: (id: number, kind: ScheduleKind) => ['barbers', id, kind] as const,
  timeOff: (id: number) => ['barbers', id, 'time-off'] as const,
  portfolio: (id: number) => ['barbers', id, 'portfolio'] as const,
}

const path = (barber: number) => ({ params: { path: { barber } } })

export function useBarbers() {
  return useQuery({
    queryKey: teamKeys.all,
    queryFn: async () => (await unwrap(api.GET('/barbers'))).data,
  })
}

export function useBarber(id: MaybeRefOrGetter<number>) {
  return useQuery({
    queryKey: () => teamKeys.barber(toValue(id)),
    queryFn: async () => (await unwrap(api.GET('/barbers/{barber}', path(toValue(id))))).data,
  })
}

/** Keeps the list and the single-barber cache in step after a change. */
function useStoreBarber() {
  const queryClient = useQueryClient()
  return (barber: Barber) => {
    queryClient.setQueryData(teamKeys.barber(barber.id), barber)
    void queryClient.invalidateQueries({ queryKey: teamKeys.all, exact: true })
  }
}

export function useCreateBarber() {
  const store = useStoreBarber()
  return useMutation({
    mutationFn: async (body: BarberInput) => (await unwrap(api.POST('/barbers', { body }))).data,
    onSuccess: store,
  })
}

export function useUpdateBarber(id: MaybeRefOrGetter<number>) {
  const store = useStoreBarber()
  return useMutation({
    mutationFn: async (body: BarberInput) =>
      (await unwrap(api.PUT('/barbers/{barber}', { ...path(toValue(id)), body }))).data,
    onSuccess: store,
  })
}

export function useDeleteBarber() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => unwrap(api.DELETE('/barbers/{barber}', path(id))),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: teamKeys.all }),
  })
}

export function useReorderBarbers() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (ids: number[]) =>
      (await unwrap(api.PUT('/barbers/order', { body: { ids } }))).data,
    onSuccess: (barbers) => {
      queryClient.setQueryData(teamKeys.all, barbers)
    },
  })
}

export function useInviteBarber(id: MaybeRefOrGetter<number>) {
  const store = useStoreBarber()
  return useMutation({
    mutationFn: async (body: Schemas['InviteBarberRequest']) =>
      (await unwrap(api.POST('/barbers/{barber}/invite', { ...path(toValue(id)), body }))).data,
    onSuccess: store,
  })
}

export function useSyncBarberServices(id: MaybeRefOrGetter<number>) {
  const store = useStoreBarber()
  return useMutation({
    mutationFn: async (services: Schemas['SyncBarberServicesRequest']['services']) =>
      (
        await unwrap(
          api.PUT('/barbers/{barber}/services', { ...path(toValue(id)), body: { services } }),
        )
      ).data,
    onSuccess: store,
  })
}

export function useBarberAvatar(id: MaybeRefOrGetter<number>) {
  const store = useStoreBarber()

  const upload = useMutation({
    mutationFn: async (file: File) => {
      const body = new FormData()
      body.append('image', file)
      return (
        await unwrap(
          api.POST('/barbers/{barber}/avatar', {
            ...path(toValue(id)),
            body: body as never,
            bodySerializer: asFormData,
          }),
        )
      ).data
    },
    onSuccess: store,
  })

  const remove = useMutation({
    mutationFn: async () =>
      (await unwrap(api.DELETE('/barbers/{barber}/avatar', path(toValue(id))))).data,
    onSuccess: store,
  })

  return { upload, remove }
}

export function useBarberSchedule(id: MaybeRefOrGetter<number>, kind: ScheduleKind) {
  return useQuery({
    queryKey: () => teamKeys.schedule(toValue(id), kind),
    queryFn: async (): Promise<WeeklyRange[]> =>
      kind === 'working-hours'
        ? (await unwrap(api.GET('/barbers/{barber}/working-hours', path(toValue(id))))).data
        : (await unwrap(api.GET('/barbers/{barber}/breaks', path(toValue(id))))).data,
  })
}

export function useUpdateBarberSchedule(id: MaybeRefOrGetter<number>, kind: ScheduleKind) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (hours: WeeklyRange[]): Promise<WeeklyRange[]> => {
      const options = { ...path(toValue(id)), body: { hours } }
      return kind === 'working-hours'
        ? (await unwrap(api.PUT('/barbers/{barber}/working-hours', options))).data
        : (await unwrap(api.PUT('/barbers/{barber}/breaks', options))).data
    },
    onSuccess: (hours) => {
      queryClient.setQueryData(teamKeys.schedule(toValue(id), kind), hours)
    },
  })
}

export function useTimeOff(id: MaybeRefOrGetter<number>) {
  return useQuery({
    queryKey: () => teamKeys.timeOff(toValue(id)),
    queryFn: async () =>
      (await unwrap(api.GET('/barbers/{barber}/time-off', path(toValue(id))))).data,
  })
}

export function useTimeOffMutations(id: MaybeRefOrGetter<number>) {
  const queryClient = useQueryClient()
  const onSuccess = () => queryClient.invalidateQueries({ queryKey: teamKeys.timeOff(toValue(id)) })

  const create = useMutation({
    mutationFn: async (body: TimeOffInput) =>
      unwrap(api.POST('/barbers/{barber}/time-off', { ...path(toValue(id)), body })),
    onSuccess,
  })

  const remove = useMutation({
    mutationFn: async (timeOff: number) =>
      unwrap(
        api.DELETE('/barbers/{barber}/time-off/{timeOff}', {
          params: { path: { barber: toValue(id), timeOff } },
        }),
      ),
    onSuccess,
  })

  return { create, remove }
}

export function usePortfolio(id: MaybeRefOrGetter<number>) {
  return useQuery({
    queryKey: () => teamKeys.portfolio(toValue(id)),
    queryFn: async () =>
      (await unwrap(api.GET('/barbers/{barber}/portfolio', path(toValue(id))))).data,
  })
}

export function usePortfolioMutations(id: MaybeRefOrGetter<number>) {
  const queryClient = useQueryClient()
  const key = () => teamKeys.portfolio(toValue(id))
  const onSuccess = () => queryClient.invalidateQueries({ queryKey: key() })
  const image = (portfolioImage: number) => ({
    params: { path: { barber: toValue(id), portfolioImage } },
  })

  const upload = useMutation({
    mutationFn: async (file: File) => {
      const body = new FormData()
      body.append('image', file)
      return unwrap(
        api.POST('/barbers/{barber}/portfolio', {
          ...path(toValue(id)),
          body: body as never,
          bodySerializer: asFormData,
        }),
      )
    },
    onSuccess,
  })

  const updateCaption = useMutation({
    mutationFn: async ({ imageId, caption }: { imageId: number; caption: string | null }) =>
      unwrap(
        api.PUT('/barbers/{barber}/portfolio/{portfolioImage}', {
          ...image(imageId),
          body: { caption },
        }),
      ),
    onSuccess,
  })

  const remove = useMutation({
    mutationFn: async (imageId: number) =>
      unwrap(api.DELETE('/barbers/{barber}/portfolio/{portfolioImage}', image(imageId))),
    onSuccess,
  })

  const reorder = useMutation({
    mutationFn: async (ids: number[]) =>
      (
        await unwrap(
          api.PUT('/barbers/{barber}/portfolio/order', { ...path(toValue(id)), body: { ids } }),
        )
      ).data,
    onSuccess: (images) => {
      queryClient.setQueryData(key(), images)
    },
  })

  return { upload, updateCaption, remove, reorder }
}
