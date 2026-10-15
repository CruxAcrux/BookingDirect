import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'

import { i18n, setLocale } from '@/i18n'

import ServiceForm from '../components/ServiceForm.vue'

function mountForm() {
  return mount(ServiceForm, { global: { plugins: [i18n] } })
}

async function fill(wrapper: ReturnType<typeof mountForm>, values: Record<string, string>) {
  const inputs = wrapper.findAll('input[type="text"]')
  const byLabel = (label: string) =>
    inputs.find(
      (input) => wrapper.find(`label[for="${input.attributes('id') ?? ''}"]`).text() === label,
    )

  for (const [label, value] of Object.entries(values)) await byLabel(label)?.setValue(value)
}

beforeEach(() => {
  setLocale('en')
})

describe('ServiceForm', () => {
  it('converts kronor to öre and empty optional fields to null', async () => {
    const wrapper = mountForm()
    await fill(wrapper, { Name: 'Skin fade', Duration: '45', Price: '349,50' })

    await wrapper.find('form').trigger('submit')

    await vi.waitFor(() => {
      expect(wrapper.emitted('submit')).toHaveLength(1)
    })
    expect(wrapper.emitted('submit')?.[0]?.[0]).toEqual({
      name: 'Skin fade',
      description: null,
      duration_minutes: 45,
      buffer_minutes: 0,
      price_ore: 34950,
      rebook_after_days: null,
      is_active: true,
    })
  })

  it('rejects durations that are not in steps of five minutes', async () => {
    const wrapper = mountForm()
    await fill(wrapper, { Name: 'Trim', Duration: '32', Price: '200' })

    await wrapper.find('form').trigger('submit')

    await vi.waitFor(() => {
      expect(wrapper.text()).toContain('Use steps of 5 minutes.')
    })
    expect(wrapper.emitted('submit')).toBeUndefined()
  })
})
