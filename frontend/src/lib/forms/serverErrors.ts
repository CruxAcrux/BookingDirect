import { validationErrors } from '@/lib/api'

/** First message per field from a Laravel 422 body, ready for VeeValidate's `setErrors`. */
export function firstFieldErrors(error: unknown): Record<string, string> {
  const errors = validationErrors(error) ?? {}
  return Object.fromEntries(
    Object.entries(errors)
      .filter(([, messages]) => messages.length > 0)
      .map(([field, messages]) => [field, messages[0] as string]),
  )
}
