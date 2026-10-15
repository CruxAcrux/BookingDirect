import { describe, expect, it } from 'vitest'

import { currentStep, parseDraft } from '../composables/useBookingDraft'

describe('booking draft', () => {
  it('reads the draft from the URL and ignores nonsense', () => {
    expect(
      parseDraft({ service: '2', barber: 'any', date: '2027-06-14', at: '2027-06-14T08:00:00Z' }),
    ).toEqual({
      serviceId: 2,
      barber: 'any',
      date: '2027-06-14',
      startsAt: '2027-06-14T08:00:00Z',
    })
    expect(parseDraft({ service: 'abc', barber: '-1', date: 'tomorrow' })).toEqual({
      serviceId: null,
      barber: null,
      date: null,
      startsAt: null,
    })
  })

  it('opens the first unanswered step, so "book again" links skip ahead', () => {
    expect(currentStep(parseDraft({}))).toBe('service')
    expect(currentStep(parseDraft({ service: '2' }))).toBe('barber')
    expect(currentStep(parseDraft({ service: '2', barber: '5' }))).toBe('time')
    expect(
      currentStep(parseDraft({ service: '2', barber: 'any', at: '2027-06-14T08:00:00Z' })),
    ).toBe('details')
  })
})
