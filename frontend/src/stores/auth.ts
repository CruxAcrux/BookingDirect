import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

import { isAppLocale, setLocale } from '@/i18n'
import { api, ensureCsrfCookie, type Schemas } from '@/lib/api'

export type CurrentUser = Schemas['CurrentUserResource']

export interface Credentials {
  email: string
  password: string
  remember: boolean
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<CurrentUser | null>(null)
  let loading: Promise<void> | null = null

  const isAuthenticated = computed(() => user.value !== null)
  const currentShop = computed(
    () => user.value?.shops.find((shop) => shop.id === user.value?.current_shop_id) ?? null,
  )

  async function fetchUser(): Promise<void> {
    const { data } = await api.GET('/me')
    user.value = data?.data ?? null
  }

  /** Loads the session user once; later calls reuse the first request. */
  function ensureLoaded(): Promise<void> {
    loading ??= fetchUser().catch(() => {
      user.value = null
    })
    return loading
  }

  async function login(credentials: Credentials) {
    await ensureCsrfCookie()
    const result = await api.POST('/auth/login', { body: credentials })

    if (result.data) {
      user.value = result.data.data
      loading = Promise.resolve()
      if (isAppLocale(user.value.locale)) setLocale(user.value.locale)
    }

    return result
  }

  async function logout(): Promise<void> {
    await api.POST('/auth/logout')
    clear()
  }

  function clear(): void {
    user.value = null
    loading = Promise.resolve()
  }

  return { user, isAuthenticated, currentShop, ensureLoaded, fetchUser, login, logout, clear }
})
