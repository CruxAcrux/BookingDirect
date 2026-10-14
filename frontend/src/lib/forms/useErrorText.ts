import { useI18n } from 'vue-i18n'

/**
 * Client-side schemas use i18n keys as messages, while server errors arrive already
 * translated. This resolves either to display text.
 */
export function useErrorText() {
  const i18n = useI18n()

  return (message: string | undefined): string | undefined =>
    message && i18n.te(message) ? i18n.t(message) : message
}
