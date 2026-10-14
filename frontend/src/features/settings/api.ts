import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'

import { api, type Schemas } from '@/lib/api'
import { asFormData, unwrap } from '@/lib/apiError'
import type { WeeklyRange } from '@/lib/schedule/weekly'

export type Shop = Schemas['ShopResource']
export type ShopUpdate = Schemas['UpdateShopRequest']

export const shopKeys = {
  shop: ['shop'] as const,
  openingHours: ['shop', 'opening-hours'] as const,
}

export function useShop() {
  return useQuery({
    queryKey: shopKeys.shop,
    queryFn: async () => (await unwrap(api.GET('/shop'))).data,
  })
}

export function useUpdateShop() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (body: ShopUpdate) => (await unwrap(api.PATCH('/shop', { body }))).data,
    onSuccess: (shop) => {
      queryClient.setQueryData(shopKeys.shop, shop)
    },
  })
}

export function useShopImage(type: 'logo' | 'cover') {
  const queryClient = useQueryClient()
  const onSuccess = (shop: Shop) => {
    queryClient.setQueryData(shopKeys.shop, shop)
  }

  const upload = useMutation({
    mutationFn: async (file: File) => {
      const body = new FormData()
      body.append('image', file)
      return (
        await unwrap(
          api.POST('/shop/{type}', {
            params: { path: { type } },
            body: body as never,
            bodySerializer: asFormData,
          }),
        )
      ).data
    },
    onSuccess,
  })

  const remove = useMutation({
    mutationFn: async () =>
      (await unwrap(api.DELETE('/shop/{type}', { params: { path: { type } } }))).data,
    onSuccess,
  })

  return { upload, remove }
}

export function useOpeningHours() {
  return useQuery({
    queryKey: shopKeys.openingHours,
    queryFn: async (): Promise<WeeklyRange[]> =>
      (await unwrap(api.GET('/shop/opening-hours'))).data,
  })
}

export function useUpdateOpeningHours() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (hours: WeeklyRange[]) =>
      (await unwrap(api.PUT('/shop/opening-hours', { body: { hours } }))).data,
    onSuccess: (hours) => {
      queryClient.setQueryData(shopKeys.openingHours, hours)
    },
  })
}
