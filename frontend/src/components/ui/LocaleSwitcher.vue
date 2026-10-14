<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import { SUPPORTED_LOCALES, setLocale, type AppLocale } from '@/i18n'

const { t, locale } = useI18n()

function select(next: AppLocale) {
  if (next !== locale.value) setLocale(next)
}
</script>

<template>
  <div
    role="group"
    :aria-label="t('locale.label')"
    class="flex border border-ink font-mono text-xs"
  >
    <button
      v-for="code in SUPPORTED_LOCALES"
      :key="code"
      type="button"
      :lang="code"
      :aria-pressed="locale === code"
      :title="t(`locale.${code}`)"
      class="px-2.5 py-1 uppercase transition-colors not-aria-pressed:hover:bg-paper-deep aria-pressed:bg-ink aria-pressed:text-paper"
      @click="select(code)"
    >
      {{ code }}
    </button>
  </div>
</template>
