import type { AvailabilityDay, PublicShop } from '../api'

export function makeShop(overrides: Partial<PublicShop> = {}): PublicShop {
  return {
    name: 'Fade Studio',
    slug: 'fade-studio',
    address: 'Andra Långgatan 12',
    phone: '+4631123456',
    email: null,
    timezone: 'Europe/Stockholm',
    locale: 'sv',
    logo_url: null,
    cover_url: null,
    max_days_ahead: 60,
    cancellation_window_hours: 24,
    walk_ins_enabled: true,
    opening_hours: [],
    services: [
      { id: 1, name: 'Skin fade', description: null, duration_minutes: 45, price_ore: 42000 },
      { id: 2, name: 'Beard trim', description: null, duration_minutes: 20, price_ore: 22000 },
    ],
    barbers: [
      {
        id: 7,
        display_name: 'Samir',
        bio: null,
        avatar_url: null,
        services: [{ service_id: 1, price_ore: 45000, duration_minutes: 45 }],
        portfolio: [],
      },
      {
        id: 8,
        display_name: 'Ali',
        bio: null,
        avatar_url: null,
        services: [
          { service_id: 1, price_ore: 42000, duration_minutes: 45 },
          { service_id: 2, price_ore: 22000, duration_minutes: 20 },
        ],
        portfolio: [],
      },
    ],
    ...overrides,
  }
}

/** Two days: the first fully booked, the second with two free times. */
export function makeDays(from: string, second: string): AvailabilityDay[] {
  return [
    { date: from, slots: [] },
    {
      date: second,
      slots: [
        { starts_at: `${second}T08:00:00Z`, date: second, time: '10:00', barber_id: 8 },
        { starts_at: `${second}T08:15:00Z`, date: second, time: '10:15', barber_id: 7 },
      ],
    },
  ]
}

/** The subset of openapi-fetch's result that our code reads. */
export function result<T>(status: number, body?: T) {
  const ok = status >= 200 && status < 300
  return {
    data: ok ? body : undefined,
    error: ok ? undefined : body,
    response: { ok, status } as Response,
  }
}
