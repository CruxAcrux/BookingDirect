<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import FormAlert from '@/components/ui/FormAlert.vue'
import { useAuthStore } from '@/stores/auth'

const { t, d } = useI18n()
const auth = useAuthStore()

const now = new Date()
const greetingKey = computed(() => {
  const hour = now.getHours()
  if (hour < 10) return 'dashboard.greeting.morning'
  if (hour < 17) return 'dashboard.greeting.day'
  return 'dashboard.greeting.evening'
})
const firstName = computed(() => auth.user?.name.split(' ')[0] ?? '')
</script>

<template>
  <div class="mx-auto max-w-6xl px-4 py-10 sm:px-8 md:py-14">
    <p class="font-mono text-xs tracking-[0.2em] text-pole uppercase">{{ d(now, 'long') }}</p>
    <h1 class="mt-3 text-5xl leading-none font-black uppercase font-condensed sm:text-6xl">
      {{ t(greetingKey, { name: firstName }) }}
    </h1>

    <FormAlert v-if="!auth.currentShop" class="mt-8 max-w-xl">
      {{ auth.user?.is_platform_admin ? t('dashboard.adminNoShop') : t('dashboard.noShop') }}
    </FormAlert>

    <section
      v-else
      class="mt-10 border-2 border-dashed border-line px-6 py-12 text-center"
      aria-labelledby="today-title"
    >
      <h2 id="today-title" class="text-2xl font-extrabold uppercase font-condensed">
        {{ t('dashboard.today.title') }}
      </h2>
      <p class="mx-auto mt-2 max-w-md text-ink-soft">{{ t('dashboard.today.body') }}</p>
    </section>
  </div>
</template>
