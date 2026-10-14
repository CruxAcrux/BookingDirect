<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
</script>

<template>
  <a
    href="#main"
    class="sr-only z-50 bg-ink px-3 py-2 text-paper focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
  >
    {{ t('app.skipToContent') }}
  </a>
  <RouterView />
</template>
