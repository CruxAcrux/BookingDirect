<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import SiteFooter from '@/components/layout/SiteFooter.vue'
import SiteHeader from '@/components/layout/SiteHeader.vue'

const { t } = useI18n()
</script>

<template>
  <a
    href="#main"
    class="sr-only z-50 bg-ink px-3 py-2 text-paper focus:not-sr-only focus:fixed focus:top-3 focus:left-3"
  >
    {{ t('app.skipToContent') }}
  </a>
  <div class="flex min-h-dvh flex-col">
    <SiteHeader />
    <main id="main" tabindex="-1" class="flex-1 focus:outline-none">
      <RouterView />
    </main>
    <SiteFooter />
  </div>
</template>
