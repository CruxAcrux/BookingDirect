import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'
import { createMemoryHistory, createRouter } from 'vue-router'

import { i18n, setLocale } from '@/i18n'
import { api } from '@/lib/api'
import { addDays, todayIn } from '@/lib/dates'

import BookingView from '../views/BookingView.vue'
import { makeDays, makeShop, result } from './fixtures'

vi.mock('@/lib/api', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/lib/api')>()),
  api: { GET: vi.fn(), POST: vi.fn() },
}))

const getMock = vi.mocked(api.GET)
const postMock = vi.mocked(api.POST)
const tomorrow = addDays(todayIn('Europe/Stockholm'), 1)

async function mountAt(query: Record<string, string>) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/s/:slug/book',
        name: 'book',
        component: BookingView,
        props: () => ({ shop: makeShop() }),
      },
      { path: '/b/:token', name: 'manage-booking', component: { template: '<div />' } },
    ],
  })
  await router.push({ name: 'book', params: { slug: 'fade-studio' }, query })

  const wrapper = mount(
    { template: '<RouterView />' },
    { global: { plugins: [router, i18n, [VueQueryPlugin, { queryClient: new QueryClient() }]] } },
  )
  await flushPromises()
  return { wrapper, router }
}

beforeEach(() => {
  setLocale('en')
  getMock.mockReset()
  postMock.mockReset()
  getMock.mockImplementation((path: string) => {
    if (path === '/public/shops/{shop}/me')
      return result(401, { message: 'Unauthenticated.' }) as never
    if (path === '/public/shops/{shop}/availability/next') return result(200, {}) as never
    return result(200, { data: makeDays(todayIn('Europe/Stockholm'), tomorrow) }) as never
  })
})

describe('BookingView', () => {
  it('walks from service to barber, offering only barbers who do the service', async () => {
    const { wrapper, router } = await mountAt({})

    await wrapper
      .findAll('button')
      .find((button) => button.text().includes('Beard trim'))
      ?.trigger('click')
    await flushPromises()

    expect(router.currentRoute.value.query).toEqual({ service: '2' })
    const text = wrapper.text()
    expect(text).toContain('First available')
    expect(text).toContain('Ali')
    expect(text).not.toContain('Samir')
  })

  it('opens straight on the time step from a "book again" link, with the barber\'s own price', async () => {
    const { wrapper } = await mountAt({ service: '1', barber: '7' })

    expect(wrapper.text()).toContain('Pick a time')
    expect(wrapper.text()).toContain('450')
  })

  it('drops a barber who does not do the chosen service', async () => {
    const { wrapper, router } = await mountAt({ service: '2', barber: '7' })

    expect(router.currentRoute.value.query.barber).toBeUndefined()
    expect(router.currentRoute.value.query.service).toBe('2')
    expect(wrapper.text()).toContain('Who would you like?')
  })

  it('verifies the phone, books and goes to the booking page', async () => {
    postMock.mockImplementation((path: string) => {
      if (path === '/public/shops/{shop}/otp')
        return result(202, { expires_in_minutes: 10, demo_code: '123456' }) as never
      if (path === '/public/shops/{shop}/otp/verify')
        return result(200, {
          data: { id: 1, name: null, phone: '+46701234567', email: null },
        }) as never
      return result(201, { data: {}, manage_token: 'secret-token' }) as never
    })
    const { wrapper, router } = await mountAt({
      service: '1',
      barber: 'any',
      date: tomorrow,
      at: `${tomorrow}T08:00:00Z`,
    })

    await wrapper.get('input[autocomplete="name"]').setValue('Kim')
    await wrapper.get('input[type="tel"]').setValue('0701234567')
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Send code')
      ?.trigger('click')
    await flushPromises()
    expect(wrapper.text()).toContain('123456')

    await wrapper.get('input[autocomplete="one-time-code"]').setValue('123456')
    await wrapper.get('form').trigger('submit')

    await vi.waitFor(() => {
      expect(router.currentRoute.value.fullPath).toBe('/b/secret-token?new=1')
    })
    expect(postMock).toHaveBeenCalledWith('/public/shops/{shop}/bookings', {
      params: { path: { shop: 'fade-studio' } },
      body: {
        service_id: 1,
        barber_id: null,
        starts_at: `${tomorrow}T08:00:00Z`,
        name: 'Kim',
        email: null,
        note: null,
      },
    })
  })

  it('sends the customer back to pick a new time when the slot was just taken', async () => {
    getMock.mockImplementation((path: string) => {
      if (path === '/public/shops/{shop}/me')
        return result(200, {
          data: { id: 1, name: 'Kim', phone: '+46701234567', email: null },
        }) as never
      if (path === '/public/shops/{shop}/availability/next') return result(200, {}) as never
      return result(200, { data: makeDays(todayIn('Europe/Stockholm'), tomorrow) }) as never
    })
    postMock.mockResolvedValue(result(409, { message: 'This time was just taken.' }))
    const { wrapper, router } = await mountAt({
      service: '1',
      barber: '8',
      date: tomorrow,
      at: `${tomorrow}T08:00:00Z`,
    })

    expect(wrapper.text()).toContain('Signed in as +46701234567')
    await wrapper.get('form').trigger('submit')

    await vi.waitFor(() => {
      expect(router.currentRoute.value.query.at).toBeUndefined()
    })
    expect(router.currentRoute.value.query).toMatchObject({
      service: '1',
      barber: '8',
      date: tomorrow,
    })
  })
})
