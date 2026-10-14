import type { CurrentUser } from '@/stores/auth'

export function makeUser(overrides: Partial<CurrentUser> = {}): CurrentUser {
  return {
    id: 1,
    name: 'Sara Lind',
    email: 'sara@example.com',
    locale: 'sv',
    is_platform_admin: false,
    current_shop_id: 10,
    shops: [{ id: 10, name: 'Fade Studio', slug: 'fade-studio', role: 'owner', is_active: true }],
    ...overrides,
  }
}

/** The subset of openapi-fetch's result that our code reads. */
export function apiResult<T>(status: number, body?: T) {
  const ok = status >= 200 && status < 300
  return {
    data: ok ? body : undefined,
    error: ok ? undefined : body,
    response: { ok, status } as Response,
  }
}
