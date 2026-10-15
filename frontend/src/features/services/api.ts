import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'

import { api, type Schemas } from '@/lib/api'
import { unwrap } from '@/lib/apiError'

export type Service = Schemas['ServiceResource']
export type ServiceInput = Schemas['ServiceRequest']

export const serviceKeys = { all: ['services'] as const }

export function useServices() {
  return useQuery({
    queryKey: serviceKeys.all,
    queryFn: async () => (await unwrap(api.GET('/services'))).data,
  })
}

function useInvalidatingMutation<TInput, TResult>(mutationFn: (input: TInput) => Promise<TResult>) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: serviceKeys.all }),
  })
}

export function useCreateService() {
  return useInvalidatingMutation(async (body: ServiceInput) =>
    unwrap(api.POST('/services', { body })),
  )
}

export function useUpdateService() {
  return useInvalidatingMutation(async ({ id, body }: { id: number; body: ServiceInput }) =>
    unwrap(api.PUT('/services/{service}', { params: { path: { service: id } }, body })),
  )
}

export function useDeleteService() {
  return useInvalidatingMutation(async (id: number) =>
    unwrap(api.DELETE('/services/{service}', { params: { path: { service: id } } })),
  )
}

export function useReorderServices() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (ids: number[]) =>
      (await unwrap(api.PUT('/services/order', { body: { ids } }))).data,
    onSuccess: (services) => {
      queryClient.setQueryData(serviceKeys.all, services)
    },
  })
}
