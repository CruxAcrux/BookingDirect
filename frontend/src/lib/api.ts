import createClient, { type Middleware } from 'openapi-fetch'

import { i18n } from '@/i18n'

import type { components, paths } from './api-types'

export type Schemas = components['schemas']

export const API_BASE_URL = '/api/v1'

export const api = createClient<paths>({
  baseUrl: API_BASE_URL,
  credentials: 'same-origin',
  headers: { Accept: 'application/json' },
})

function readCookie(name: string): string | null {
  const match = document.cookie.split('; ').find((row) => row.startsWith(`${name}=`))
  return match ? decodeURIComponent(match.slice(name.length + 1)) : null
}

const requestMiddleware: Middleware = {
  onRequest({ request }) {
    // Laravel sets the XSRF-TOKEN cookie; echoing it back in a header proves the request
    // comes from our own pages.
    const token = readCookie('XSRF-TOKEN')
    if (token) request.headers.set('X-XSRF-TOKEN', token)
    request.headers.set('X-Requested-With', 'XMLHttpRequest')
    request.headers.set('Accept-Language', i18n.global.locale.value)
    return request
  },
}

let unauthorizedHandler: (() => void) | null = null

export function onUnauthorized(handler: () => void): void {
  unauthorizedHandler = handler
}

const sessionMiddleware: Middleware = {
  onResponse({ response, schemaPath }) {
    // Public pages have their own customer sign-in; their 401s say nothing about staff.
    if (response.status === 401 && schemaPath !== '/me' && !schemaPath.startsWith('/public/')) {
      unauthorizedHandler?.()
    }
    return response
  },
}

api.use(requestMiddleware, sessionMiddleware)

export async function ensureCsrfCookie(): Promise<void> {
  await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' })
}

export type ValidationErrors = Record<string, string[]>

/** Field errors from a Laravel 422 response, if the error has that shape. */
export function validationErrors(error: unknown): ValidationErrors | null {
  if (typeof error !== 'object' || error === null || !('errors' in error)) return null
  const { errors } = error
  return typeof errors === 'object' && errors !== null ? (errors as ValidationErrors) : null
}
