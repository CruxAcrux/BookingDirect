import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { QueryClient, VueQueryPlugin } from '@tanstack/vue-query'

import { i18n, setLocale } from '@/i18n'
import { api } from '@/lib/api'
import { todayIn, addDays } from '@/lib/dates'

import SlotPicker from '../components/SlotPicker.vue'
import { makeDays, result } from './fixtures'

vi.mock('@/lib/api', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@/lib/api')>()),
  api: { GET: vi.fn(), POST: vi.fn() },
}))

const getMock = vi.mocked(api.GET)
const today = todayIn('Europe/Stockholm')
const tomorrow = addDays(today, 1)

function mountPicker(props: Record<string, unknown> = {}) {
  return mount(SlotPicker, {
    props: {
      slug: 'fade-studio',
      timezone: 'Europe/Stockholm',
      serviceId: 1,
      barberId: null,
      maxDaysAhead: 60,
      selected: null,
      date: null,
      ...props,
    },
    global: { plugins: [i18n, [VueQueryPlugin, { queryClient: new QueryClient() }]] },
  })
}

beforeEach(() => {
  setLocale('en')
  getMock.mockReset()
  getMock.mockResolvedValue(result(200, { data: makeDays(today, tomorrow) }))
})

describe('SlotPicker', () => {
  it('asks for two weeks starting today and greys out days without times', async () => {
    const wrapper = mountPicker()
    await flushPromises()

    expect(getMock).toHaveBeenCalledWith('/public/shops/{shop}/availability', {
      params: {
        path: { shop: 'fade-studio' },
        query: { service_id: 1, barber_id: null, from: today, to: addDays(today, 13) },
      },
    })
    const days = wrapper.findAll('[role="radiogroup"]')[0]?.findAll('button') ?? []
    expect(days[0]?.attributes('disabled')).toBeDefined()
    expect(days[1]?.attributes('disabled')).toBeUndefined()
  })

  it('opens the first day with free times', async () => {
    const wrapper = mountPicker()
    await flushPromises()

    expect(wrapper.emitted('update:date')?.[0]).toEqual([tomorrow])
  })

  it('names the barber next to each time for "first available"', async () => {
    const wrapper = mountPicker({ date: tomorrow, barberNames: { 7: 'Samir', 8: 'Ali' } })
    await flushPromises()

    const times = wrapper.findAll('[role="radiogroup"]')[1]?.findAll('button') ?? []
    expect(times.map((button) => button.text())).toEqual(['10:00Ali', '10:15Samir'])
  })

  it('emits the chosen slot', async () => {
    const wrapper = mountPicker({ date: tomorrow })
    await flushPromises()

    await wrapper.findAll('[role="radiogroup"]')[1]?.findAll('button')[1]?.trigger('click')

    expect(wrapper.emitted('select')?.[0]?.[0]).toMatchObject({
      starts_at: `${tomorrow}T08:15:00Z`,
      barber_id: 7,
    })
  })

  it('stops at the booking horizon', async () => {
    const wrapper = mountPicker({ maxDaysAhead: 10 })
    await flushPromises()

    const later = wrapper.get('button[aria-label="Later days"]')
    expect(later.attributes('disabled')).toBeDefined()
    expect(getMock.mock.calls[0]?.[1]).toMatchObject({
      params: { query: { to: addDays(today, 10) } },
    })
  })
})
