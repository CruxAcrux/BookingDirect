import { useI18n } from 'vue-i18n'

/** Locale-aware formatting for prices, durations and the API's local date-times. */
export function useFormat() {
  const i18n = useI18n()

  /** Prices come from the API in öre. Whole kronor are shown without decimals. */
  const price = (ore: number) =>
    i18n.n(ore / 100, { key: 'currency', minimumFractionDigits: ore % 100 === 0 ? 0 : 2 })

  const duration = (minutes: number) => i18n.t('common.minutes', { n: minutes })

  /**
   * Formats "2026-11-03T09:00" exactly as written, in the shop's local time. The value
   * is read as UTC and formatted as UTC so the browser's own timezone never shifts it.
   */
  const localDateTime = (value: string, options: Intl.DateTimeFormatOptions) =>
    new Intl.DateTimeFormat(i18n.locale.value, { ...options, timeZone: 'UTC' }).format(
      new Date(`${value}:00Z`),
    )

  return { price, duration, localDateTime }
}
