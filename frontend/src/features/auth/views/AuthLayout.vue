<script setup lang="ts">
import { useI18n } from 'vue-i18n'

import LocaleSwitcher from '@/components/ui/LocaleSwitcher.vue'

const { t } = useI18n()
</script>

<template>
  <div class="grid min-h-dvh md:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
    <aside
      class="relative flex flex-col justify-between bg-ink px-4 py-5 text-paper sm:px-8 md:py-8"
    >
      <RouterLink to="/" class="text-2xl font-extrabold tracking-tight uppercase font-condensed">
        Booking<span class="text-pole">Direct</span>
      </RouterLink>
      <div class="hidden md:block">
        <p class="font-mono text-xs tracking-[0.2em] text-paper/60 uppercase">
          {{ t('auth.layout.kicker') }}
        </p>
        <p
          class="mt-3 text-6xl leading-[0.9] font-black text-balance uppercase font-condensed lg:text-7xl"
        >
          {{ t('auth.layout.title') }}
        </p>
      </div>
      <div
        class="absolute inset-x-0 bottom-0 h-1.5 pole-stripe md:inset-x-auto md:inset-y-0 md:right-0 md:h-auto md:w-2"
        aria-hidden="true"
      />
    </aside>

    <main
      id="main"
      tabindex="-1"
      class="flex flex-col px-4 py-6 focus:outline-none sm:px-8 md:py-8"
    >
      <div class="flex justify-end">
        <LocaleSwitcher />
      </div>
      <div class="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-10">
        <RouterView />
      </div>
    </main>
  </div>
</template>
