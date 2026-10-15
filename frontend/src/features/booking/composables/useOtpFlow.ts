import { ref, toValue, type MaybeRefOrGetter } from 'vue'
import { useI18n } from 'vue-i18n'

import { ApiError } from '@/lib/apiError'
import { firstFieldErrors } from '@/lib/forms/serverErrors'

import { useCustomerSignIn, type PublicCustomer } from '../api'

/**
 * Phone number → SMS code → signed in. Shared by the booking flow and "my bookings".
 */
export function useOtpFlow(slug: MaybeRefOrGetter<string>) {
  const { t } = useI18n()
  const { requestCode, verify } = useCustomerSignIn(slug)

  const phone = ref('')
  const code = ref('')
  const sentTo = ref<string | null>(null)
  const demoCode = ref<string | null>(null)
  const errors = ref<{ phone?: string; code?: string; general?: string }>({})

  function messageFor(error: unknown, field: 'phone' | 'code') {
    if (error instanceof ApiError && error.status === 422) {
      errors.value = { [field]: firstFieldErrors(error.body)[field] ?? error.message }
    } else if (error instanceof ApiError && error.status === 429) {
      errors.value = { general: t('errors.throttled') }
    } else {
      errors.value = { general: t('errors.generic') }
    }
  }

  async function send(): Promise<boolean> {
    errors.value = {}
    if (phone.value.trim() === '') {
      errors.value = { phone: t('validation.required') }
      return false
    }

    try {
      const result = await requestCode.mutateAsync(phone.value)
      sentTo.value = phone.value
      demoCode.value = result.demo_code
      code.value = ''
      return true
    } catch (error) {
      messageFor(error, 'phone')
      return false
    }
  }

  async function confirm(): Promise<PublicCustomer | null> {
    errors.value = {}
    if (!/^\d{6}$/.test(code.value)) {
      errors.value = { code: t('booking.otp.sixDigits') }
      return null
    }

    try {
      return await verify.mutateAsync({ phone: sentTo.value ?? phone.value, code: code.value })
    } catch (error) {
      messageFor(error, 'code')
      return null
    }
  }

  function changeNumber() {
    sentTo.value = null
    demoCode.value = null
    code.value = ''
    errors.value = {}
  }

  return {
    phone,
    code,
    sentTo,
    demoCode,
    errors,
    sending: () => toValue(requestCode.isPending),
    verifying: () => toValue(verify.isPending),
    send,
    confirm,
    changeNumber,
  }
}
