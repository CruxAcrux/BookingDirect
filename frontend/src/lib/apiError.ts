import { validationErrors, type ValidationErrors } from './api'

/** A failed API call, thrown so TanStack Query mutations and queries can see the status. */
export class ApiError extends Error {
  constructor(
    readonly status: number,
    readonly body: unknown,
  ) {
    const message =
      typeof body === 'object' &&
      body !== null &&
      'message' in body &&
      typeof body.message === 'string'
        ? body.message
        : `Request failed with status ${String(status)}`
    super(message)
    this.name = 'ApiError'
  }

  get validationErrors(): ValidationErrors | null {
    return this.status === 422 ? validationErrors(this.body) : null
  }
}

interface FetchResult<T> {
  data?: T
  error?: unknown
  response: Response
}

/** Returns the response body or throws an {@link ApiError}. */
export async function unwrap<T>(request: Promise<FetchResult<T>>): Promise<T> {
  const { data, error, response } = await request
  if (!response.ok) throw new ApiError(response.status, error)
  return data as T
}

/** For multipart uploads: send FormData as-is instead of JSON. */
export const asFormData = (body: unknown) => body as FormData
