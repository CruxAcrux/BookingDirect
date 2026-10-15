import { validationErrors } from '@/lib/api'
import { ApiError } from '@/lib/apiError'

/** First message per field from a Laravel 422 body, ready for VeeValidate's `setErrors`. */
export function firstFieldErrors(error: unknown): Record<string, string> {
  const errors = validationErrors(error) ?? {}
  return Object.fromEntries(
    Object.entries(errors)
      .filter(([, messages]) => messages.length > 0)
      .map(([field, messages]) => [field, messages[0] as string]),
  )
}

/**
 * Puts a failed request's field errors on the form. Returns a message for errors that
 * belong to no field (or null when every error was placed on a field).
 */
export function applyApiError(
  error: unknown,
  setErrors: (errors: Record<string, string>) => void,
  fields: readonly string[],
  fallback: string,
): string | null {
  if (!(error instanceof ApiError)) return fallback

  const fieldErrors = firstFieldErrors(error.body)
  setErrors(fieldErrors)

  const unplaced = Object.entries(fieldErrors).find(([field]) => !fields.includes(field))
  if (unplaced) return unplaced[1]
  if (error.status === 422) return null
  return error.status === 403 ? error.message : fallback
}
