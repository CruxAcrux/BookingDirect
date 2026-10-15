import { toValue, type MaybeRefOrGetter } from 'vue'
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'

import { api, type Schemas } from '@/lib/api'
import { unwrap } from '@/lib/apiError'

export type AdminShop = Schemas['AdminShopResource']

export const adminKeys = {
  shops: (search: string, page: number) => ['admin', 'shops', search, page] as const,
  all: ['admin', 'shops'] as const,
}

export function useAdminShops(search: MaybeRefOrGetter<string>, page: MaybeRefOrGetter<number>) {
  return useQuery({
    queryKey: () => adminKeys.shops(toValue(search), toValue(page)),
    queryFn: async () =>
      unwrap(
        api.GET('/admin/shops', {
          params: { query: { search: toValue(search) || undefined, page: toValue(page) } },
        }),
      ),
    placeholderData: keepPreviousData,
  })
}

export function useCreateShop() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (body: Schemas['StoreShopRequest']) =>
      (await unwrap(api.POST('/admin/shops', { body }))).data,
    onSuccess: () => queryClient.invalidateQueries({ queryKey: adminKeys.all }),
  })
}

export function useSetShopActive() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, active }: { id: number; active: boolean }) => {
      const options = { params: { path: { shop: id } } }
      return active
        ? (await unwrap(api.POST('/admin/shops/{shop}/activate', options))).data
        : (await unwrap(api.POST('/admin/shops/{shop}/deactivate', options))).data
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: adminKeys.all }),
  })
}
