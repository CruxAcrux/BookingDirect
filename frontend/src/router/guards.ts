import type { RouteLocationNormalized, RouteLocationRaw } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    requiresAuth?: boolean
    guestOnly?: boolean
    requiresPlatformAdmin?: boolean
  }
}

/** Only same-site paths, so `?redirect=` can't send people to another site. */
export function safeRedirect(value: unknown): string | null {
  return typeof value === 'string' && value.startsWith('/') && !value.startsWith('//')
    ? value
    : null
}

export async function authGuard(to: RouteLocationNormalized): Promise<RouteLocationRaw | true> {
  const { requiresAuth, guestOnly, requiresPlatformAdmin } = to.meta
  if (!requiresAuth && !guestOnly && !requiresPlatformAdmin) return true

  const auth = useAuthStore()
  await auth.ensureLoaded()

  if ((requiresAuth || requiresPlatformAdmin) && !auth.user) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (requiresPlatformAdmin && !auth.user?.is_platform_admin) {
    return { name: 'dashboard' }
  }

  if (guestOnly && auth.user) {
    return safeRedirect(to.query.redirect) ?? { name: 'dashboard' }
  }

  return true
}
