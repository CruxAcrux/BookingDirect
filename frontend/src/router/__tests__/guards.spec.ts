import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import type { RouteLocationNormalized } from 'vue-router'

import { api } from '@/lib/api'
import { apiResult, makeUser } from '@/test/fixtures'

import { authGuard, safeRedirect } from '../guards'

vi.mock('@/lib/api', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/lib/api')>()),
  api: { GET: vi.fn(), POST: vi.fn() },
}))

const getMock = vi.mocked(api.GET)

function route(meta: RouteLocationNormalized['meta'], query = {}): RouteLocationNormalized {
  return { fullPath: '/dashboard/x', meta, query } as unknown as RouteLocationNormalized
}

function signedInAs(user: ReturnType<typeof makeUser> | null) {
  getMock.mockResolvedValue(apiResult(user ? 200 : 401, user ? { data: user } : {}))
}

beforeEach(() => {
  setActivePinia(createPinia())
  getMock.mockReset()
})

describe('authGuard', () => {
  it('lets public routes through without asking the API', async () => {
    expect(await authGuard(route({}))).toBe(true)
    expect(getMock).not.toHaveBeenCalled()
  })

  it('sends guests to the login page and remembers where they were going', async () => {
    signedInAs(null)

    expect(await authGuard(route({ requiresAuth: true }))).toEqual({
      name: 'login',
      query: { redirect: '/dashboard/x' },
    })
  })

  it('lets signed-in staff into protected routes', async () => {
    signedInAs(makeUser())

    expect(await authGuard(route({ requiresAuth: true }))).toBe(true)
  })

  it('keeps signed-in staff away from the login page', async () => {
    signedInAs(makeUser())

    expect(await authGuard(route({ guestOnly: true }))).toEqual({ name: 'dashboard' })
    expect(await authGuard(route({ guestOnly: true }, { redirect: '/dashboard/calendar' }))).toBe(
      '/dashboard/calendar',
    )
  })

  it('only lets platform admins into admin routes', async () => {
    signedInAs(makeUser({ is_platform_admin: false }))
    expect(await authGuard(route({ requiresPlatformAdmin: true }))).toEqual({ name: 'dashboard' })

    setActivePinia(createPinia())
    signedInAs(makeUser({ is_platform_admin: true }))
    expect(await authGuard(route({ requiresPlatformAdmin: true }))).toBe(true)
  })
})

describe('safeRedirect', () => {
  it.each([
    ['/dashboard', '/dashboard'],
    ['//evil.example.com', null],
    ['https://evil.example.com', null],
    [undefined, null],
    [['/a', '/b'], null],
  ])('maps %j to %j', (input, expected) => {
    expect(safeRedirect(input)).toBe(expected)
  })
})
