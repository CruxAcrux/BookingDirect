<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const year = new Date().getFullYear()
</script>

<template>
  <footer class="border-t border-line">
    <div
      class="mx-auto flex max-w-6xl justify-between px-4 py-6 font-mono text-xs text-ink-soft sm:px-8"
    >
      <span>{{ t('app.name') }} © {{ year }}</span>
      <span>{{ t('footer.madeIn') }}</span>
    </div>
  </footer>
</template>
