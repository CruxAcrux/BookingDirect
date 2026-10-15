import { toValue, type MaybeRefOrGetter } from 'vue'
import { keepPreviousData, useQuery } from '@tanstack/vue-query'

import { api, type Schemas } from '@/lib/api'
import { unwrap } from '@/lib/apiError'

export type SentSms = Schemas['SmsMessageResource']
export type SmsType = Schemas['SmsType']

export const SMS_TYPES: readonly SmsType[] = [
  'booking_confirmed',
  'booking_rescheduled',
  'booking_cancelled',
  'reminder_24h',
  'reminder_2h',
  'otp',
]

export interface SmsFilters {
  type: SmsType | ''
  phone: string
  page: number
}

export function useSentSms(filters: MaybeRefOrGetter<SmsFilters>) {
  return useQuery({
    queryKey: () => ['sms-messages', toValue(filters)] as const,
    queryFn: async () => {
      const { type, phone, page } = toValue(filters)
      return unwrap(
        api.GET('/sms-messages', {
          params: { query: { type: type || undefined, phone: phone || undefined, page } },
        }),
      )
    },
    placeholderData: keepPreviousData,
    // New messages are written by the queue worker, so keep the log fresh for demos.
    refetchInterval: 15_000,
  })
}
