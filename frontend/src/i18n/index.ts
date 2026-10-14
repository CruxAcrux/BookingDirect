import { createI18n } from 'vue-i18n'

import en from '@/locales/en.json'
import sv from '@/locales/sv.json'

export type MessageSchema = typeof sv
export const SUPPORTED_LOCALES = ['sv', 'en'] as const
export type AppLocale = (typeof SUPPORTED_LOCALES)[number]

const STORAGE_KEY = 'locale'

export function isAppLocale(value: unknown): value is AppLocale {
  return SUPPORTED_LOCALES.includes(value as AppLocale)
}

function initialLocale(): AppLocale {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    if (isAppLocale(stored)) return stored
  } catch {
    // Storage can be unavailable (private mode, blocked cookies); fall back to the default.
  }
  return 'sv'
}

const numberFormats = {
  currency: { style: 'currency', currency: 'SEK', maximumFractionDigits: 0 },
} as const

const datetimeFormats = {
  time: { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' },
  date: { weekday: 'short', day: 'numeric', month: 'short' },
} as const

export const i18n = createI18n<[MessageSchema], AppLocale, false>({
  legacy: false,
  locale: initialLocale(),
  fallbackLocale: 'en',
  messages: { sv, en },
  numberFormats: { sv: numberFormats, en: numberFormats },
  datetimeFormats: { sv: datetimeFormats, en: datetimeFormats },
})

export function setLocale(locale: AppLocale): void {
  i18n.global.locale.value = locale
  document.documentElement.lang = locale
  try {
    localStorage.setItem(STORAGE_KEY, locale)
  } catch {
    // Not persisting the choice is acceptable.
  }
}
