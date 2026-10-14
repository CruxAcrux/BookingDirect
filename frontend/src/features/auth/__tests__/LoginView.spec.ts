import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import { i18n, setLocale } from '@/i18n'
import { api } from '@/lib/api'
import { apiResult, makeUser } from '@/test/fixtures'

import LoginView from '../views/LoginView.vue'

vi.mock('@/lib/api', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/lib/api')>()),
  api: { GET: vi.fn(), POST: vi.fn() },
  ensureCsrfCookie: vi.fn(),
}))

const postMock = vi.mocked(api.POST)

async function mountLogin(query: Record<string, string> = {}) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/login', name: 'login', component: LoginView },
      { path: '/forgot-password', name: 'forgot-password', component: { template: '<div />' } },
      { path: '/dashboard/:rest(.*)*', name: 'dashboard', component: { template: '<div />' } },
    ],
  })
  await router.push({ name: 'login', query })

  const wrapper = mount(LoginView, { global: { plugins: [router, i18n] } })
  return { wrapper, router }
}

async function fillAndSubmit(wrapper: Awaited<ReturnType<typeof mountLogin>>['wrapper']) {
  await wrapper.find('input[type="email"]').setValue('sara@example.com')
  await wrapper.find('input[type="password"]').setValue('secret-password')
  await wrapper.find('form').trigger('submit')
}

beforeEach(() => {
  setActivePinia(createPinia())
  setLocale('en')
  postMock.mockReset()
})

describe('LoginView', () => {
  it('validates the fields before calling the API', async () => {
    const { wrapper } = await mountLogin()

    await wrapper.find('form').trigger('submit')

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('This field is required.')
    })
    expect(wrapper.find('input[type="email"]').attributes('aria-invalid')).toBe('true')
    expect(postMock).not.toHaveBeenCalled()
  })

  it('shows the server error on the email field', async () => {
    postMock.mockResolvedValue(
      apiResult(422, {
        message: 'These credentials do not match our records.',
        errors: { email: ['These credentials do not match our records.'] },
      }),
    )
    const { wrapper } = await mountLogin()

    await fillAndSubmit(wrapper)

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('These credentials do not match our records.')
    })
  })

  it('tells the user to wait when rate limited', async () => {
    postMock.mockResolvedValue(apiResult(429, { message: 'Too Many Attempts.' }))
    const { wrapper } = await mountLogin()

    await fillAndSubmit(wrapper)

    await vi.waitFor(() => {
      expect(wrapper.get('[role="alert"]').text()).toBe(
        'Too many attempts. Wait a minute and try again.',
      )
    })
  })

  it('signs in and continues to the requested page', async () => {
    postMock.mockResolvedValue(apiResult(200, { data: makeUser({ locale: 'en' }) }))
    const { wrapper, router } = await mountLogin({ redirect: '/dashboard/calendar' })

    await fillAndSubmit(wrapper)

    await vi.waitFor(() => {
      expect(router.currentRoute.value.fullPath).toBe('/dashboard/calendar')
    })
    expect(postMock).toHaveBeenCalledWith('/auth/login', {
      body: { email: 'sara@example.com', password: 'secret-password', remember: false },
    })
  })
})
